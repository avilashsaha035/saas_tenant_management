<?php

namespace App\Contracts;

use App\Models\Tenant;

interface AnalyticsServiceInterface
{
    /**
     * Get aggregated dashboard analytics for tenant, served from Redis cache.
     *
     * @param Tenant $tenant
     * @param bool $forceFresh
     * @return array
     */
    public function getDashboardData(Tenant $tenant, bool $forceFresh = false): array;

    /**
     * Invalidate tenant analytics cache in Redis.
     *
     * @param int $tenantId
     * @return void
     */
    public function invalidateCache(int $tenantId): void;
}
