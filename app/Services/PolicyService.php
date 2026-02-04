<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Policy;
use App\Models\User;

class PolicyService
{
    public function evaluate(
        User $user,
        Document $document
    ): void {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Policy> $policies */
        $policies = Policy::active()
            ->forDocumentType($document->document_type_id)
            ->byPriority()
            ->get();

        foreach ($policies as $policy) {
            $policy->evaluate($user, $document);
        }
    }
}
