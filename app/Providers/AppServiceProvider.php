<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            Request::setTrustedProxies(
                ['*'],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10));
    }
}
