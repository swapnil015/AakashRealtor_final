<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
| Allows the Nuxt SSR frontend and the mobile apps to call the API.
| Origins come from CORS_ALLOWED_ORIGINS (comma-separated) so they can be
| changed per-environment without a code deploy.
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))
)));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins,

    // Allow native/mobile builds and preview deployments by pattern.
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://([a-z0-9-]+\.)?aakashrealtor\.com$#',
        '#^capacitor://localhost$#',   // Capacitor mobile shell
        '#^http://localhost$#',         // Flutter / Expo dev
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 60 * 60 * 24,

    // True so Sanctum's SPA cookie flow works from the Nuxt origin.
    'supports_credentials' => true,

];
