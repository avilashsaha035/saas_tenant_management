<?php

namespace App\Contracts;

use App\Models\Tenant;

interface TenantServiceInterface
{
    /**
     * Register a new company/tenant with owner user and subscription.
     *
     * @param array $data
     * @return array Contains 'tenant', 'user', 'token', and 'subscription'
     */
    public function registerTenant(array $data): array;

    /**
     * Get tenant profile with current subscription and plan.
     *
     * @param Tenant $tenant
     * @return Tenant
     */
    public function getTenantProfile(Tenant $tenant): Tenant;

    /**
     * Update tenant company profile or settings.
     *
     * @param Tenant $tenant
     * @param array $data
     * @return Tenant
     */
    public function updateTenantProfile(Tenant $tenant, array $data): Tenant;
}
