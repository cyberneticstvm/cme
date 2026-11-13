<dialog id="welcome-poster" class="poster-dialog" aria-labelledby="poster-dialog-title" aria-describedby="poster-dialog-description" aria-modal="true">
    <button type="button" class="poster-close" data-dismiss-poster aria-label="Close event announcement" autofocus>
        <x-icon name="plus" />
    </button>
    <div class="poster-layout">
        <div class="poster-artwork">
            <span class="poster-mobile-label">OFFICIAL EVENT ANNOUNCEMENT</span>
            <img data-popup-poster data-src="{{ asset('images/visionx-2026-poster.jpeg') }}" alt="Official VISION X 2026 CME poster: Devi Eye Hospital in association with Indian Optometrists Association Kerala. Sunday, 6 December 2026, Shastamcotta." width="1230" height="1278" decoding="async">
        </div>
        <div class="poster-copy">
            <div class="poster-copy-main">
                <p class="poster-eyebrow">A NEW PERSPECTIVE ON EYE CARE</p>
                <h2 id="poster-dialog-title">One day.<br><span>A shared vision.</span></h2>
                <p id="poster-dialog-description">Join doctors, optometrists and opticians for VISION X 2026, a continuing medical education programme.</p>
                <div class="poster-event-details">
                    <div><x-icon name="calendar" /><span><small>SAVE THE DATE</small><strong>6 December 2026</strong><span>Sunday</span></span></div>
                    <div><x-icon name="pin" /><span><small>THE DESTINATION</small><strong>Shastamcotta, Kerala</strong></span></div>
                </div>
            </div>
            <div class="poster-actions">
                <button type="button" class="button poster-continue" data-dismiss-poster>Continue to website <x-icon name="arrow-right" /></button>
                <a href="{{ asset('images/visionx-2026-poster.jpeg') }}" target="_blank" rel="noopener">View full-size poster <x-icon name="arrow-up-right" /></a>
            </div>
            <p class="poster-signoff">A shared vision. A brighter tomorrow.</p>
        </div>
    </div>
</dialog>
