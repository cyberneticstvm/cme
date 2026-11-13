<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipationCertificate extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your participation certificate | VISION X 2026 | ID '.$this->registration->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registrations.certificate', text: 'mail.registrations.certificate-text');
    }

    public function attachments(): array
    {
        return [Attachment::fromStorageDisk('certificates', $this->registration->certificatePath())
            ->as('VISION-X-2026-certificate-'.$this->registration->reference.'.pdf')->withMime('application/pdf')];
    }
}
