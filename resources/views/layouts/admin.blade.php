<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Administration') | VISION X 2026</title><meta name="robots" content="noindex, nofollow"><meta name="theme-color" content="#092d62">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
</head>
<body class="admin-body @hasSection('guest') admin-guest @endif">
<a class="admin-skip" href="#admin-main">Skip to content</a>
@hasSection('guest')
    @yield('content')
@else
    <aside class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-brand"><img src="{{ asset('images/visionx-2026-logo.png') }}" alt="VISION X 2026" width="1922" height="818"><span>ORGANISER WORKSPACE</span></a>
        <p class="admin-nav-label">EVENT MANAGEMENT</p>
        <nav aria-label="Admin navigation"><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.check-in.*') ? '' : 'admin-nav-current' }}" aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}"><x-icon name="users" />Registrations<span class="admin-nav-dot"></span></a><a href="{{ route('admin.check-in.index') }}" @class(['admin-nav-current' => request()->routeIs('admin.check-in.*')]) @if(request()->routeIs('admin.check-in.*')) aria-current="page" @endif><x-icon name="qr" />Event check-in</a><a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="eye" />View event website<x-icon name="arrow-up-right" /></a></nav>
        <div class="admin-sidebar-event"><span>THE EYE CARE CME</span><strong>A shared vision.<br>A brighter tomorrow.</strong><p><x-icon name="calendar" />06 December 2026</p><p><x-icon name="pin" />Sasthamcotta, Kerala</p><div>Doctors &middot; Optometrists &middot; Opticians</div></div>
        <div class="admin-sidebar-account"><span class="admin-account-avatar">{{ mb_strtoupper(mb_substr(auth('admin')->user()->username, 0, 1)) }}</span><div><strong>{{ auth('admin')->user()->username }}</strong><span>Administrator</span></div><form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit" aria-label="Sign out" title="Sign out"><x-icon name="logout" /></button></form></div>
    </aside>
    <div class="admin-workspace">
        <header class="admin-topbar"><div><span class="admin-topbar-eyebrow">VISION X 2026</span><span class="admin-topbar-title">Registration workspace</span></div><div class="admin-topbar-right"><span class="admin-secure"><x-icon name="lock" />Private admin area</span><details class="admin-mobile-menu"><summary>Menu <span aria-hidden="true">+</span></summary><nav aria-label="Mobile admin navigation"><a href="{{ route('admin.dashboard') }}">Registrations</a><a href="{{ route('admin.check-in.index') }}">Event check-in</a><a href="{{ route('home') }}" target="_blank" rel="noopener">View event website <x-icon name="arrow-up-right" /></a><form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit">Sign out <x-icon name="logout" /></button></form></nav></details></div></header>
        <main id="admin-main" class="admin-main">
            @if(session('status'))<div class="admin-flash" role="status"><x-icon name="check" />{{ session('status') }}</div>@endif
            @if(session('warning'))<div class="admin-flash admin-flash-warning" role="status"><x-icon name="mail" />{{ session('warning') }}</div>@endif
            @yield('content')
        </main>
        <footer class="admin-footer"><span>&copy; {{ now()->year }} VISION X &middot; Devi Eye Hospital</span><span>Better knowledge. Better eye care.</span></footer>
    </div>
@endif
@stack('scripts')
</body></html>
