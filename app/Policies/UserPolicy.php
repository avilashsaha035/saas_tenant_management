<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isActive() && $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true);
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        if (!$user->isActive() || $user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Self-update
        if ($user->id === $model->id) {
            return true;
        }

        // Owner can update anyone
        if ($user->isOwner()) {
            return true;
        }

        // Admin can update non-owner
        if ($user->isAdmin() && !$model->isOwner()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->isActive() || $user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Cannot delete self or tenant owner
        if ($user->id === $model->id || $model->isOwner()) {
            return false;
        }

        return in_array($user->role, [User::ROLE_OWNER, User::ROLE_ADMIN], true);
    }
}
