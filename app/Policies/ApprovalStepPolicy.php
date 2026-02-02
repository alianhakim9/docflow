<?php

namespace App\Policies;

use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApprovalStepPolicy
{
    /**
     * Bisa memberikan persetujuan?
     * Hanya approver yang ditugaskan + status Pending
     */
    public function approve(User $user, ApprovalStep $approvalStep): bool
    {
        return $approvalStep->approver_id === $user->id
            && $approvalStep->isPending();
    }

    /**
     * Bisa mendelegasikan?
     * Hanya approver yang ditugaskan + status Pending
     */
    public function delegate(User $user, ApprovalStep $approvalStep): bool
    {
        return $approvalStep->approver_id === $user->id
            && $approvalStep->isPending();
    }
}
