<?php

namespace App\Http\Middleware;

use App\Auth\EnvironmentAdminProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');
        $lastActivity = $request->session()->get('admin_last_activity');
        $timeout = max(1, (int) config('admin.session_timeout')) * 60;
        $authenticated = $guard->check() && hash_equals(app(EnvironmentAdminProvider::class)->identifier(), (string) $guard->id());

        if (! $authenticated || ! is_int($lastActivity) || now()->timestamp - $lastActivity >= $timeout) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('admin.login')->with('status', $authenticated ? 'Your session expired. Please sign in again.' : 'Please sign in to continue.');
        }

        $request->session()->put('admin_last_activity', now()->timestamp);

        return $next($request);
    }
}
