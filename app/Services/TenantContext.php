<?php

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $currentTenant = null;

    /**
     * Set the current active tenant.
     */
    public static function setTenant(?Tenant $tenant): void
    {
        self::$currentTenant = $tenant;
    }

    /**
     * Get the current active tenant.
     */
    public static function getTenant(): ?Tenant
    {
        return self::$currentTenant;
    }

    /**
     * Get the ID of the current tenant, or null.
     */
    public static function getId(): ?int
    {
        return self::$currentTenant?->id;
    }

    /**
     * Check if a tenant is currently set.
     */
    public static function hasTenant(): bool
    {
        return self::$currentTenant !== null;
    }

    /**
     * Reset the tenant context (useful for testing and queued jobs).
     */
    public static function reset(): void
    {
        self::$currentTenant = null;
    }
}
