@extends('mail.registrations.layout')
@section('mail-title', 'New VISION X 2026 delegate registration')
@section('preheader', 'A new delegate has registered for VISION X 2026. Their details are ready for your review.')
@section('mail-eyebrow', 'ORGANISER NOTIFICATION')
@section('mail-heading')A new connection.<br><span style="color:#ffab6c">A growing community.</span>@endsection
@section('mail-subheading', 'A new delegate registration has been successfully saved.')
@section('mail-content')
<p style="font-size:14px;line-height:1.8;color:#214866;margin-bottom:15px">Hello Organising Team,</p>
<p style="font-size:13px;line-height:1.9;color:#617d92;margin-bottom:25px"><strong style="color:#092d62">{{ $registration->full_name }}</strong> has registered for VISION X 2026 in the <strong style="color:#092d62">{{ $registration->profession->label() }}</strong> category. The details below have been saved in the registration database.</p>
<table role="presentation" width="100%" style="background-color:#edf6fc;border:1px solid #d5e7f4;margin-bottom:27px"><tr><td style="padding:19px 20px"><p style="font-size:9px;letter-spacing:1.3px;color:#5c86a3;margin-bottom:9px">REGISTRATION ID</p><p class="mail-reference" style="font-size:24px;letter-spacing:3px;font-weight:bold;font-family:Consolas,monospace;color:#092d62;overflow-wrap:anywhere">{{ $registration->reference }}</p><p style="font-size:10px;color:#6d8a9e;margin-top:9px">Received {{ $registration->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p></td></tr></table>
@include('mail.registrations.details', ['registration' => $registration])
@if($registration->message)<div style="margin-top:24px;padding:19px 20px;background-color:#f4f8fb;border-left:3px solid #8cbad7"><p style="font-size:10px;font-weight:bold;letter-spacing:1px;color:#446e8b;margin-bottom:9px">NOTE FROM THE DELEGATE</p><p style="font-size:12px;line-height:1.9;color:#617d92;white-space:pre-line">{{ $registration->message }}</p></div>@endif
<p style="font-size:11px;line-height:1.8;color:#728b9d;margin-top:23px">Event communication consent recorded at {{ $registration->consented_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:25px"><tr><td bgcolor="#092d62" style="border-radius:6px"><a href="mailto:{{ $registration->email }}" style="display:inline-block;padding:15px 23px;font-size:12px;font-weight:bold;color:#ffffff;text-decoration:none;border:1px solid #092d62;border-radius:6px">Contact the delegate &rarr;</a></td></tr></table>
<p style="font-size:11px;line-height:1.8;color:#728b9d;margin-top:18px">You can also reply directly to this email to contact the delegate.</p>
@endsection
@section('mail-footnote', 'Internal registration notification for the VISION X 2026 organising team. Please handle delegate contact details with care.')
