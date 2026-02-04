<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\CacheService;

class DocumentObserver
{
    public function __construct(
        private CacheService $cacheService,
    ) {}

    /**
     * Setelah document di-update
     */
    public function updated(Document $document): void
    {
        $this->cacheService->forgetDocumentCache($document->id);
    }

    /**
     * Setelah document di-delete
     */
    public function deleted(Document $document): void
    {
        $this->cacheService->forgetDocumentCache($document->id);
        $this->cacheService->forgetUserCache($document->submitter_id);
        $this->cacheService->forgetDashboardCache($document->submitter_id);
    }
}
