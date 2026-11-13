@props(['profession'])
<svg {{ $attributes->merge(['viewBox' => '0 0 48 48', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.6', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($profession)
        @case('doctor')
            <path d="M12 7v14a9 9 0 0 0 18 0V7M8 7h8m10 0h8M21 30v5a8 8 0 0 0 16 0v-6"/><circle cx="37" cy="25" r="4"/>
            @break
        @case('optometrist')
            <path d="M3 24s8-13 21-13 21 13 21 13-8 13-21 13S3 24 3 24Z"/><circle cx="24" cy="24" r="8"/><circle cx="24" cy="24" r="2"/><path d="M24 3v4m0 34v4"/>
            @break
        @default
            <circle cx="12" cy="27" r="9"/><circle cx="36" cy="27" r="9"/><path d="M21 26h6M3 25l4-13h7m31 13-4-13h-7"/>
    @endswitch
</svg>
