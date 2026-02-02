<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Bisa melihat dokumen?
     * Pemilik, approver, manager tim, atau admin
     */
    public function view(User $user, Document $document): bool
    {
        // Pemilik dokumen
        if ($document->submitter_id === $user->id) {
            return true;
        }

        // Approver di salah satu langkah
        if ($document->approvalSteps()->where('approver_id', $user->id)->exists()) {
            return true;
        }

        // Manager dari tim yang sama
        if ($user->isManager() && $document->submitter->department_id === $user->department_id) {
            return true;
        }

        // Admin
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Bisa mengedit dokumen?
     * Hanya pemilik + status Draft atau Returned
     */
    public function update(User $user, Document $document): bool
    {
        return $document->submitter_id === $user->id && $document->canBeEdited();
    }

    /**
     * Bisa menghapus dokumen?
     * Hanya pemilik + status Draft
     */
    public function delete(User $user, Document $document): bool
    {
        return $document->submitter_id === $user->id
            && $document->canBeDeleted();
    }

    /**
     * Bisa membatalkan dokumen?
     * Hanya pemilik + status Pending
     */
    public function cancel(User $user, Document $document): bool
    {
        return $document->submitter_id === $user->id
            && $document->canBeCancelled();
    }
}
