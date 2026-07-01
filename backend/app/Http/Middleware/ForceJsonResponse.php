<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force every API request to be treated as JSON so that framework helpers
 * (validation, auth, model-binding) throw JSON-renderable exceptions even
 * when a client forgets the `Accept: application/json` header.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
