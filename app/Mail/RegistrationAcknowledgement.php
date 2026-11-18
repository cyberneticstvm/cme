<?php

namespace App\Mail;

use App\Models\Registration;
use App\Services\RegistrationPayment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationAcknowledgement extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Registration received | VISION X 2026 | ID '.$this->registration->reference);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.registrations.acknowledgement',
            text: 'mail.registrations.acknowledgement-text',
            with: [
                'paymentUrl' => RegistrationPayment::isDue($this->registration)
                    ? RegistrationPayment::url($this->registration->reference)
                    : null,
                'whatsAppNumber' => RegistrationPayment::whatsAppNumber(),
                'whatsAppUrl' => RegistrationPayment::whatsAppUrl($this->registration->reference),
            ],
        );
    }
}
