@props(['name' => 'arrow-up-right'])

<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('qr')
            <path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6zM12 3v3m0 3v3H3m9 3v6m3-9h6" />
            @break
        @case('camera')
            <path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/>
            @break
        @case('image')
            <rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="2"/><path d="m3 17 6-5 4 3 4-5 4 4"/>
            @break
        @case('trash')
            <path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7" />
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>
            @break
        @case('users')
            <circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-17a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 4v3"/>
            @break
        @case('search')
            <circle cx="10" cy="10" r="7"/><path d="m16 16 5 5"/>
            @break
        @case('download')
            <path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"/>
            @break
        @case('logout')
            <path d="M9 4H4v16h5m6-13 5 5-5 5M9 12h11"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>
            @break
        @case('filter')
            <path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>
            @break
        @case('arrow-left')
            <path d="M20 12H4m6-6-6 6 6 6"/>
            @break
        @case('arrow-right')
            <path d="M4 12h16m-6-6 6 6-6 6" />
            @break
        @case('arrow-up')
            <path d="M12 20V4m-6 6 6-6 6 6" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="3" /><path d="M7 3v5m10-5v5M3 11h18m-13 4h1m6 0h1m-8 3h1" />
            @break
        @case('pin')
            <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" />
            @break
        @case('spark')
            <path d="M12 2v20M2 12h20M5 5l14 14M5 19 19 5" />
            @break
        @case('plus')
            <path d="M5 12h14M12 5v14" />
            @break
        @case('chat')
            <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2V11.5a9.5 9.5 0 0 1 19 0Z" /><path d="M7 10h8m-8 4h5" />
            @break
        @case('eye')
            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
            @break
        @default
            <path d="M5 19 19 5M5 5h14v14" />
    @endswitch
</svg>
