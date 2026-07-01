<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

/*
|--------------------------------------------------------------------------
| Application bootstrap (Laravel 11 style)
|--------------------------------------------------------------------------
| Routing, middleware and the global exception handler are all configured
| here. Every API error is rendered through the same JSON envelope:
|   { "success": false, "data": null, "message": "...", "errors": {...} }
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // API requests must always negotiate JSON, even from a browser.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // NOTE: the API is token-based (Sanctum personal access tokens) for both
        // the Nuxt web app and the mobile app — it is NOT a cookie/SPA flow.
        // `statefulApi()` is therefore intentionally NOT enabled: turning it on
        // makes requests from SANCTUM_STATEFUL_DOMAINS (e.g. localhost:3000) go
        // through the web group + CSRF, causing 419 "CSRF token mismatch" on
        // POSTs like /auth/register. Bearer-token auth needs no CSRF.

        // Route-level aliases used by the route files & controllers.
        $middleware->alias([
            'role'      => \App\Http\Middleware\EnsureUserHasRole::class,
            'active'    => \App\Http\Middleware\EnsureUserIsActive::class,
            'honeypot'  => \App\Http\Middleware\BlockSpamBots::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render *every* exception on an /api/* path as our JSON envelope.
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null; // fall through to default (web) handling
            }

            return App\Support\ApiResponse::fromException($e);
        });
    })
    ->create();
