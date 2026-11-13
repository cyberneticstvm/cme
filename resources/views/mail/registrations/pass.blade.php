@extends('mail.registrations.layout')
@section('mail-title', 'Your VISION X 2026 delegate pass')
@section('preheader', 'Payment confirmed. Your delegate pass is attached as a PDF.')
@section('mail-eyebrow', 'PAYMENT CONFIRMED. PASS ATTACHED.')
@section('mail-heading')Your next perspective.<br><span style="color:#ffab6c">Your place is ready.</span>@endsection
@section('mail-subheading', 'Your VISION X 2026 delegate pass is here.')
@section('mail-content')
<p style="font-size:15px;color:#214866">Hello {{ $registration->full_name }},</p>
<p style="font-size:13px;line-height:1.9;color:#617d92">Our organising team has confirmed your payment. Your personalised <strong style="color:#092d62">VISION X 2026 delegate pass</strong> is attached to this email as a PDF.</p>
<table role="presentation" width="100%" style="background:#edf6fc;border:1px solid #d5e7f4;margin:25px 0"><tr><td style="padding:22px"><p style="font-size:10px;letter-spacing:1.5px;color:#5c86a3">YOUR REGISTRATION ID</p><p style="font-size:30px;font-weight:bold;letter-spacing:4px;color:#092d62">{{ $registration->reference }}</p><p style="font-size:11px;color:#297457;margin-top:12px">Payment confirmed &middot; PDF pass attached</p></td></tr></table>
<p style="font-size:13px;line-height:1.9;color:#617d92">Save the PDF on your phone or bring a printed copy. The pass is personalised for you. Please keep your registration ID handy when contacting us. Our team will scan the QR code at entry. Each registration permits one admission; copies of the pass share the same single-use code.</p>
<table role="presentation" width="100%" style="background:#fff7ee;margin:20px 0"><tr><td style="padding:18px 20px;border-left:3px solid #f87918"><p style="font-size:13px;color:#092d62;font-weight:bold">Sunday, 6 December 2026</p><p style="font-size:12px;line-height:1.8;color:#8b735d;margin-top:6px">Shastamcotta, Kerala<br>Exact venue and timings will be shared separately.</p></td></tr></table>
<p style="font-size:13px;line-height:1.9;color:#617d92">We look forward to a day of learning and connection with our eye care community.</p><p style="font-size:13px;line-height:1.8;color:#214866">Warm regards,<br><strong>The VISION X 2026 Organising Team</strong><br>Devi Eye Hospital</p>
@endsection
@section('mail-footnote', 'This pass was sent only to the email address on your registration. Pass validity follows the current payment and registration status in our records.')
