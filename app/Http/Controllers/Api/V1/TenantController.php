<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TenantServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\UpdateTenantProfileRequest;
use App\Http\Resources\TenantResource;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantController extends BaseApiController
{
    public function __construct(
        protected TenantServiceInterface $tenantService
    ) {}

    /**
     * Show current tenant details and active plan.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        Gate::authorize('view', $tenant);

        $profile = $this->tenantService->getTenantProfile($tenant);

        return $this->successResponse(new TenantResource($profile), 'Tenant profile retrieved.');
    }

    /**
     * Update tenant company profile and settings.
     */
    public function update(UpdateTenantProfileRequest $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        Gate::authorize('update', $tenant);

        $updated = $this->tenantService->updateTenantProfile($tenant, $request->validated());

        return $this->successResponse(new TenantResource($updated), 'Tenant profile updated successfully.');
    }
}
