@extends('layouts.site')
@section('title', 'Recover Registration ID | VISION X 2026')
@section('body-class', 'delegate-compact delegate-recovery')
@section('head')
<meta name="robots" content="noindex,follow">
<link rel="stylesheet" href="{{ asset('css/enrolment.css') }}?v={{ filemtime(public_path('css/enrolment.css')) }}">
@endsection
@section('content')
<section class="container enrolment-shell" aria-labelledby="recovery-heading">
    <x-enrolment-story />
    <div class="enrolment-form-panel enrolment-recovery-panel">
        <a class="enrolment-recovery-back" href="{{ route('registrations.create') }}"><x-icon name="arrow-left" />Back to registration</a>
        <div class="enrolment-recovery-icon"><x-icon name="mail" /></div>
        <p class="enrolment-recovery-eyebrow">YOUR PLACE IS STILL YOURS</p>
        <h2 id="recovery-heading">Forgot your<br><span>registration ID?</span></h2>
        <p class="enrolment-recovery-intro">A little reminder, straight to your inbox. Enter the email address you used to register for VISION X 2026.</p>
        @if($recoveryThrottled ?? false)<div class="delegate-error-summary" role="alert">Please wait before requesting another reminder. You can request one email per address every 10 minutes.</div>@endif
        @if(session('recovery_status'))<div class="enrolment-recovery-success" role="status"><x-icon name="check" /><div><strong>Check your inbox</strong><p>{{ session('recovery_status') }}</p></div></div>@endif
        @if($errors->any())<div class="delegate-error-summary" role="alert" tabindex="-1" id="form-errors">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('registrations.recover.send') }}" class="enrolment-recovery-form" data-recovery-form>@csrf
            <x-form-field name="email" label="Registered email address" type="email" :required="true" maxlength="254" autocomplete="email" placeholder="you@example.com" />
            <button type="submit" class="button delegate-submit"><span data-submit-label>Email my registration ID</span><x-icon name="arrow-up-right" /></button>
        </form>
        <div class="enrolment-recovery-note"><x-icon name="lock" /><p>We only send to an email address already registered with us. You do not need to submit another application.</p></div>
    </div>
</section>
@endsection
@section('footer')
<footer class="enrolment-footer"><div class="container"><span>&copy; {{ now()->year }} VISION X &middot; Devi Eye Hospital</span><nav aria-label="Recovery support"><a href="{{ route('home') }}#faq">Need help? <x-icon name="chat" /></a><a href="{{ route('registrations.create') }}">New registration <x-icon name="arrow-right" /></a></nav></div></footer>
@endsection
