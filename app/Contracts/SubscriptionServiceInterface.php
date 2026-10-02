<?php

namespace App\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

interface SubscriptionServiceInterface
{
    /**
     * Change or upgrade/downgrade tenant subscription plan.
     */
    public function changePlan(Tenant $tenant, Plan $newPlan): Subscription;

    /**
     * Cancel active tenant subscription.
     */
    public function cancelSubscription(Tenant $tenant): Subscription;

    /**
     * Get the tenant's current active subscription.
     */
    public function getCurrentSubscription(Tenant $tenant): ?Subscription;
}
