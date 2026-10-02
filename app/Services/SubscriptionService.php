<?php

namespace App\Services;

use App\Contracts\SubscriptionServiceInterface;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Change or upgrade/downgrade tenant subscription plan.
     */
    public function changePlan(Tenant $tenant, Plan $newPlan): Subscription
    {
        return DB::transaction(function () use ($tenant, $newPlan) {
            $currentSub = $this->getCurrentSubscription($tenant);

            if ($currentSub) {
                // If it is the same plan, return it
                if ($currentSub->plan_id === $newPlan->id && $currentSub->isActive()) {
                    return $currentSub;
                }

                // Mark current subscription canceled or expired
                $currentSub->update([
                    'status'     => 'canceled',
                    'cancels_at' => now(),
                    'ends_at'    => now(),
                ]);
            }

            // Create new active subscription
            $newSubscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id'   => $newPlan->id,
                'status'    => 'active',
                'starts_at' => now(),
                'ends_at'   => now()->addMonth(),
            ]);

            // Log activity
            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => auth()->id(),
                'action'      => 'plan_changed',
                'entity_type' => 'Subscription',
                'entity_id'   => $newSubscription->id,
                'description' => "Changed subscription plan to {$newPlan->name}.",
                'properties'  => [
                    'previous_plan_id' => $currentSub?->plan_id,
                    'new_plan_id'      => $newPlan->id,
                ],
                'ip_address'  => request()->ip(),
            ]);

            // Invalidate Redis cache for this tenant
            Cache::forget("tenant:{$tenant->id}:dashboard:analytics");
            Cache::forget("tenant:{$tenant->id}:usage_summary");

            return $newSubscription->load('plan.features');
        });
    }

    /**
     * Cancel active tenant subscription.
     */
    public function cancelSubscription(Tenant $tenant): Subscription
    {
        return DB::transaction(function () use ($tenant) {
            $subscription = $this->getCurrentSubscription($tenant);

            if (!$subscription) {
                throw new \RuntimeException('No active subscription found to cancel.');
            }

            $subscription->update([
                'status'     => 'canceled',
                'cancels_at' => now(),
            ]);

            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => auth()->id(),
                'action'      => 'subscription_canceled',
                'entity_type' => 'Subscription',
                'entity_id'   => $subscription->id,
                'description' => "Subscription for plan {$subscription->plan->name} canceled.",
                'ip_address'  => request()->ip(),
            ]);

            // Invalidate Redis cache for this tenant
            Cache::forget("tenant:{$tenant->id}:dashboard:analytics");
            Cache::forget("tenant:{$tenant->id}:usage_summary");

            return $subscription;
        });
    }

    /**
     * Get the tenant's current active subscription.
     */
    public function getCurrentSubscription(Tenant $tenant): ?Subscription
    {
        return Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();
    }
}
