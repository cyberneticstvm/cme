<footer class="site-footer">
    <div class="footer-glow" aria-hidden="true"></div>
    <div class="container">
        <div class="footer-invitation">
            <div><p class="eyebrow">VISION X 2026 &middot; THE EYE CARE CME</p><h2>Let&rsquo;s see what&rsquo;s next.<br><span>Together.</span></h2></div>
            <a href="{{ route('registrations.create') }}" class="footer-invitation-link"><span>YOUR NEXT PERSPECTIVE<br>STARTS HERE</span><span class="footer-arrow" aria-hidden="true"><x-icon name="arrow-up-right" /></span></a>
        </div>
        <div class="footer-event-strip">
            <div class="footer-date"><strong>06</strong><span>DECEMBER 2026<br><b>SUNDAY</b></span></div>
            <div class="footer-location"><span aria-hidden="true"><x-icon name="pin" /></span><div><strong>Sasthamcotta, Kerala</strong><span>A day to connect, learn and look ahead.</span></div></div>
            <a href="{{ asset('visionx-2026.ics') }}" download>Add to your calendar <span aria-hidden="true"><x-icon name="arrow-up-right" /></span></a>
        </div>
        <div class="footer-columns">
            <div class="footer-logo-only">
                <a href="{{ request()->routeIs('home') ? '#home' : route('home').'#home' }}" aria-label="VISION X 2026 - back to home"><img class="visionx-logo footer-event-logo" src="{{ asset('images/visionx-2026-logo.png') }}" alt="VISION X 2026" width="1922" height="818" loading="lazy"></a>
            </div>
            <nav class="footer-nav" aria-label="Explore the CME"><h3>Explore the CME</h3><a href="{{ request()->routeIs('home') ? '#about' : route('home').'#about' }}">Our shared vision</a><a href="{{ request()->routeIs('home') ? '#community' : route('home').'#community' }}">Who should attend</a><a href="{{ route('program-schedule') }}">Program Schedule</a><a href="{{ route('program-schedule') }}#sessions">Speakers &amp; faculty</a></nav>
            <nav class="footer-nav" aria-label="Plan your day"><h3>Plan your day</h3><a href="{{ request()->routeIs('home') ? '#venue' : route('home').'#venue' }}">Venue & location</a><a href="{{ route('registrations.create') }}">Delegate registration</a><a href="{{ request()->routeIs('home') ? '#faq' : route('home').'#faq' }}">Your questions, answered</a><a href="{{ asset('images/visionx-2026-poster.jpeg') }}" target="_blank" rel="noopener">Event announcement <x-icon name="arrow-up-right" /></a></nav>
            <div class="footer-association"><h3>In association with</h3><div class="footer-association-mark"><img src="{{ asset('images/ioa-kerala.png') }}" alt="Indian Optometrists Association Kerala emblem" width="62" height="65" loading="lazy"><span>Indian Optometrists<br>Association<strong>Kerala</strong></span></div><div class="footer-save-note"><span class="live-dot"></span>One community. A brighter tomorrow.</div><a href="{{ route('registrations.create') }}" class="footer-update-link">Register for VISION X <span aria-hidden="true"><x-icon name="arrow-right" /></span></a></div>
        </div>
        <div class="footer-signoff"><span>&copy; {{ now()->year }} VISION X. All rights reserved.</span><span>Doctors &middot; Optometrists &middot; Opticians</span><a href="{{ request()->routeIs('home') ? '#home' : '#main' }}">Back to top <span aria-hidden="true"><x-icon name="arrow-up" /></span></a></div>
    </div>
</footer>
