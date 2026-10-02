<?php

namespace App\Http\Middleware;

use App\Contracts\FeatureLimitServiceInterface;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureLimit
{
    public function __construct(
        protected FeatureLimitServiceInterface $limitService
    ) {}

    /**
     * Handle incoming request by enforcing feature limits.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $feature
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = TenantContext::getTenant();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context not resolved.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($feature === 'customer') {
            $this->limitService->enforceCustomerLimit($tenant);
        } elseif ($feature === 'user') {
            $this->limitService->enforceUserLimit($tenant);
        } else {
            $this->limitService->enforceFeatureAccess($tenant, $feature);
        }

        return $next($request);
    }
}
