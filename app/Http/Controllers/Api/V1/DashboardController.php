<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\FeatureLimitServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(
        protected AnalyticsServiceInterface $analyticsService,
        protected FeatureLimitServiceInterface $limitService
    ) {}

    /**
     * Get aggregated dashboard analytics (cached with Redis).
     */
    public function analytics(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $forceFresh = $request->boolean('refresh', false);

        $data = $this->analyticsService->getDashboardData($tenant, $forceFresh);

        return $this->successResponse(
            $data,
            'Dashboard analytics retrieved successfully (served via Redis cache).'
        );
    }

    /**
     * Get real-time subscription quota usage and remaining limits.
     */
    public function usage(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $summary = $this->limitService->getUsageSummary($tenant);

        return $this->successResponse($summary, 'Subscription usage summary retrieved.');
    }
}
