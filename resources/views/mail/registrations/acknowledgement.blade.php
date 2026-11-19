@extends('mail.registrations.layout')
@section('mail-title', 'Your VISION X 2026 registration')
@section('preheader', 'Your registration ID and what to expect next.')
@section('mail-eyebrow', 'REGISTRATION RECEIVED')
@section('mail-heading')A shared vision.<br><span style="color:#ffab6c">And now, you.</span>@endsection
@section('mail-subheading', 'Thank you for being part of the eye care conversation.')
@section('mail-content')
<p style="font-size:15px;line-height:1.8;color:#214866;margin-bottom:15px">Hello {{ $registration->full_name }},</p>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-bottom:25px">We&rsquo;ve received your registration for <strong style="color:#092d62">VISION X 2026</strong>. We look forward to bringing doctors, optometrists and opticians together for a day of learning, shared perspectives and meaningful connections.</p>
<table role="presentation" width="100%" style="background-color:#edf6fc;border:1px solid #d5e7f4;margin-bottom:27px"><tr><td style="padding:19px 20px"><p style="font-size:9px;letter-spacing:1.3px;color:#5c86a3;margin-bottom:9px">YOUR REGISTRATION ID</p><p class="mail-reference" style="font-size:24px;letter-spacing:3px;font-weight:bold;font-family:Consolas,monospace;color:#092d62;overflow-wrap:anywhere">{{ $registration->reference }}</p></td></tr></table>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-bottom:25px">Registration is free.@if ($registration->canIssuePass()) The organising team will email your delegate pass separately.@endif</p>
<p style="font-size:10px;font-weight:bold;letter-spacing:1.4px;color:#315f80;margin-bottom:14px">YOUR DELEGATE DETAILS</p>
@include('mail.registrations.details', ['registration' => $registration])
<p style="font-size:13px;margin-top:24px"><a href="{{ route('program-schedule') }}" style="font-weight:bold">View the program schedule &rarr;</a></p>
<p style="font-size:12px;line-height:1.8;color:#6c8598;margin-top:25px">Warm regards,<br><strong style="color:#315775">The VISION X 2026 Organising Team</strong><br>Devi Eye Hospital</p>
@endsection
@section('mail-footnote', 'You received this email because a registration was submitted using this email address. If you did not register, you can contact the organising team through the website.')
