<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();

        // Role-based redirect setelah login
        Fortify::redirects('login', function () {
            $user = auth()->user();
            if ($user && $user->role === \App\Models\User::ROLE_ADMIN) {
                return '/admin';
            }
            return '/';
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Custom rate limiters untuk endpoint sensitif (Task 14).
     *
     * - 'review-submit': max 5 submit per menit per IP+user untuk
     *   menutupi celah multi-akun yang memakai IP yang sama (PRD weakness #8).
     * - 'review-report': max 10 report per 5 menit untuk cegah report-spam.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('review-submit', function (Request $request) {
            $key = $request->user()?->id
                ? "user:{$request->user()->id}|ip:{$request->ip()}"
                : "ip:{$request->ip()}";

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak submit. Coba lagi dalam 1 menit.',
                ], 429);
            });
        });

        RateLimiter::for('review-report', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
