<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the specific customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->isActive() && $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->isActive() &&
            $user->tenant_id === $customer->tenant_id &&
            in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isActive() &&
            $user->tenant_id === $customer->tenant_id &&
            in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true);
    }
}
