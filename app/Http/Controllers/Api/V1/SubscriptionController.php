<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\SubscriptionServiceInterface;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\ChangePlanRequest;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService
    ) {}

    /**
     * List all available public subscription plans with feature limits.
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::with('features')->where('is_active', true)->get();

        return $this->successResponse(PlanResource::collection($plans), 'Plans retrieved successfully.');
    }

    /**
     * Get current tenant's active subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        $subscription = $this->subscriptionService->getCurrentSubscription($tenant);

        if (!$subscription) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        return $this->successResponse(new SubscriptionResource($subscription->load('plan.features')), 'Active subscription retrieved.');
    }

    /**
     * Upgrade or downgrade subscription plan.
     */
    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        Gate::authorize('manageSubscription', $tenant);

        $newPlan = Plan::where('slug', $request->validated('plan_slug'))->firstOrFail();

        $subscription = $this->subscriptionService->changePlan($tenant, $newPlan);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            "Subscription successfully upgraded/changed to '{$newPlan->name}'."
        );
    }

    /**
     * Cancel current active subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();

        Gate::authorize('manageSubscription', $tenant);

        $subscription = $this->subscriptionService->cancelSubscription($tenant);

        return $this->successResponse(
            new SubscriptionResource($subscription->load('plan.features')),
            'Subscription cancelled successfully.'
        );
    }
}
