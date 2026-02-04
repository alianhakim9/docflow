<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalStep;
use App\Models\Document;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Services\CacheService;

class DashboardController extends Controller
{
    public function __construct(
        private CacheService $cacheService,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = $this->cacheService->getUserCache(
            $user->id,
            'dashboard:stats',
            function () use ($user) {
                if ($user->isAdmin()) {
                    return $this->getAdminStats();
                }

                if ($user->isManager()) {
                    return $this->getManagerStats($user);
                }

                return $this->getStaffStats($user);
            }
        );

        return response()->json(['data' => $stats]);
    }

    public function myDocuments(Request $request): JsonResponse
    {
        $user = $request->user();

        $documents = Document::with([
            'documentType:id,name,slug',
            'approvalSteps.approver:id,name'
        ])
            ->where('submitter_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'data' => $documents->map(fn($doc) => [
                'id' => $doc->id,
                'document_number' => $doc->document_number,
                'title' => $doc->title,
                'status' => $doc->status->value,
                'status_label' => $doc->status->label(),
                'document_type' => $doc->documentType ? [
                    'name' => $doc->documentType->name,
                ] : null,
                'submitted_at' => $doc->submitted_at?->format('Y-m-d H:i'),
                'created_at' => $doc->created_at?->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function myApprovals(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvals = ApprovalStep::with([
            'document' => function ($q) {
                $q->with([
                    'submitter:id,name,email',
                    'documentType:id,name,slug'
                ]);
            }
        ])->where('approver_id', $user->id)
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('due_at')
            ->paginate(10);

        return response()->json([
            'data' => $approvals->map(fn($step) => [
                'id' => $step->id,
                'step_name' => $step->step_name,
                'document' => $step->document ? [
                    'id' => $step->document->id,
                    'document_number' => $step->document->document_number,
                    'title' => $step->document->title,
                    'submitter' => $step->document->submitter ? [
                        'name' => $step->document->submitter_name,
                    ] : null,
                    'document_type' => $step->document->documentType ? [
                        'name' => $step->document->documentType->name
                    ] : null
                ] : null,
                'due_at' => $step->due_at?->format('Y-m-d H:i'),
                'is_sla_breached' => $step->isSLABreached(),
            ]),
            'meta' => [
                'current_page' => $approvals->currentPage(),
                'last_page' => $approvals->lastPage(),
                'total' => $approvals->total(),
            ]
        ]);
    }

    private function getAdminStats(): array
    {
        $totalDocuments = Document::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $avgApprovalTime = $this->calculateAverageApprovalTime();

        $slaBreaches = ApprovalStep::where('status', ApprovalStatus::PENDING->value)
            ->where('due_at', '<', now())
            ->count();

        return [
            'total_documents' => $totalDocuments,
            'by_status' => [
                'draft' => $statusCounts['draf'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'returned' => $statusCounts['returned'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0
            ],
            'avg_approval_time_hours' => $avgApprovalTime,
            'sla_breaches' => $slaBreaches,
        ];
    }

    private function getManagerStats(object $user): array
    {
        $teamDocuments = Document::where(function ($q) use ($user) {
            $q->where('submitter_id', $user->id)
                ->orWhere('submitter_id', function ($sub) use ($user) {
                    $sub->select('id')->from('users')->where('reports_to', $user->id);
                });
        });

        $totalTeamDocuments = $teamDocuments->count();

        $statusCounts = $teamDocuments->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $pendingApprovals = ApprovalStep::where('approver_id', $user->id)
            ->where('status', ApprovalStatus::PENDING->value)
            ->count();

        return [
            'total_team_documents' => $totalTeamDocuments,
            'by_status' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
            ],
            'pending_approvals' => $pendingApprovals,
        ];
    }

    private function getStaffStats(object $user): array
    {
        $myDocuments = Document::where('submitter_id', $user->id);

        $statusCounts = $myDocuments->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total_documents' => $myDocuments->count(),
            'by_status' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
            ],
        ];
    }

    private function calculateAverageApprovalTime(): float
    {
        $documents = Document::whereNotNull('submitted_at')
            ->whereNotNull('completed_at')
            ->get([
                'submitted_at',
                'completed_at'
            ]);

        if ($documents->isEmpty()) {
            return 0.0;
        }

        $totalHours = $documents->sum(function ($doc) {
            return $doc->submitted_at->diffInHours($doc->completed_at);
        });

        return round($totalHours / $documents->count(), 1);
    }
}
