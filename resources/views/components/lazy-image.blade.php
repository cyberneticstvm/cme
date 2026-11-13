@props(['src', 'alt', 'width', 'height'])

<img {{ $attributes->merge(['class' => 'lazy-image']) }} data-lazy-image data-src="{{ $src }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" decoding="async" loading="lazy">
<noscript>
    <style>[data-lazy-image] { display: none !important; }</style>
    <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" loading="lazy">
</noscript>
