<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `->middleware('role:admin')` or `'role:agent,admin'`.
 * Admins implicitly pass every role check.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        // Admin is a superset of every role.
        if ($user->role === 'admin' || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return ApiResponse::error(
            'You do not have permission to access this resource.',
            403
        );
    }
}
