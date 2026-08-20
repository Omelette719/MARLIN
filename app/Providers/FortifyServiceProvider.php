<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();

            if (! $user) {
                return null;
            }

            if (! Hash::check((string) $request->password, $user->password)) {
                // Only an already-active account can still be locked out by
                // this — an already-deactivated one has nothing further to
                // lock, so there's no point spending a write incrementing it
                // forever on every subsequent guess.
                if ($user->aktif) {
                    $user->increment('failed_login_attempts');

                    if ($user->failed_login_attempts >= 6) {
                        $user->update(['aktif' => false]);

                        throw ValidationException::withMessages([
                            Fortify::username() => __('Akun dinonaktifkan karena 6 kali percobaan masuk yang gagal secara berturut-turut. Hubungi admin untuk mengaktifkan kembali.'),
                        ]);
                    }
                }

                return null;
            }

            if (! $user->aktif) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Akun ini telah dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.'),
                ]);
            }

            if ($user->failed_login_attempts > 0) {
                $user->update(['failed_login_attempts' => 0]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            // Wide enough that the 6-consecutive-failures account lockout in
            // authenticateUsing() above is always what a user actually sees
            // first — a tighter per-minute cap here would throw Laravel's
            // bare 429 page before the friendlier lockout message ever gets
            // a chance to fire. This is still a real ceiling against
            // scripted brute-forcing, just not the primary defense anymore.
            return Limit::perMinute(10)->by($throttleKey);
        });
    }
}
