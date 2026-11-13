@extends('mail.registrations.layout')
@section('mail-title', 'Your VISION X 2026 participation certificate')
@section('preheader', 'Thank you for participating. Your personalised certificate is attached as a PDF.')
@section('mail-eyebrow', 'YOUR PARTICIPATION, RECOGNISED')
@section('mail-heading')A shared perspective.<br><span style="color:#ffab6c">A lasting connection.</span>@endsection
@section('mail-subheading', 'Your VISION X 2026 participation certificate is here.')
@section('mail-content')
<p style="font-size:15px;line-height:1.8;color:#214866">Hello {{ $registration->full_name }},</p>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-top:15px">Thank you for participating in <strong style="color:#092d62">VISION X 2026</strong>, our Continuing Medical Education programme for the eye care community. We appreciate your presence and the perspective you brought to the event.</p>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-top:15px">Your personalised <strong style="color:#092d62">Certificate of Participation</strong> is attached to this email as a PDF. Please save a copy for your records.</p>
<table role="presentation" width="100%" style="background:#edf6fc;border:1px solid #d5e7f4;margin:25px 0"><tr><td style="padding:22px"><p style="font-size:9px;letter-spacing:1.5px;color:#5c86a3">CERTIFICATE NUMBER</p><p style="font-size:22px;font-weight:bold;letter-spacing:1px;color:#092d62;margin-top:9px;overflow-wrap:anywhere">{{ $registration->certificate_number }}</p><p style="font-size:12px;color:#52748e;margin-top:12px">Registration ID: <strong>{{ $registration->reference }}</strong></p><p style="font-size:11px;color:#2e7b59;margin-top:10px">Participation confirmed from your event check-in.</p></td></tr></table>
<p style="font-size:13px;line-height:1.9;color:#617d92">We look forward to more opportunities to learn, connect and move eye care forward together.</p><p style="font-size:13px;line-height:1.8;color:#214866;margin-top:24px">Warm regards,<br><strong>The VISION X 2026 Organising Team</strong><br>Devi Eye Hospital</p>
@endsection
@section('mail-event-note', 'Thank you for being part of our eye care community.')
@section('mail-footnote', 'This certificate was sent to the email address on your checked-in registration. If any details need correction, please contact the organising team through the website.')
