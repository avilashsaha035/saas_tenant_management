<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantSuspendedException;
use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request and bind tenant context.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws TenantSuspendedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // 1. Identify from authenticated Sanctum user
        if ($request->user() && $request->user()->tenant_id) {
            $tenant = Tenant::find($request->user()->tenant_id);
        }

        // 2. Identify from X-Tenant-ID header if not set yet
        if (!$tenant && $request->hasHeader('X-Tenant-ID')) {
            $tenantId = $request->header('X-Tenant-ID');
            $tenant = Tenant::find($tenantId);
        }

        // 3. Identify from X-Tenant-Slug header
        if (!$tenant && $request->hasHeader('X-Tenant-Slug')) {
            $slug = $request->header('X-Tenant-Slug');
            $tenant = Tenant::where('slug', $slug)->first();
        }

        // 4. Identify from host/subdomain if applicable
        if (!$tenant) {
            $host = $request->getHost();
            $tenant = Tenant::where('domain', $host)->first();
        }

        if ($tenant) {
            if ($tenant->isSuspended()) {
                throw new TenantSuspendedException("Tenant '{$tenant->name}' is suspended. Please contact support.");
            }

            TenantContext::setTenant($tenant);
        }

        return $next($request);
    }

    /**
     * Clear tenant context when request lifecycle terminates.
     */
    public function terminate(Request $request, Response $response): void
    {
        TenantContext::reset();
    }
}
