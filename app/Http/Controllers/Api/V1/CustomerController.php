<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\CustomerServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends BaseApiController
{
    public function __construct(
        protected CustomerServiceInterface $customerService
    ) {}

    /**
     * Display a paginated, filtered listing of tenant customers.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Customer::class);

        $tenant = TenantContext::getTenant();

        $filters = $request->only(['search', 'status', 'min_revenue', 'sort_by', 'sort_dir']);
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $paginator = $this->customerService->listCustomers($tenant, $filters, $perPage);

        return $this->successResponse(
            CustomerResource::collection($paginator->items()),
            'Customers retrieved successfully.',
            Response::HTTP_OK,
            [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        );
    }

    /**
     * Store a newly created customer in storage with limit enforcement.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $tenant = TenantContext::getTenant();

        $customer = $this->customerService->createCustomer(
            $tenant,
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        $customer = $this->customerService->getCustomer($tenant, $id);

        if (!$customer) {
            return $this->errorResponse('Customer not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $customer);

        return $this->successResponse(new CustomerResource($customer), 'Customer retrieved successfully.');
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        $customer = $this->customerService->getCustomer($tenant, $id);

        if (!$customer) {
            return $this->errorResponse('Customer not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $customer);

        $updated = $this->customerService->updateCustomer(
            $tenant,
            $customer,
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(new CustomerResource($updated), 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        $customer = $this->customerService->getCustomer($tenant, $id);

        if (!$customer) {
            return $this->errorResponse('Customer not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('delete', $customer);

        $this->customerService->deleteCustomer($tenant, $customer, auth()->id());

        return $this->successResponse(null, 'Customer deleted successfully.');
    }
}
