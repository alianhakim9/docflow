<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentService
{
    public function __construct(
        private PolicyService $policyService,
        private ApprovalService $approvalService,
        private CacheService $cacheService
    ) {}

    public function getDocuments(
        User $user,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Document::with([
            'submitter:id,name,email',
            'documentType:id,name,slug',
            'approvalSteps.approver:id,name'
        ]);

        if ($user->isAdmin()) {
        } else if ($user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('submitter_id', $user->id)
                    ->orWhere('submitter_id', function ($sub) use ($user) {
                        $sub->select('id')->from('users')->where('reports_to', $user->id);
                    });
            });
        } else {
            $query->where('submitter_id', $user->id);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['document_type_id'])) {
            $query->where('document_type_id', $filters['document_type_id']);
        }


        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', '%{$search}%')
                    ->orWhere('title', 'like', '%{$search}%');
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('submitted_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('submitted_at', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getDocument(int $documentId): Document
    {
        return $this->cacheService->getQueryCache(
            "document:{$documentId}",
            function () use ($documentId) {
                return Document::with([
                    'submitter:id,name,email',
                    'documentType:id,name,slug',
                    'approvalSteps' => function ($q) {
                        $q->orderBy('sequence')
                            ->with('approver:id,name,email');
                    },
                    'attachments',
                    'auditLogs' => function ($q) {
                        $q->orderByDesc('created_at');
                    },
                ])->findOrFail($documentId);
            },
            300 // 5 menit
        );
    }

    public function create(
        User $user,
        array $data
    ): Document {
        $documentType = DocumentType::active()
            ->findOrFail($data['document_type_id']);

        $document = Document::create([
            'document_number' => $this->generateDocumentNumber($documentType),
            'document_type_id' => $documentType->id,
            'submitter_id' => $user->id,
            'title' => $data['title'],
            'data' => $data['data'],
            'status' => DocumentStatus::DRAFT,
        ]);

        AuditLog::log('document.created', $document, $user, null, [
            'document_number' => $document->document_number,
            'status' => DocumentStatus::DRAFT->value,
        ]);

        // Invalidate cache
        $this->cacheService->forgetUserCache($user->id, 'documents');
        $this->cacheService->forgetDashboardCache($user->id);

        return $document;
    }

    public function update(
        Document $document,
        array $data
    ): Document {
        $oldData = [
            'title' => $document->title,
            'data' => $document->data,
        ];

        $document->update([
            'title' => $data['title'] ?? $document->title,
            'data' => $data['data'] ?? $document->data,
        ]);

        AuditLog::log('document.updated', $document, null, $oldData, [
            'title' => $document->title,
            'data' => $document->data,
        ]);

        // Invalidate cache
        $this->cacheService->forgetDocumentCache($document->id);

        return $document;
    }

    public function submit(
        Document $document,
    ): Document {
        $user = $document->submitter;

        $this->policyService->evaluate($user, $document);

        $document->update([
            'status' => DocumentStatus::PENDING,
            'submitted_at' => now()
        ]);

        $this->approvalService->initializeApprovalChain($document);

        AuditLog::log(
            'document.submitted',
            $document,
            $user,
            ['status' => DocumentStatus::DRAFT->value],
            ['status' => DocumentStatus::PENDING->value]
        );

        // Invalidate cache
        $this->cacheService->forgetDocumentCache($document->id);
        $this->cacheService->forgetUserCache($document->submitter_id);
        $this->cacheService->forgetDashboardCache($document->submitter_id);

        return $document;
    }

    public function cancel(
        Document $document
    ): Document {
        $user = $document->submitter;

        $document->update([
            'status' => DocumentStatus::CANCELLED
        ]);

        $document->approvalSteps()
            ->where('status', ApprovalStatus::PENDING->value)
            ->update(['status' => ApprovalStatus::SKIPPED->value]);

        AuditLog::log(
            'document.cancelled',
            $document,
            $user,
            ['status' => DocumentStatus::PENDING->value],
            ['status' => DocumentStatus::CANCELLED->value]
        );

        // Invalidate cache
        $this->cacheService->forgetDocumentCache($document->id);
        $this->cacheService->forgetDashboardCache($document->submitter_id);

        return $document;
    }

    public function delete(
        Document $document
    ): void {
        AuditLog::log('document.deleted', $document, null, [
            'document_number' => $document->document_number,
            'status' => $document->status->value,
        ]);

        $document->delete();
    }

    private function generateDocumentNumber(
        DocumentType $documentType
    ): string {
        $prefix = match ($documentType->slug) {
            'leave_request' => 'LV',
            'reimbursement' => 'RB',
            'purchase_request' => 'PR',
            default => 'DC',
        };

        $year = now()->format('Y');

        $lastNumber = Document::where('document_number', 'like', "{$prefix}-{$year}-%")
            ->pluck('document_number')
            ->first();

        $sequence = 1;

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $sequence = (int) end($parts) + 1;
        }

        return "{$prefix}-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
