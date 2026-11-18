@extends('layouts.site')

@section('title', 'Registration Payment | VISION X 2026')
@section('head')
    <meta name="robots" content="noindex, nofollow">
    @if ($appLinks)
        <script src="{{ asset('js/payment.js') }}?v={{ filemtime(public_path('js/payment.js')) }}" defer></script>
    @endif
@endsection

@section('content')
    <section class="delegate-success" aria-labelledby="payment-title">
        <div class="container">
            <div class="delegate-success-card">
                <p class="eyebrow">VISION X 2026</p>
                <h1 id="payment-title">Registration payment</h1>
                <div class="delegate-receipt"><span>YOUR REGISTRATION ID</span><strong>{{ $reference }}</strong></div>
                @if ($appLinks)
                    <x-registration-payment :reference="$reference">
                        <a class="button" href="{{ $appLinks['upi'] }}" data-upi-payment>Open UPI app <x-icon name="arrow-up-right" /></a>
                        <a class="text-link" href="{{ $appLinks['googlePay'] }}" data-ios-payment>Google Pay on iPhone</a>
                    </x-registration-payment>
                @elseif ($paymentConfirmed)
                    <p class="delegate-success-lead">Your registration payment has been confirmed.</p>
                @else
                    <p class="delegate-success-lead">Please contact the organising team for help with this registration.</p>
                @endif
                <div class="delegate-success-actions">
                    <a class="text-link" href="{{ route('program-schedule') }}">View the program schedule <x-icon name="arrow-right" /></a>
                </div>
            </div>
        </div>
    </section>
@endsection
