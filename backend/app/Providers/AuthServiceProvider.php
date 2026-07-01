<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyInquiry;
use App\Policies\BlogPolicy;
use App\Policies\PropertyImagePolicy;
use App\Policies\PropertyInquiryPolicy;
use App\Policies\PropertyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Property::class        => PropertyPolicy::class,
        PropertyImage::class   => PropertyImagePolicy::class,
        PropertyInquiry::class => PropertyInquiryPolicy::class,
        Blog::class            => BlogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Admins bypass every gate/policy check.
        Gate::before(function ($user, $ability) {
            return $user->role === 'admin' ? true : null;
        });
    }
}
