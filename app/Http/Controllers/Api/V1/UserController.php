<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\UserServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseApiController
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {}

    /**
     * Display a listing of the tenant users.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $tenant = TenantContext::getTenant();
        $filters = $request->only(['role', 'status', 'search']);
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $paginator = $this->userService->listUsers($tenant, $filters, $perPage);

        return $this->successResponse(
            UserResource::collection($paginator->items()),
            'Users retrieved successfully.',
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
     * Store a newly created team user with user limit enforcement.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $tenant = TenantContext::getTenant();

        $user = $this->userService->createUser(
            $tenant,
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(
            new UserResource($user),
            'Team member created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $user = User::where('tenant_id', $tenant->id)->find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $user);

        return $this->successResponse(new UserResource($user), 'User details retrieved.');
    }

    /**
     * Update the specified team user.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $user = User::where('tenant_id', $tenant->id)->find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $user);

        $updated = $this->userService->updateUser(
            $tenant,
            $user,
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(new UserResource($updated), 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $user = User::where('tenant_id', $tenant->id)->find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('delete', $user);

        $this->userService->deleteUser($tenant, $user, auth()->id());

        return $this->successResponse(null, 'User deleted successfully.');
    }
}
