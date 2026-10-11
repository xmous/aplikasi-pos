<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Paginator::useBootstrapFive();

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('owner') ? true : null;
        });

        RateLimiter::for('pos-write', function ($request) {
            return Limit::perMinute(config('pos-security.write_requests_per_minute', 120))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('pos-export', function ($request) {
            return Limit::perMinute(config('pos-security.exports_per_minute', 10))
                ->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(Login::class, function (Login $event): void {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });

        Event::listen(Failed::class, function (Failed $event): void {
            logger()->warning('Authentication failed', [
                'email_hash' => isset($event->credentials['email'])
                    ? hash('sha256', strtolower((string) $event->credentials['email']))
                    : null,
                'ip' => request()->ip(),
            ]);
        });
    }
}
