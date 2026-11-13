@extends('layouts.site')
@section('title', 'Delegate Registration | VISION X 2026 CME')
@section('body-class', 'delegate-compact')
@section('head')
<link rel="stylesheet" href="{{ asset('css/enrolment.css') }}?v={{ filemtime(public_path('css/enrolment.css')) }}">
@endsection
@section('content')
<section class="container enrolment-shell" aria-labelledby="form-heading">
    <x-enrolment-story />
    <div class="enrolment-form-panel">
        <div class="enrolment-form-heading"><div><h2 id="form-heading">Your delegate details</h2><p>Choose your profession. Fields marked * are required.</p></div><span class="enrolment-open"><i></i>REGISTRATION OPEN</span></div>
        @if($errors->any())
            <div class="delegate-error-summary" role="alert" tabindex="-1" id="form-errors"><strong>Please check your details</strong><ul>@foreach($errors->getMessages() as $field => $messages)<li><a href="#{{ $field === 'profession' ? 'profession-doctor' : ($field === 'registration' ? 'form-heading' : $field) }}">{{ $messages[0] }}</a></li>@endforeach</ul></div>
        @endif
        <form action="{{ route('registrations.store') }}" method="POST" class="enrolment-form" data-registration-form data-email-check-url="{{ route('registrations.check-email') }}">
            @csrf
            <fieldset class="delegate-professions" @if($errors->has('profession')) aria-describedby="profession-error" @endif>
                <legend class="enrolment-sr-only">I am a (required)</legend>
                <div class="delegate-profession-grid">
                    @foreach($professions as $profession)
                        <label class="delegate-profession"><input type="radio" name="profession" id="profession-{{ $profession->value }}" value="{{ $profession->value }}" @checked(old('profession') === $profession->value) required><span class="delegate-profession-content"><x-profession-icon :profession="$profession->value" /><strong>{{ $profession->label() }}</strong><span class="delegate-radio-dot" aria-hidden="true"></span></span></label>
                    @endforeach
                </div>
                @error('profession')<p id="profession-error" class="delegate-error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="delegate-fields">
                <x-form-field name="full_name" label="Full name" :required="true" maxlength="120" autocomplete="name" placeholder="Your full name" />
                <x-form-field name="organisation" label="Organisation" maxlength="180" autocomplete="organization" placeholder="Hospital / practice / optical" />
                <div class="enrolment-email-field"><x-form-field name="email" label="Email address" type="email" :required="true" maxlength="254" autocomplete="email" placeholder="you@example.com" /><p class="enrolment-email-check" id="email-availability" aria-live="polite" data-email-status></p><a class="enrolment-recover-link" href="{{ route('registrations.recover') }}">Already registered? Forgot registration ID?</a></div>
                <x-form-field name="phone" label="Mobile number" type="tel" :required="true" maxlength="24" autocomplete="tel" placeholder="+91 98765 43210" />
                <x-form-field name="city" label="City / town" :required="true" maxlength="100" autocomplete="address-level2" placeholder="Your city or town" />
                <x-form-field name="state" label="State / region" :required="true" maxlength="100" autocomplete="address-level1" placeholder="Your state or region" />
            </div>
            <details class="enrolment-optional" @if(old('message') || $errors->has('message')) open @endif>
                <summary><x-icon name="plus" /><span>Add a note for the organising team</span><small>Optional</small></summary>
                <div class="delegate-field"><label class="enrolment-sr-only" for="message">Note for the organising team</label><textarea id="message" name="message" rows="2" maxlength="1500" placeholder="Questions, accessibility needs or a note for our team" @if($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif>{{ is_string(old('message')) ? old('message') : '' }}</textarea>@error('message')<p id="message-error" class="delegate-error">{{ $message }}</p>@enderror</div>
            </details>
            <div class="delegate-honeypot" aria-hidden="true"><label for="website">Leave this field empty</label><input type="text" name="website" id="website" tabindex="-1" autocomplete="off"></div>
            <div class="delegate-consent"><label for="consent"><input type="checkbox" name="consent" id="consent" value="1" @checked(old('consent')) required @if($errors->has('consent')) aria-invalid="true" aria-describedby="consent-error" @endif><span>I agree to Devi Eye Hospital using my details for VISION X 2026 registration and event updates. <span aria-hidden="true">*</span></span></label>@error('consent')<p id="consent-error" class="delegate-error">{{ $message }}</p>@enderror</div>
            <div class="delegate-submit-row"><button class="button delegate-submit" type="submit"><span data-submit-label>Submit registration</span><x-icon name="arrow-up-right" /><span class="delegate-spinner" aria-hidden="true"></span></button></div>
            <p class="enrolment-submit-info"><x-icon name="mail" /><span>Your registration ID and acknowledgement will be emailed to you. <small>One registration per email. Your pass follows payment confirmation.</small></span></p>
        </form>
    </div>
</section>
@endsection
@section('footer')
<footer class="enrolment-footer"><div class="container"><span>&copy; {{ now()->year }} VISION X &middot; Devi Eye Hospital</span><nav aria-label="Registration support"><a href="{{ route('home') }}#faq">Have a question? <x-icon name="chat" /></a><a href="{{ asset('visionx-2026.ics') }}" download>Save the date <x-icon name="calendar" /></a></nav></div></footer>
@endsection
