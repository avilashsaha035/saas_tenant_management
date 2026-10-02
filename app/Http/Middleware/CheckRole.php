<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle incoming request by checking if user has any of the required roles.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Owners bypass all role restrictions
        if ($user->isOwner()) {
            return $next($request);
        }

        if (empty($roles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden: You do not have the required permission/role for this action.',
            'errors'  => [
                'required_roles' => $roles,
                'your_role'      => $user->role,
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
