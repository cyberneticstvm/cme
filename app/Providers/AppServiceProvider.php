<?php

namespace App\Providers;

use App\Auth\EnvironmentAdminProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('registration-email-check', fn (Request $request) => Limit::perMinute(30)->by('email-check:'.$request->ip()));
        $recoveryLimitResponse = fn (Request $request, array $headers) => response()->view('registrations.recover', ['recoveryThrottled' => true], 429, $headers);
        RateLimiter::for('registration-recovery', fn (Request $request) => [
            Limit::perMinute(5)->by('recovery-ip:'.$request->ip())->response($recoveryLimitResponse),
            Limit::perMinutes(10, 1)->by('recovery-email:'.hash('sha256', mb_strtolower(trim(is_string($request->input('email')) ? $request->input('email') : ''))))->response($recoveryLimitResponse),
        ]);
        Auth::provider('environment-admin', fn () => new EnvironmentAdminProvider);
        RateLimiter::for('admin-check-in', fn (Request $request) => Limit::perMinute(180)->by('admin-check-in:'.hash('sha256', $request->session()->getId().'|'.$request->ip())));
        RateLimiter::for('admin-export', fn (Request $request) => Limit::perMinute(10)->by('admin-export:'.$request->ip()));
        RateLimiter::for('admin-emails', fn (Request $request) => Limit::perMinute(5)->by('admin-emails:'.$request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by('admin-login:'.$request->ip())
            ->response(fn (Request $request, array $headers) => response()->view('admin.login', [
                'configured' => app(EnvironmentAdminProvider::class)->configured(),
                'throttled' => true,
            ], 429, $headers)));
    }
}
