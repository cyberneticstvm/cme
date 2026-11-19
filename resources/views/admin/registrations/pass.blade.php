@extends('layouts.admin')
@section('title', 'Delegate Pass')
@section('content')
<a class="admin-back-link" href="{{ route('admin.dashboard') }}"><x-icon name="arrow-left" />Back to registrations</a>
<div class="admin-page-heading admin-detail-heading"><div><p class="admin-eyebrow">DELEGATE PASS</p><h1>{{ $registration->full_name }}</h1><p class="admin-reference">Registration ID {{ $registration->reference }}</p></div></div>
<x-admin-registration-tabs :registration="$registration" />
@if($errors->any())<div class="admin-form-errors" role="alert" tabindex="-1" data-error-summary><strong>Please check these details.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($registration->checked_in_at)<div class="admin-attendance-banner"><x-icon name="check" /><div><strong>Attendance recorded &middot; {{ $registration->checked_in_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</strong><p>This registration has already checked in. Replacing or resending its pass will not permit another entry.</p></div></div>@endif
<div class="admin-pass-column">
<section class="admin-pass-card" aria-labelledby="pass-heading"><div class="admin-pass-card-top"><p class="admin-eyebrow">THE NEXT STEP</p><h2 id="pass-heading">Their place.<br>Made official.</h2><p>One personalised PDF.<br>Delivered to their registered inbox.</p><x-icon name="eye" /></div>
<div class="admin-pass-preview"><div class="admin-pass-preview-brand"><img src="{{ asset('images/visionx-2026-logo.png') }}" alt="VISION X 2026" width="150" height="64"><span>PASS PREVIEW</span></div><strong>{{ $registration->full_name }}</strong><p>{{ $registration->profession->label() }}</p><div class="admin-pass-preview-id"><span>REGISTRATION ID</span><strong @class(['is-legacy' => strlen($registration->reference) > 5])>{{ $registration->reference }}</strong></div><small>06 DECEMBER 2026 &middot; SASTHAMCOTTA</small></div>
<div class="admin-pass-actions">
<div class="admin-pass-recipient"><x-icon name="mail" /><div><span>DELIVER ONLY TO</span><strong>{{ $registration->email }}</strong></div></div>
@if($registration->pass_id && $registration->pass_qr_version !== App\Services\PassQrCode::VERSION)<p class="admin-pass-upgrade">This PDF predates QR check-in. Generate and email an updated QR pass before the event.</p>@endif
@if($registration->canIssuePass())
@if($pdfAvailable)<div class="admin-pass-ready"><x-icon name="check" /><span>Pass delivery enabled.</span></div>@else<div class="admin-pass-locked"><x-icon name="lock" /><strong>Pass service awaiting setup</strong><p>The system administrator needs to finish PDF service setup before issuing passes.</p></div>@endif
<form method="POST" action="{{ route('admin.registrations.pass.send', $registration) }}" data-admin-submit>@csrf<button type="submit" class="admin-button admin-button-primary" @disabled(!$pdfAvailable)><x-icon name="mail" />{{ $registration->pass_id && $registration->pass_qr_version !== App\Services\PassQrCode::VERSION ? 'Generate & email updated QR pass' : ($registration->pass_id ? ($registration->pass_sent_at ? 'Resend PDF pass' : 'Send PDF pass') : 'Generate & email PDF pass') }}</button></form>
@if($pdfAvailable && $registration->pass_id && $registration->pass_issued_at && $registration->pass_qr_version === App\Services\PassQrCode::VERSION)<a class="admin-button admin-button-navy" href="{{ route('admin.registrations.pass.download', $registration) }}"><x-icon name="download" />Download PDF pass</a>@endif
@else
<div class="admin-pass-locked"><x-icon name="lock" /><strong>Registration cancelled</strong><p>Delegate passes are available for active registrations.</p></div>
@endif
@if($registration->pass_sent_at)<p class="admin-pass-delivery">Last handed to the mail service<br><strong>{{ $registration->pass_sent_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</strong><br>{{ $registration->pass_sent_to }}</p>@elseif($registration->pass_id)<p class="admin-pass-delivery">PDF generated &middot; Email delivery pending</p>@endif
</div></section>
<p class="admin-pass-note"><x-icon name="lock" />Changing delegate details revokes an existing pass. Save changes, then generate and email a fresh copy. Previously emailed attachments cannot be recalled.</p>
</div>
@endsection
