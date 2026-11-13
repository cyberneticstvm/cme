<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationIdReminder extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your registration ID | VISION X 2026 | '.$this->registration->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registrations.reminder', text: 'mail.registrations.reminder-text');
    }
}
