@extends('mail.registrations.layout')
@use('App\Services\RegistrationPayment')
@section('mail-title', 'Your VISION X 2026 registration')
@section('preheader', $paymentUrl ? 'Your registration ID, payment instructions and next steps for your gate pass.' : 'Your VISION X 2026 registration has been received.')
@section('mail-eyebrow', 'REGISTRATION RECEIVED')
@section('mail-heading')A shared vision.<br><span style="color:#ffab6c">And now, you.</span>@endsection
@section('mail-subheading', 'Thank you for being part of the eye care conversation.')
@section('mail-content')
<p style="font-size:15px;line-height:1.8;color:#214866;margin-bottom:15px">Hello {{ $registration->full_name }},</p>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-bottom:25px">We&rsquo;ve received your registration for <strong style="color:#092d62">VISION X 2026</strong>. We look forward to bringing doctors, optometrists and opticians together for a day of learning, shared perspectives and meaningful connections.</p>
<table role="presentation" width="100%" style="background-color:#edf6fc;border:1px solid #d5e7f4;margin-bottom:27px"><tr><td style="padding:19px 20px"><p style="font-size:9px;letter-spacing:1.3px;color:#5c86a3;margin-bottom:9px">YOUR REGISTRATION ID</p><p class="mail-reference" style="font-size:24px;letter-spacing:3px;font-weight:bold;font-family:Consolas,monospace;color:#092d62;overflow-wrap:anywhere">{{ $registration->reference }}</p></td></tr></table>
@if ($paymentUrl)
    <table role="presentation" width="100%" style="margin-bottom:27px;background-color:#fff7ee">
        <tr><td style="padding:20px;border-left:3px solid #f87918">
            <h2 style="font-size:17px;color:#092d62;margin:0 0 9px">Registration fee &middot; Rs.{{ RegistrationPayment::FEE }}/-</h2>
            <p style="font-size:13px;line-height:1.7;color:#614d3b">{{ RegistrationPayment::SCAN_INSTRUCTIONS }} Enter <strong>Rs.{{ RegistrationPayment::FEE }}/-</strong> and use <strong>{{ $registration->reference }}</strong> as the payment note if prompted.</p>
            <img src="{{ $message->embed(public_path(RegistrationPayment::QR_IMAGE)) }}" alt="Devi Eye Hospital payment QR code" width="220" height="220" style="width:220px;max-width:100%;height:auto;margin:15px 0;background-color:#ffffff">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr><td bgcolor="#f87918" style="border-radius:6px">
                <a href="{{ $paymentUrl }}" style="display:inline-block;padding:13px 18px;font-size:13px;font-weight:bold;color:#082b59;text-decoration:none;border-radius:6px">Pay Rs.{{ RegistrationPayment::FEE }} with UPI &rarr;</a>
            </td></tr></table>
            <p style="font-size:12px;line-height:1.7;color:#614d3b;margin-top:9px">Tap the payment link on mobile to open a compatible UPI app with the fee and your registration ID prefilled. You can also scan the QR code from another device.</p>
            <p style="font-size:13px;line-height:1.7;color:#614d3b;margin-top:15px">
                After paying, please send your payment screenshot and registration ID <strong>{{ $registration->reference }}</strong>
                @if ($whatsAppUrl)
                    to <a href="{{ $whatsAppUrl }}" style="font-weight:bold">WhatsApp (+{{ $whatsAppNumber }})</a>.
                @else
                    to the organising team through the contact option on the <a href="{{ route('home') }}#faq">event website</a>.
                @endif
                The organising team will verify your payment and issue your gate pass.
            </p>
        </td></tr>
    </table>
@endif
<p style="font-size:10px;font-weight:bold;letter-spacing:1.4px;color:#315f80;margin-bottom:14px">YOUR DELEGATE DETAILS</p>
@include('mail.registrations.details', ['registration' => $registration])
<p style="font-size:13px;margin-top:24px"><a href="{{ route('program-schedule') }}" style="font-weight:bold">View the program schedule &rarr;</a></p>
<p style="font-size:12px;line-height:1.8;color:#6c8598;margin-top:25px">Warm regards,<br><strong style="color:#315775">The VISION X 2026 Organising Team</strong><br>Devi Eye Hospital</p>
@endsection
@section('mail-footnote', 'You received this email because a registration was submitted using this email address. If you did not register, you can contact the organising team through the website.')
