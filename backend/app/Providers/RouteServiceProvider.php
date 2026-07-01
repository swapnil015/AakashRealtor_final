<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // General API ceiling: 60 req/min per user (or IP for guests).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Tight throttle for auth endpoints to slow credential stuffing.
        RateLimiter::for('auth', function (Request $request) {
            $max = (int) config('auth.rate_limit', 5);
            return Limit::perMinute($max)->by($request->ip());
        });

        // Public lead forms (inquiries / requirements): generous but bounded.
        RateLimiter::for('leads', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        // Image uploads: expensive, keep low.
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
