<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\ApprovalStep;
use App\Models\ApprovalTemplate;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;

class ApprovalService
{
    public function initializeApprovalChain(Document $document)
    {
        $template = $this->resolveTemplate($document);

        if (!$template) {
            return;
        }

        $templateSteps = $template->steps()->orderBy('sequence')->get();

        foreach ($templateSteps as $templateStep) {
            $approver = $templateStep->resolveApprover($document);

            if (!$approver) {
                continue;
            }

            ApprovalStep::create([
                'document_id' => $document->id,
                'template_step_id' => $templateStep->id,
                'sequence' => $templateStep->sequence,
                'step_name' => $templateStep->step_name,
                'approver_id' => $approver->id,
                'status' => ApprovalStatus::PENDING,
                'sla_hours' => $templateStep->sla_hours,
                'due_at' => $templateStep->sla_hours
                    ? now()->addHours($templateStep->sla_hours) : null
            ]);
        }
    }

    public function approve(ApprovalStep $approvalStep, User $user, ?string $comments = null): ApprovalStep
    {
        $approvalStep->update([
            'status' => ApprovalStatus::APPROVED,
            'action_taken_at' => now(),
            'action_taken_by' => $user->id,
            'comments' => $comments
        ]);

        AuditLog::log(
            'approval.approved',
            $approvalStep->document,
            $user,
            ['status' => ApprovalStatus::PENDING->value],
            ['status' => ApprovalStatus::APPROVED->value, 'comments' => $comments]
        );

        $this->advanceWorkflow($approvalStep->document);

        return $approvalStep;
    }

    private function advanceWorkflow(Document $document): void
    {
        $pendingSteps = $document->approvalSteps()
            ->where('status', ApprovalStatus::PENDING->value)
            ->count();

        if ($pendingSteps === 0) {
            $allApproved = $document->approvalSteps()
                ->where('status', '!=', ApprovalStatus::APPROVED->value)
                ->where('status', '!=', ApprovalStatus::SKIPPED->value)
                ->doesntExist();

            if ($allApproved) {
                $document->update([
                    'status' => DocumentStatus::APPROVED,
                    'completed_at' => now(),
                ]);
            }

            AuditLog::log(
                'document.approved',
                $document,
                null,
                ['status' => DocumentStatus::PENDING->value],
                ['status' => DocumentStatus::APPROVED->value]
            );
        }
    }

    private function resolveTemplate(Document $document): ?ApprovalTemplate
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalTemplate> $templates */
        $templates = ApprovalTemplate::where('document_type_id', $document->document_type_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();

        foreach ($templates as $template) {
            if ($template->isApplicable($document)) {
                return $template;
            }
        }

        return null;
    }
}
