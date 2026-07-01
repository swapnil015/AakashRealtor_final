<?php

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        // Cookie/session guard used by Sanctum's stateful SPA flow.
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        // Bearer-token guard for mobile / third-party API clients.
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

    // Max login/register attempts per minute per IP (see RouteServiceProvider).
    'rate_limit' => (int) env('AUTH_RATE_LIMIT', 5),

];
