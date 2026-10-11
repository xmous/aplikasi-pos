<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! config('pos-features.two_factor_enabled', false)) {
            $features = array_values(array_filter(
                (array) config('fortify.features', []),
                static fn (mixed $feature): bool =>
                    $feature !== 'two-factor-authentication'
            ));

            config(['fortify.features' => $features]);
        }
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        if (config('pos-features.two_factor_enabled', false)) {
            Fortify::twoFactorChallengeView(
                fn () => view('auth.two-factor-challenge')
            );
        }

        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = Str::lower(trim((string) $request->input(Fortify::username())));
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user || ! $user->is_active || ! Hash::check((string) $request->password, $user->password)) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());
            return Limit::perMinute(config('pos-security.login_attempts_per_minute', 5))->by($key);
        });

        if (config('pos-features.two_factor_enabled', false)) {
            RateLimiter::for(
                'two-factor',
                function (Request $request): Limit {
                    return Limit::perMinute(
                        config(
                            'pos-security.login_attempts_per_minute',
                            5
                        )
                    )->by(
                        (string) $request->session()->get('login.id')
                    );
                }
            );
        }
    }
}
