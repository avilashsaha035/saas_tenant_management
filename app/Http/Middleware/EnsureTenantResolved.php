<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantResolved
{
    /**
     * Ensure that a valid tenant context is active for this route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!TenantContext::hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'No active tenant found for this request. Please authenticate or provide X-Tenant-ID header.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
