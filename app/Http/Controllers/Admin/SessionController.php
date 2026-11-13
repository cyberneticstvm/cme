<?php

namespace App\Http\Controllers\Admin;

use App\Auth\EnvironmentAdminProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check() && $request->session()->has('admin_last_activity')) {
            return to_route('admin.dashboard');
        }

        return view('admin.login', ['configured' => app(EnvironmentAdminProvider::class)->configured()]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('admin')->attempt($request->safe()->only(['username', 'password']))) {
            throw ValidationException::withMessages(['username' => 'The username or password is incorrect.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_last_activity', now()->timestamp);

        return to_route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login')->with('status', 'You have been signed out securely.');
    }
}
