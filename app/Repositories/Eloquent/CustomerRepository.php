<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Get paginated and filtered list of customers.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query();

        // Search across name, email, company, phone
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by status (lead, active, churned)
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Filter by minimum revenue
        if (isset($filters['min_revenue'])) {
            $query->where('revenue', '>=', (float) $filters['min_revenue']);
        }

        // Sorting
        $allowedSorts = ['name', 'created_at', 'status', 'revenue'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Find a customer by ID within current tenant scope.
     */
    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    /**
     * Create a new customer for the current tenant.
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    /**
     * Soft delete a customer.
     */
    public function delete(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }
}
