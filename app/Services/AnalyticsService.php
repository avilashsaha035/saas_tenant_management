<?php

namespace App\Services;

use App\Contracts\AnalyticsServiceInterface;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService implements AnalyticsServiceInterface
{
    public const CACHE_TTL_SECONDS = 3600; // 1 hour TTL

    /**
     * Get aggregated dashboard analytics for tenant, served from Redis cache.
     */
    public function getDashboardData(Tenant $tenant, bool $forceFresh = false): array
    {
        $cacheKey = "tenant:{$tenant->id}:dashboard:analytics";

        if ($forceFresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($tenant) {
            return $this->computeDashboardMetrics($tenant);
        });
    }

    /**
     * Compute optimized database metrics with single aggregated queries.
     */
    protected function computeDashboardMetrics(Tenant $tenant): array
    {
        $plan = $tenant->getCurrentPlan();
        $features = $tenant->getPlanFeatures();
        $subscription = $tenant->activeSubscription;

        // 1. Quota calculations
        $maxUsers = $features?->max_users ?? 0;
        $maxCustomers = $features?->max_customers ?? 0;

        $userCount = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        // 2. High performance single-query aggregation for customer counts and revenue
        $customerStats = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = "lead" THEN 1 ELSE 0 END) as lead_count,
                SUM(CASE WHEN status = "churned" THEN 1 ELSE 0 END) as churned_count,
                COALESCE(SUM(revenue), 0) as total_revenue,
                COALESCE(AVG(revenue), 0) as avg_revenue
            ')
            ->first();

        $totalCustomers = (int) ($customerStats->total ?? 0);
        $activeCustomers = (int) ($customerStats->active_count ?? 0);
        $leadCustomers = (int) ($customerStats->lead_count ?? 0);
        $churnedCustomers = (int) ($customerStats->churned_count ?? 0);
        $totalRevenue = round((float) ($customerStats->total_revenue ?? 0), 2);
        $avgRevenue = round((float) ($customerStats->avg_revenue ?? 0), 2);

        // 3. Subscription status and days remaining
        $daysRemaining = null;
        if ($subscription && $subscription->ends_at) {
            $daysRemaining = max(0, (int) Carbon::now()->diffInDays($subscription->ends_at, false));
        }

        // 4. Recent activities eager loading user
        $recentActivities = ActivityLog::withoutGlobalScopes()
            ->with(['user:id,name,email,role'])
            ->where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->take(8)
            ->get()
            ->map(function ($log) {
                return [
                    'id'          => $log->id,
                    'action'      => $log->action,
                    'description' => $log->description,
                    'user'        => $log->user ? [
                        'id'    => $log->user->id,
                        'name'  => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'created_at'  => $log->created_at->toIso8601String(),
                ];
            });

        return [
            'tenant' => [
                'id'     => $tenant->id,
                'name'   => $tenant->name,
                'slug'   => $tenant->slug,
                'status' => $tenant->status,
            ],
            'subscription' => [
                'plan_name'      => $plan?->name ?? 'Free',
                'status'         => $subscription?->status ?? 'active',
                'billing_cycle'  => $plan?->billing_cycle ?? 'monthly',
                'price'          => $plan?->price ?? 0.00,
                'days_remaining' => $daysRemaining,
                'ends_at'        => $subscription?->ends_at?->toIso8601String(),
            ],
            'quota_usage' => [
                'users' => [
                    'current'           => $userCount,
                    'limit'             => $maxUsers,
                    'percentage_used'   => $maxUsers > 0 ? round(($userCount / $maxUsers) * 100, 1) : 100,
                    'is_limit_reached'  => $userCount >= $maxUsers,
                ],
                'customers' => [
                    'current'           => $totalCustomers,
                    'limit'             => $maxCustomers,
                    'percentage_used'   => $maxCustomers > 0 ? round(($totalCustomers / $maxCustomers) * 100, 1) : 100,
                    'is_limit_reached'  => $totalCustomers >= $maxCustomers,
                ],
            ],
            'metrics' => [
                'total_customers'   => $totalCustomers,
                'active_customers'  => $activeCustomers,
                'lead_customers'    => $leadCustomers,
                'churned_customers' => $churnedCustomers,
                'total_revenue'     => $totalRevenue,
                'avg_revenue'       => $avgRevenue,
            ],
            'recent_activities' => $recentActivities,
            'cached_at'         => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Invalidate tenant analytics cache in Redis.
     */
    public function invalidateCache(int $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:dashboard:analytics");
        Cache::forget("tenant:{$tenantId}:usage_summary");
    }
}
