<?php

namespace App\Services;

use App\Contracts\FeatureLimitServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected FeatureLimitServiceInterface $limitService
    ) {}

    /**
     * List paginated users for tenant.
     */
    public function listUsers(Tenant $tenant, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::where('tenant_id', $tenant->id);

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create user within tenant with feature limit enforcement.
     */
    public function createUser(Tenant $tenant, array $data, ?int $creatorId = null): User
    {
        // 1. Enforce user limit
        $this->limitService->enforceUserLimit($tenant);

        // 2. Prepare user data
        $data['tenant_id'] = $tenant->id;
        $data['password']  = Hash::make($data['password']);
        $data['role']      = $data['role'] ?? User::ROLE_MEMBER;
        $data['status']    = $data['status'] ?? 'active';

        $user = User::create($data);

        // 3. Log activity
        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $creatorId,
            'action'      => 'user_created',
            'entity_type' => 'User',
            'entity_id'   => $user->id,
            'description' => "Created new team user '{$user->name}' with role '{$user->role}'.",
            'properties'  => ['email' => $user->email, 'role' => $user->role],
            'ip_address'  => request()->ip(),
        ]);

        // 4. Invalidate Redis cache
        $this->invalidateTenantCache($tenant->id);

        return $user;
    }

    /**
     * Update user details.
     */
    public function updateUser(Tenant $tenant, User $user, array $data, ?int $updaterId = null): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $updaterId,
            'action'      => 'user_updated',
            'entity_type' => 'User',
            'entity_id'   => $user->id,
            'description' => "Updated user '{$user->name}'.",
            'properties'  => $data,
            'ip_address'  => request()->ip(),
        ]);

        $this->invalidateTenantCache($tenant->id);

        return $user->fresh();
    }

    /**
     * Delete user from tenant.
     */
    public function deleteUser(Tenant $tenant, User $user, ?int $deleterId = null): bool
    {
        if ($user->isOwner()) {
            throw new \RuntimeException('Cannot delete the tenant owner account.');
        }

        $deleted = (bool) $user->delete();

        if ($deleted) {
            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => $deleterId,
                'action'      => 'user_deleted',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'description' => "Deleted user '{$user->name}'.",
                'ip_address'  => request()->ip(),
            ]);

            $this->invalidateTenantCache($tenant->id);
        }

        return $deleted;
    }

    protected function invalidateTenantCache(int $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:dashboard:analytics");
        Cache::forget("tenant:{$tenantId}:usage_summary");
    }
}
