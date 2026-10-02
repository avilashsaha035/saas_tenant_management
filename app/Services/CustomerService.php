<?php

namespace App\Services;

use App\Contracts\CustomerServiceInterface;
use App\Contracts\FeatureLimitServiceInterface;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Tenant;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected FeatureLimitServiceInterface $limitService
    ) {}

    /**
     * List paginated customers for tenant.
     */
    public function listCustomers(Tenant $tenant, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->paginate($filters, $perPage);
    }

    /**
     * Get specific customer for tenant.
     */
    public function getCustomer(Tenant $tenant, int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    /**
     * Create customer with feature limit enforcement and cache invalidation.
     */
    public function createCustomer(Tenant $tenant, array $data, ?int $userId = null): Customer
    {
        // 1. Enforce plan limit before creating
        $this->limitService->enforceCustomerLimit($tenant);

        // 2. Ensure tenant_id is explicitly set
        $data['tenant_id'] = $tenant->id;

        // 3. Persist via repository
        $customer = $this->customerRepository->create($data);

        // 4. Record activity log
        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $userId,
            'action'      => 'customer_created',
            'entity_type' => 'Customer',
            'entity_id'   => $customer->id,
            'description' => "Created customer '{$customer->name}' ({$customer->email}).",
            'properties'  => $customer->toArray(),
            'ip_address'  => request()->ip(),
        ]);

        // 5. Invalidate tenant Redis cache
        $this->invalidateTenantCache($tenant->id);

        return $customer;
    }

    /**
     * Update customer and invalidate cache.
     */
    public function updateCustomer(Tenant $tenant, Customer $customer, array $data, ?int $userId = null): Customer
    {
        $updated = $this->customerRepository->update($customer, $data);

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $userId,
            'action'      => 'customer_updated',
            'entity_type' => 'Customer',
            'entity_id'   => $updated->id,
            'description' => "Updated customer '{$updated->name}'.",
            'properties'  => $data,
            'ip_address'  => request()->ip(),
        ]);

        $this->invalidateTenantCache($tenant->id);

        return $updated;
    }

    /**
     * Soft delete customer and invalidate cache.
     */
    public function deleteCustomer(Tenant $tenant, Customer $customer, ?int $userId = null): bool
    {
        $deleted = $this->customerRepository->delete($customer);

        if ($deleted) {
            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => $userId,
                'action'      => 'customer_deleted',
                'entity_type' => 'Customer',
                'entity_id'   => $customer->id,
                'description' => "Deleted customer '{$customer->name}'.",
                'ip_address'  => request()->ip(),
            ]);

            $this->invalidateTenantCache($tenant->id);
        }

        return $deleted;
    }

    /**
     * Invalidate Redis cache keys for this tenant.
     */
    protected function invalidateTenantCache(int $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:dashboard:analytics");
        Cache::forget("tenant:{$tenantId}:usage_summary");
    }
}
