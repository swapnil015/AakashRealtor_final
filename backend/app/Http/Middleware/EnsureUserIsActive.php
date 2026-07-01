<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks suspended accounts (is_active = false) from authenticated routes
 * without revoking their tokens — flip the flag back to restore access.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return ApiResponse::error(
                'Your account has been deactivated. Please contact support.',
                403
            );
        }

        return $next($request);
    }
}
