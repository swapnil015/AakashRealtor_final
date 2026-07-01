<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS for generated URLs (mail links, signed uploads) in prod.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
