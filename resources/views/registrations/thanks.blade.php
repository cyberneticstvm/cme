@extends('layouts.site')
@use('App\Services\RegistrationPayment')
@section('title', 'Registration Received | VISION X 2026')
@section('head')<meta name="robots" content="noindex, nofollow"><meta http-equiv="Cache-Control" content="no-store">@endsection
@section('content')
<section class="delegate-success"><div class="container"><div class="delegate-success-card">
    <span class="delegate-success-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="1.5"/><path d="m15 24 6 6 13-14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
    <p class="eyebrow">A SHARED VISION. A NEW CONNECTION.</p><h1>Your next perspective<br><em>starts here.</em></h1><p class="delegate-success-lead">Thank you, {{ $receipt['full_name'] }}.<br>Your VISION X 2026 registration has been received.</p>
    <div class="delegate-receipt"><span>YOUR REGISTRATION ID</span><strong>{{ $receipt['reference'] }}</strong><p>Please keep this ID for future communication.</p></div>
    <div class="delegate-success-message"><x-icon name="chat" /><p>
        @if($receipt['email_sent'])
            We&rsquo;ve sent an acknowledgement to <strong>{{ $receipt['email'] }}</strong>. Please check your inbox and spam folder.
        @else
            Your registration is safely saved. Your email acknowledgement is pending; please keep the ID above and contact us on WhatsApp if you need assistance.
        @endif
    </p></div>
    @if ($paymentDue)
        <x-registration-payment :reference="$receipt['reference']">
            <a class="button" href="{{ RegistrationPayment::url($receipt['reference']) }}">Pay with UPI <x-icon name="arrow-up-right" /></a>
        </x-registration-payment>
    @endif
    <div class="delegate-success-event"><span><x-icon name="calendar" />Sunday, 6 December 2026</span><span><x-icon name="pin" />Gemini Heights Auditorium, Sasthamcotta</span></div>
    <div class="delegate-success-actions"><a class="button" href="{{ asset('visionx-2026.ics') }}" download>Save the date <x-icon name="calendar" /></a><a class="text-link" href="{{ route('program-schedule') }}">View the program schedule <x-icon name="arrow-right" /></a></div>
</div></div></section>
@endsection
