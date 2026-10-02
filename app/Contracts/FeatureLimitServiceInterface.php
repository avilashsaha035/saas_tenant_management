<?php

namespace App\Contracts;

use App\Models\Tenant;

interface FeatureLimitServiceInterface
{
    /**
     * Check if tenant can create another user under current plan.
     */
    public function canCreateUser(Tenant $tenant): bool;

    /**
     * Assert that tenant can create a user, or throw SubscriptionLimitExceededException.
     */
    public function enforceUserLimit(Tenant $tenant): void;

    /**
     * Check if tenant can create another customer under current plan.
     */
    public function canCreateCustomer(Tenant $tenant): bool;

    /**
     * Assert that tenant can create a customer, or throw SubscriptionLimitExceededException.
     */
    public function enforceCustomerLimit(Tenant $tenant): void;

    /**
     * Check if tenant has access to a specific feature flag.
     */
    public function hasFeature(Tenant $tenant, string $featureKey): bool;

    /**
     * Assert that tenant has access to a feature, or throw SubscriptionLimitExceededException.
     */
    public function enforceFeatureAccess(Tenant $tenant, string $featureKey): void;

    /**
     * Get real-time usage statistics against plan limits.
     */
    public function getUsageSummary(Tenant $tenant): array;
}
