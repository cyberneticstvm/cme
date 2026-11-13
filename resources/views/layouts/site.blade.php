<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'VISION X 2026 | Eye Care CME')</title>
    <meta name="description" content="@yield('description', 'Register for VISION X 2026, an eye care CME for doctors, optometrists and opticians, on 6 December 2026 in Shastamcotta, Kerala.')">
    <meta name="theme-color" content="#092d62">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'VISION X 2026 | Eye Care CME')">
    <meta property="og:image" content="{{ asset('images/visionx-2026-poster.jpeg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/registration.css') }}?v={{ filemtime(public_path('css/registration.css')) }}">
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
    <script src="{{ asset('js/registration.js') }}?v={{ filemtime(public_path('js/registration.js')) }}" defer></script>
    @yield('head')
</head>
<body class="delegate-page @yield('body-class')">
    <a class="skip-link" href="#main">Skip to content</a>
    <x-site-header />
    <main id="main">@yield('content')</main>
    @hasSection('footer')
        @yield('footer')
    @else
        <x-site-footer />
    @endif
    <x-whatsapp-button />
</body>
</html>
