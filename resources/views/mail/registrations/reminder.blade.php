@extends('mail.registrations.layout')
@section('mail-title', 'Your VISION X 2026 registration ID')
@section('preheader', 'Your registration ID, safely back in your inbox.')
@section('mail-eyebrow', 'YOUR REGISTRATION, RECONNECTED')
@section('mail-heading')A little reminder.<br><span style="color:#ffab6c">A shared vision.</span>@endsection
@section('mail-subheading', 'Keep your registration ID handy for event correspondence.')
@section('mail-content')
<p style="font-size:15px;color:#214866">Hello {{ $registration->full_name }},</p>
<p style="font-size:13px;line-height:1.9;color:#617d92">We received a request to recover the registration ID associated with this email address. Here is your existing VISION X 2026 registration ID.</p>
<table role="presentation" width="100%" style="background:#edf6fc;border:1px solid #d5e7f4;margin:25px 0"><tr><td style="padding:24px"><p style="font-size:10px;letter-spacing:1.4px;color:#5c86a3">YOUR REGISTRATION ID</p><p style="font-size:32px;font-weight:bold;letter-spacing:5px;color:#092d62">{{ $registration->reference }}</p></td></tr></table>
<p style="font-size:13px;line-height:1.9;color:#617d92">You do not need to register again. This reminder does not change your registration or payment status. Our organising team will email your delegate pass after confirming payment.</p>
<p style="font-size:12px;line-height:1.8;color:#617d92">Sunday, 6 December 2026 &middot; Sasthamcotta, Kerala<br>Exact venue to be announced.</p>
<p style="font-size:13px;line-height:1.8;color:#214866">Warm regards,<br><strong>The VISION X 2026 Organising Team</strong><br>Devi Eye Hospital</p>
@endsection
@section('mail-footnote', 'If you did not request this reminder, you can safely ignore it. Your registration details have not been changed.')
