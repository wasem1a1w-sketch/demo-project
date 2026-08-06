<?php

namespace App\Providers;

use App\Services\ExceptionTracker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        Gate::define('viewPulse', fn ($user) => $user->can('admin.access'));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10));

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            $handler->reportable(fn (Throwable $e) => ExceptionTracker::record($e));
        });
    }
}
