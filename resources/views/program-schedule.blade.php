@extends('layouts.site')
@use('App\Services\RegistrationPayment')

@section('title', 'Program Schedule | VISION X 2026')
@section('description', 'Explore the VISION X 2026 Optometry CME schedule, speakers and hands-on workshops on 6 December at Gemini Heights Auditorium, Sasthamcotta.')
@section('head')
    <link rel="stylesheet" href="{{ asset('css/schedule.css') }}?v={{ filemtime(public_path('css/schedule.css')) }}">
@endsection

@section('content')
    @php
        $sessions = [
            ['8:45–9:00 AM', 'Inauguration', null, null],
            ['9:00–9:45 AM', 'Prisms in Spectacle Correction', 'Kishor Kumar K R', 'BSc Optometry'],
            ['9:45–10:00 AM', 'Emmetropization: The Journey Towards Normal Vision', 'Dr Anju Mery Thomas', 'MBBS, DO, DNB'],
            ['10:00–10:15 AM', 'Retinopathy of Prematurity', 'Dr Roshni R S', 'MBBS, DNB'],
            ['10:15–10:45 AM', 'Progressive Lenses – The Art of Seamless Vision', 'Abdul Majeed P', 'BSc Optometry'],
            ['10:45–11:15 AM', 'Refreshment Break', null, null],
            ['11:15 AM–12:15 PM', 'The Role of Age in Management for Amblyopia and Strabismus', 'Sreelatha O K', 'M Optom, FCO (LVPEI, Hyderabad)'],
            ['12:15–1:00 PM', 'Low Vision Rehabilitation & Practices – Optometrists', 'Manjulata Devi Sharma', 'MSc Optometry (FLVPEI)'],
            ['1:00–2:00 PM', 'Lunch', null, null],
        ];
        $workshops = ['Low Vision Aid', 'OCT', 'Prism', 'A-Scan', 'B-Scan'];
    @endphp

    <section class="schedule-hero" aria-labelledby="schedule-title">
        <div class="container schedule-hero-grid">
            <div class="schedule-intro">
                <p class="eyebrow">VISION X 2026 &middot; OPTOMETRY CME</p>
                <h1 id="schedule-title">Program <span>Schedule</span></h1>
                <p>Where Vision Meets Innovation</p>
                <div class="schedule-actions">
                    <a class="button" href="#sessions">View sessions <x-icon name="arrow-right" /></a>
                    <a class="text-link" href="#workshops">Hands-on workshops <x-icon name="arrow-right" /></a>
                </div>
            </div>
            <div class="schedule-event">
                <img src="{{ asset('images/optometry-examination.jpg') }}" alt="" width="1536" height="1024" fetchpriority="high">
                <dl>
                    <div>
                        <dt><x-icon name="calendar" /> Date &amp; time</dt>
                        <dd><time datetime="2026-12-06">Sunday, 6 December 2026</time><span>8:45 AM–4:30 PM IST</span></dd>
                    </div>
                    <div>
                        <dt><x-icon name="pin" /> Venue</dt>
                        <dd>Gemini Heights Auditorium<span>Sasthamcotta, Kerala</span></dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="container schedule-agenda" id="sessions" aria-labelledby="sessions-title">
        <div class="schedule-section-heading">
            <h2 id="sessions-title">Sessions &amp; speakers</h2>
            <p>All times are in IST.</p>
        </div>
        <div class="schedule-list">
            <div class="schedule-row schedule-columns" aria-hidden="true">
                <span>Time</span><span>Topic</span><span>Speaker</span>
            </div>
            <ol role="list">
                @foreach ($sessions as [$time, $topic, $speaker, $credentials])
                    <li @class(['schedule-row', 'schedule-milestone' => $speaker === null])>
                        <p class="schedule-time">{{ $time }}</p>
                        <h3>{{ $topic }}</h3>
                        @if ($speaker !== null)
                            <p class="schedule-speaker">
                                <strong>{{ $speaker }}</strong>
                                <span>{{ $credentials }}</span>
                            </p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="container schedule-workshops" id="workshops" aria-labelledby="workshops-title">
        <div class="schedule-workshop-panel">
            <div>
                <p class="eyebrow">AFTER LUNCH</p>
                <h2 id="workshops-title">Hands-on workshops</h2>
            </div>
            <ul role="list">
                @foreach ($workshops as $workshop)
                    <li><x-icon name="check" /> {{ $workshop }}</li>
                @endforeach
            </ul>
        </div>
        <div class="schedule-registration">
            <p>Registration fee <strong>Rs.{{ RegistrationPayment::FEE }}/-</strong></p>
            <a class="button" href="{{ route('registrations.create') }}">Register for VISION X <x-icon name="arrow-up-right" /></a>
        </div>
    </section>
@endsection
