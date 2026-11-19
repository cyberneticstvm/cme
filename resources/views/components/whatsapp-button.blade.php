@php($whatsAppNumber = preg_replace('/[\s()+.\-]/', '', (string) config('services.whatsapp.number')))

@if (preg_match('/^[1-9][0-9]{6,14}$/D', $whatsAppNumber))
    <a class="whatsapp-float" href="https://wa.me/{{ $whatsAppNumber }}?text={{ rawurlencode((string) config('services.whatsapp.message')) }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with the VISION X team on WhatsApp (opens in a new tab)">
        <span class="whatsapp-tooltip">Let&rsquo;s talk about VISION X</span>
        <svg viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 .9A14.9 14.9 0 0 0 3.1 23.2L1 31l8-2.1A14.9 14.9 0 1 0 16 .9Zm0 27.2a12.3 12.3 0 0 1-6.3-1.7l-.5-.3-4.8 1.3 1.3-4.7-.3-.5A12.3 12.3 0 1 1 16 28.1Zm6.8-9.2c-.4-.2-2.2-1.1-2.6-1.2-.3-.1-.6-.2-.8.2-.3.4-1 1.2-1.2 1.4-.2.3-.4.3-.8.1-.4-.2-1.6-.6-3-1.8-1.1-1-1.8-2.2-2-2.6-.2-.4 0-.6.2-.8l.6-.7.4-.7c.1-.2 0-.5 0-.7l-1.2-2.8c-.3-.7-.6-.6-.8-.6h-.7c-.3 0-.7.1-1 .5-.4.4-1.3 1.2-1.3 3s1.3 3.5 1.5 3.7c.2.3 2.6 4 6.3 5.6.9.4 1.6.6 2.1.8.9.3 1.7.2 2.4.1.7-.1 2.2-.9 2.5-1.8.3-.9.3-1.7.2-1.8-.1-.2-.3-.3-.7-.5Z"/></svg>
        <span class="whatsapp-online" aria-hidden="true"></span>
    </a>
@endif
