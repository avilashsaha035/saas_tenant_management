<?php

namespace App\Services;

use App\Contracts\FeatureLimitServiceInterface;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;

class FeatureLimitService implements FeatureLimitServiceInterface
{
    /**
     * Check if tenant can create another user.
     */
    public function canCreateUser(Tenant $tenant): bool
    {
        $features = $tenant->getPlanFeatures();
        if (!$features) {
            return false;
        }

        $currentCount = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        return $currentCount < $features->max_users;
    }

    /**
     * Assert that tenant can create a user, or throw SubscriptionLimitExceededException.
     */
    public function enforceUserLimit(Tenant $tenant): void
    {
        $features = $tenant->getPlanFeatures();
        $allowedLimit = $features ? $features->max_users : 0;

        $currentCount = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        if ($currentCount >= $allowedLimit) {
            throw new SubscriptionLimitExceededException(
                'max_users',
                $currentCount,
                $allowedLimit,
                "User limit reached for tenant plan. Current users: {$currentCount}/{$allowedLimit}. Please upgrade your plan."
            );
        }
    }

    /**
     * Check if tenant can create another customer.
     */
    public function canCreateCustomer(Tenant $tenant): bool
    {
        $features = $tenant->getPlanFeatures();
        if (!$features) {
            return false;
        }

        $currentCount = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        return $currentCount < $features->max_customers;
    }

    /**
     * Assert that tenant can create a customer, or throw SubscriptionLimitExceededException.
     */
    public function enforceCustomerLimit(Tenant $tenant): void
    {
        $features = $tenant->getPlanFeatures();
        $allowedLimit = $features ? $features->max_customers : 0;

        $currentCount = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        if ($currentCount >= $allowedLimit) {
            throw new SubscriptionLimitExceededException(
                'max_customers',
                $currentCount,
                $allowedLimit,
                "Customer limit reached for tenant plan. Current customers: {$currentCount}/{$allowedLimit}. Please upgrade your plan."
            );
        }
    }

    /**
     * Check if tenant has access to a specific feature flag.
     */
    public function hasFeature(Tenant $tenant, string $featureKey): bool
    {
        $features = $tenant->getPlanFeatures();
        if (!$features) {
            return false;
        }

        if ($featureKey === 'has_advanced_analytics') {
            return (bool) $features->has_advanced_analytics;
        }

        if ($featureKey === 'has_api_access') {
            return (bool) $features->has_api_access;
        }

        if (!empty($features->custom_features) && isset($features->custom_features[$featureKey])) {
            return (bool) $features->custom_features[$featureKey];
        }

        return false;
    }

    /**
     * Assert that tenant has access to a feature, or throw SubscriptionLimitExceededException.
     */
    public function enforceFeatureAccess(Tenant $tenant, string $featureKey): void
    {
        if (!$this->hasFeature($tenant, $featureKey)) {
            throw new SubscriptionLimitExceededException(
                $featureKey,
                0,
                1,
                "Feature '{$featureKey}' is not available on your current plan. Please upgrade to unlock."
            );
        }
    }

    /**
     * Get real-time usage summary for dashboard.
     */
    public function getUsageSummary(Tenant $tenant): array
    {
        $features = $tenant->getPlanFeatures();
        $plan = $tenant->getCurrentPlan();

        $maxUsers = $features?->max_users ?? 0;
        $maxCustomers = $features?->max_customers ?? 0;

        $currentUsers = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        $currentCustomers = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        return [
            'plan' => [
                'name'  => $plan?->name ?? 'None',
                'slug'  => $plan?->slug ?? 'none',
                'price' => $plan?->price ?? 0.00,
            ],
            'users' => [
                'current'    => $currentUsers,
                'limit'      => $maxUsers,
                'remaining'  => max(0, $maxUsers - $currentUsers),
                'percentage' => $maxUsers > 0 ? round(($currentUsers / $maxUsers) * 100, 1) : 100,
            ],
            'customers' => [
                'current'    => $currentCustomers,
                'limit'      => $maxCustomers,
                'remaining'  => max(0, $maxCustomers - $currentCustomers),
                'percentage' => $maxCustomers > 0 ? round(($currentCustomers / $maxCustomers) * 100, 1) : 100,
            ],
            'features' => [
                'has_advanced_analytics' => (bool) ($features?->has_advanced_analytics ?? false),
                'has_api_access'         => (bool) ($features?->has_api_access ?? false),
                'rate_limit_per_minute'  => $features?->rate_limit_per_minute ?? 60,
            ],
        ];
    }
}
