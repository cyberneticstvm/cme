<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationNotification extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->registration->email, $this->registration->full_name)],
            subject: 'New delegate registration | VISION X 2026 | ID '.$this->registration->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registrations.notification', text: 'mail.registrations.notification-text');
    }
}
