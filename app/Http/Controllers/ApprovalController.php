<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Requests\ApproveDocumentRequest;
use App\Http\Requests\DelegateApprovalRequest;
use App\Http\Requests\RejectDocumentRequest;
use App\Http\Requests\ReturnDocumentRequest;
use App\Models\ApprovalStep;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalService $approvalService
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $approvals = ApprovalStep::with([
            'document' => function ($q) {
                $q->with([
                    'submitter:id,name,email',
                    'documentType:id,name,slug'
                ]);
            }
        ])->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_at')
            ->paginate(15)
            ->through(fn($step) => $this->formatApproval($step));

        return response()->json([
            'data' => $approvals,
            'meta' => [
                'current_page' => $approvals->currentPage(),
                'last_page' => $approvals->lastPage(),
                'per_page' => $approvals->perPage(),
                'total' => $approvals->total(),
            ]
        ]);
    }

    public function approve(
        ApproveDocumentRequest $request,
        int $id,
    ) {
        $approvalStep = ApprovalStep::with('document')->findOrFail($id);

        $this->authorize('approve', $approvalStep);

        $approvalStep = $this->approvalService->approve(
            $approvalStep,
            $request->user(),
            $request->input('comments')
        );

        return response()->json([
            'message' => 'Persejutuan berhasil diberikan.',
            'data' => $this->formatApproval($approvalStep)
        ]);
    }

    public function reject(
        RejectDocumentRequest $request,
        int $id
    ): JsonResponse {
        $approvalStep = ApprovalStep::with('document')->findOrFail($id);

        $this->authorize('approve', $approvalStep);

        $approvalStep = $this->approvalService->reject(
            $approvalStep,
            $request->user(),
            $request->input('comments')
        );

        return response()->json([
            'message' => 'Persetujuan berhasil ditolak.',
            'data' => $this->formatApproval($approvalStep)
        ]);
    }

    public function return_(
        ReturnDocumentRequest $request,
        int $id
    ): JsonResponse {
        $approvalStep = ApprovalStep::with('document')->findOrFail($id);

        $this->authorize('approve', $approvalStep);

        $approvalStep = $this->approvalService->return_(
            $approvalStep,
            $request->user(),
            $request->input('comments')
        );

        return response()->json([
            'message' => 'Dokumen berhasil dikembalikan.',
            'data' => $this->formatApproval($approvalStep)
        ]);
    }

    public function delegate(
        DelegateApprovalRequest $request,
        int $id
    ): JsonResponse {
        $approvalStep = ApprovalStep::with('document')->findOrFail($id);

        $this->authorize('delegate', $approvalStep);

        $approvalStep = $this->approvalService->delegate(
            $approvalStep,
            $request->user(),
            $request->input('delegate_to'),
            $request->input('end_date')
        );

        return response()->json([
            'message' => 'Persetujuan berhasil didelegasikan.',
            'data' => $this->formatApproval($approvalStep),
        ]);
    }

    public function formatApproval(
        ApprovalStep $step
    ): array {
        return [
            'id' => $step->id,
            'sequence' => $step->sequence,
            'step_name' => $step->step_name,
            'status' => $step->status->value,
            'status_label' => $step->status->label(),
            'approver' => $step->approver ? [
                'id' => $step->approver->id,
                'name' => $step->approver->name,
            ] : null,
            'delegated_from' => $step->delegatedFrom ? [
                'id' => $step->delegatedFrom->id,
                'name' => $step->delegatedFrom->name,
            ] : null,
            'document' => $step->document ? [
                'id' => $step->document->id,
                'document_number' => $step->document->document_number,
                'title' => $step->document->title,
                'status' => $step->document->status->value,
                'status_label' => $step->document->status->label(),
                'submitter' => $step->document->submitter ? [
                    'id' => $step->document->submitter->id,
                    'name' => $step->document->submitter->name,
                ] : null,
                'document_type' => $step->document->documentType ? [
                    'id' => $step->document->documentType->id,
                    'name' => $step->document->documentType->name,
                ] : null,
            ] : null,
            'comments' => $step->comments,
            'action_taken_at' => $step->action_taken_at?->format('Y-m-d H:i'),
            'due_at' => $step->due_at?->format('Y-m-d H:i'),
            'is_sla_breached' => $step->isSLABreached(),
        ];
    }
}
