<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DelegatePass extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your delegate pass | VISION X 2026 | ID '.$this->registration->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registrations.pass', text: 'mail.registrations.pass-text');
    }

    public function attachments(): array
    {
        return [Attachment::fromStorageDisk('passes', $this->registration->passPath())
            ->as('VISION-X-2026-pass-'.$this->registration->reference.'.pdf')->withMime('application/pdf')];
    }
}
