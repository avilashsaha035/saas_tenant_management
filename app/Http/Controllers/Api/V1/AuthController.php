<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TenantServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterTenantRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseApiController
{
    public function __construct(
        protected TenantServiceInterface $tenantService
    ) {}

    /**
     * Register a new company/tenant, owner user, and subscription.
     */
    public function registerTenant(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->tenantService->registerTenant($request->validated());

        return $this->successResponse([
            'tenant' => new TenantResource($result['tenant']),
            'user'   => new UserResource($result['user']),
            'token'  => $result['token'],
        ], 'Tenant registered successfully with active subscription.', Response::HTTP_CREATED);
    }

    /**
     * Authenticate tenant user and return Sanctum API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid email or password credentials.', Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return $this->errorResponse('Your account is currently disabled. Please contact your company administrator.', Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::find($user->tenant_id);
        if ($tenant && $tenant->isSuspended()) {
            return $this->errorResponse('Your company account is suspended. Please contact support.', Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'user'   => new UserResource($user),
            'tenant' => $tenant ? new TenantResource($tenant->load('activeSubscription.plan.features')) : null,
            'token'  => $token,
        ], 'Login successful.');
    }

    /**
     * Invalidate current user's token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Successfully logged out.');
    }

    /**
     * Get authenticated user profile and tenant details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant?->load('activeSubscription.plan.features');

        return $this->successResponse([
            'user'   => new UserResource($user),
            'tenant' => $tenant ? new TenantResource($tenant) : null,
        ], 'Profile retrieved successfully.');
    }
}
