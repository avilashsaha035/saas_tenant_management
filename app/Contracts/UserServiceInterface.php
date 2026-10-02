<?php

namespace App\Contracts;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function listUsers(Tenant $tenant, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function createUser(Tenant $tenant, array $data, ?int $creatorId = null): User;

    public function updateUser(Tenant $tenant, User $user, array $data, ?int $updaterId = null): User;

    public function deleteUser(Tenant $tenant, User $user, ?int $deleterId = null): bool;
}
