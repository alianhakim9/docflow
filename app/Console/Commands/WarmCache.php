<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up application cache with frequently accessed data';

    public function __construct(
        private CacheService $cacheService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Warming up cache...');

        $this->cacheService->warmMasterData();

        $this->info('✓ Master data cached');
        $this->info('Cache warming completed!');

        return self::SUCCESS;
    }
}
