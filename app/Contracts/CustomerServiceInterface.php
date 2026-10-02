<?php

namespace App\Contracts;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerServiceInterface
{
    public function listCustomers(Tenant $tenant, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getCustomer(Tenant $tenant, int $id): ?Customer;

    public function createCustomer(Tenant $tenant, array $data, ?int $userId = null): Customer;

    public function updateCustomer(Tenant $tenant, Customer $customer, array $data, ?int $userId = null): Customer;

    public function deleteCustomer(Tenant $tenant, Customer $customer, ?int $userId = null): bool;
}
