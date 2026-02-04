<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        $filters = $request->only([
            'status',
            'document_type_id',
            'search',
            'start_date',
            'end_date',
            'per_page'
        ]);

        $documents = $this->documentService->getDocuments($request->user(), $filters)->through(fn($doc) => $this->formatDocument($doc));

        return response()->json([
            'data' => $documents,
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function store(
        StoreDocumentRequest $request
    ): JsonResponse {
        $document = $this->documentService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Dokumen berhasil dibuat.',
            'data' => $this->formatDocument($document),
        ], 201);
    }

    public function show(
        int $id
    ): JsonResponse {
        $document = $this->documentService->getDocument($id);

        $this->authorize('view', $document);

        return response()->json([
            'data' => $this->formatDocument($document)
        ]);
    }

    public function update(
        UpdateDocumentRequest  $request,
        int $id
    ): JsonResponse {
        $document = Document::findOrFail($id);

        $this->authorize('update', $document);

        $document = $this->documentService->update($document, $request->validated());

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => $this->formatDocument($document),
        ]);
    }

    public function destroy(
        int $id
    ): JsonResponse {
        $document = Document::findOrFail($id);

        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.'
        ]);
    }

    public function submit(
        int $id
    ): JsonResponse {
        $document = Document::findOrFail($id);

        $this->authorize('update', $document);

        $document = $this->documentService->submit($document);

        return response()->json([
            'message' => 'Dokumen berhasil diajukan.',
            'data' => $this->formatDocument($document),
        ]);
    }

    public function cancel(
        int $id
    ): JsonResponse {
        $document = Document::findOrFail($id);

        $this->authorize('cancel', $document);

        $document = $this->documentService->cancel($document);

        return response()->json([
            'message' => 'Dokumen berhasil dibatalkan.',
            'data' => $this->formatDocument($document),
        ]);
    }

    private function formatDocument(
        Document $document
    ): array {
        return [
            'id' => $document->id,
            'document_number' => $document->document_number,
            'title' => $document->title,
            'status' => $document->status->value,
            'status_label' => $document->status->label(),
            'document_type' => $document->documentType ? [
                'id' => $document->documentType->id,
                'name' => $document->documentType->name,
                'slug' => $document->documentType->slug,
            ] : null,
            'submitter' => $document->submitter ? [
                'id' => $document->submitter->id,
                'name' => $document->submitter->name,
                'email' => $document->submitter->email,
            ] : null,
            'data' => $document->data,
            'approval_steps' => $document->approvalSteps?->map(fn($step) => $this->formatApprovalStep($step) ?? []),
            'attachments' => $document->attachments?->map(fn($att) => [
                'id' => $att->id,
                'original_filename' => $att->original_filename,
                'mime_type' => $att->mime_type,
                'file_size' => $att->file_size,
            ]) ?? [],
            'submitted_at' => $document->submitted_at?->format('Y-m-d H:i'),
            'completed_at' => $document->completed_at?->format('Y-m-d H:i'),
            'created_at' => $document->created_at?->format('Y-m-d H:i')
        ];
    }

    private function formatApprovalStep(
        object $step
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
            'comments' => $step->comments,
            'action_taken_at' => $step->action_taken_at?->format('Y-m-d H:i'),
            'due_at' => $step->due_at?->format('Y-m-d H:i'),
            'is_sla_breached' => $step->isSLABreached(),
        ];
    }
}
