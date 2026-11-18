@props(['reference' => null])
@use('App\Services\RegistrationPayment')

@pushOnce('styles')
    <link rel="stylesheet" href="{{ asset('css/payment.css') }}?v={{ filemtime(public_path('css/payment.css')) }}">
@endPushOnce

@php
    $whatsAppNumber = RegistrationPayment::whatsAppNumber();
    $whatsAppUrl = $reference === null
        ? ($whatsAppNumber ? 'https://wa.me/'.$whatsAppNumber : null)
        : RegistrationPayment::whatsAppUrl($reference);
    $paymentNote = $reference ?? 'your five-digit registration ID';
    $proofReference = $reference === null ? 'five-digit registration ID' : 'registration ID '.$reference;
@endphp

<section {{ $attributes->class(['registration-payment', 'registration-payment-public' => $reference === null]) }} aria-labelledby="registration-payment-heading">
    <div class="registration-payment-copy">
        <p class="eyebrow">REGISTRATION FEE</p>
        <h2 id="registration-payment-heading">Rs.{{ RegistrationPayment::FEE }}/-</h2>
        @if ($reference === null)
            <p><strong>Register first to receive your five-digit registration ID.</strong></p>
        @endif
        <p>{{ RegistrationPayment::SCAN_INSTRUCTIONS }} Enter <strong>Rs.{{ RegistrationPayment::FEE }}/-</strong> and use <strong>{{ $paymentNote }}</strong> as the payment note if prompted.</p>
        @if ($slot->isNotEmpty())
            <div class="registration-payment-apps">{{ $slot }}</div>
        @endif
        @if ($whatsAppUrl)
            <p>After paying, send your payment screenshot and <strong>{{ $proofReference }}</strong> to <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer">WhatsApp (+{{ $whatsAppNumber }})</a>. The organising team will verify your payment and issue your gate pass.</p>
        @else
            <p>After paying, keep your payment screenshot and <strong>{{ $proofReference }}</strong> and contact the organising team to receive your gate pass.</p>
        @endif
    </div>
    <div class="registration-payment-qr">
        <img src="{{ asset(RegistrationPayment::QR_IMAGE) }}" alt="Devi Eye Hospital payment QR code" width="430" height="430" loading="lazy">
        <a href="{{ asset(RegistrationPayment::QR_IMAGE) }}" download>Download QR code <x-icon name="download" /></a>
    </div>
</section>
