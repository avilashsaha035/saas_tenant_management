<?php

namespace App\Jobs;

use App\Contracts\AnalyticsServiceInterface;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateTenantUsageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant
    ) {}

    /**
     * Execute the job to recompute and warm up Redis cache.
     */
    public function handle(AnalyticsServiceInterface $analyticsService): void
    {
        Log::info("Recalculating usage and warming Redis cache for Tenant [{$this->tenant->name} - ID: {$this->tenant->id}].");
        $analyticsService->getDashboardData($this->tenant, true);
    }
}
