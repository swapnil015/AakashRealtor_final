<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    | First-party SPA domains that may authenticate via the stateful cookie
    | guard. Third-party / mobile clients use Bearer personal access tokens
    | instead and don't need to be listed here.
    */
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', implode(',', [
        'localhost',
        'localhost:3000',
        '127.0.0.1',
        '127.0.0.1:3000',
        '::1',
        parse_url(env('FRONTEND_URL', ''), PHP_URL_HOST) ?: '',
    ]))),

    'guard' => ['web'],

    // Minutes until issued tokens expire (null = never). Driven by env so
    // mobile (long-lived) and web can differ across deployments.
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION') !== null && env('SANCTUM_TOKEN_EXPIRATION') !== ''
        ? (int) env('SANCTUM_TOKEN_EXPIRATION')
        : null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'akr_'),

    'middleware' => [
        'authenticate_session'    => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies'         => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token'     => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
