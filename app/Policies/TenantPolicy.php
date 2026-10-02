<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine whether the user can view tenant details.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isActive() && $user->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can update the tenant profile/settings.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isActive() &&
            $user->tenant_id === $tenant->id &&
            $user->isOwner();
    }

    /**
     * Determine whether the user can manage subscriptions (upgrade/cancel).
     */
    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        return $user->isActive() &&
            $user->tenant_id === $tenant->id &&
            $user->isOwner();
    }
}
