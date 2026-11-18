<?php

namespace Tests\Feature;

use App\Mail\RegistrationAcknowledgement;
use App\Mail\RegistrationNotification;
use App\Models\Registration;
use App\PaymentStatus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Tests\TestCase;

class RegistrationMailTest extends TestCase
{
    #[TestWith([RegistrationAcknowledgement::class, ['visionx-2026-logo.png', 'visionx-payment-qr.png', 'devi-eye-hospital-transparent.png']])]
    #[TestWith([RegistrationNotification::class, ['visionx-2026-logo.png', 'devi-eye-hospital-transparent.png']])]
    /** @param list<string> $filenames */
    public function test_emails_are_assembled_with_inline_images_and_plain_text(string $mailableClass, array $filenames): void
    {
        config(['mail.mailers.preview' => ['transport' => 'array']]);
        $registration = Registration::factory()->make(['reference' => '12345', 'created_at' => now()]);

        $sent = Mail::mailer('preview')->to('delegate@example.test')->send(new $mailableClass($registration));

        if ($sent === null) {
            $this->fail('The preview mailer must return a sent message.');
        }
        $email = $sent->getSymfonySentMessage()->getOriginalMessage();
        if (! $email instanceof Email) {
            $this->fail('The mailer must assemble a structured Symfony Email message.');
        }
        $this->assertStringContainsString('cid:', $email->getHtmlBody());
        $this->assertStringContainsString('12345', $email->getTextBody());
        $attachments = $email->getAttachments();
        $attachedFilenames = array_map(fn (DataPart $attachment): ?string => $attachment->getFilename(), $attachments);
        sort($filenames);
        sort($attachedFilenames);
        $this->assertSame($filenames, $attachedFilenames);
        foreach ($attachments as $attachment) {
            $this->assertSame('inline', $attachment->getDisposition());
        }
    }

    public function test_acknowledgement_contains_branded_event_details_and_reference_in_both_formats(): void
    {
        $registration = Registration::factory()->make(['reference' => '12345', 'profession' => 'optometrist']);
        $mail = new RegistrationAcknowledgement($registration);

        $mail->assertHasSubject('Registration received | VISION X 2026 | ID 12345');
        $mail->assertSeeInHtml('12345');
        $mail->assertSeeInHtml('YOUR REGISTRATION ID');
        $mail->assertSeeInHtml('6 DECEMBER 2026');
        $mail->assertSeeInHtml('Sasthamcotta, Kerala');
        $mail->assertSeeInHtml('Optometrist');
        $mail->assertSeeInText($registration->email);
        $mail->assertSeeInText('12345');
        $mail->assertSeeInText('6 December 2026');
    }

    public function test_acknowledgement_provides_payment_and_gate_pass_instructions_in_both_formats(): void
    {
        config(['services.whatsapp.number' => '+91 (94970) 05735']);
        $registration = Registration::factory()->make(['reference' => '12345']);
        $mail = new RegistrationAcknowledgement($registration);
        $paymentUrl = URL::signedRoute('registrations.payment', ['reference' => '12345']);

        foreach (['Rs.500/-', 'payment screenshot and registration ID', '+919497005735', 'gate pass', $paymentUrl] as $text) {
            $mail->assertSeeInHtml($text);
            $mail->assertSeeInText($text);
        }

        $mail->assertSeeInHtml('Devi Eye Hospital payment QR code');
        $mail->assertSeeInHtml('https://wa.me/919497005735?text=');
    }

    public function test_acknowledgement_omits_payment_instructions_when_payment_is_confirmed(): void
    {
        $registration = Registration::factory()->make(['reference' => '12345', 'payment_status' => PaymentStatus::Confirmed]);

        $mail = new RegistrationAcknowledgement($registration);

        $mail->assertDontSeeInHtml('Devi Eye Hospital payment QR code');
        $mail->assertDontSeeInText('Rs.500/-');
    }

    public function test_organiser_email_contains_complete_details_and_reply_to_delegate(): void
    {
        $registration = Registration::factory()->make(['reference' => '12345', 'profession' => 'optician', 'message' => 'Please share travel information.', 'organisation' => "O'Connell & Vision Clinic", 'created_at' => now()]);
        $mail = new RegistrationNotification($registration);

        $mail->assertHasReplyTo($registration->email);
        $mail->assertSeeInHtml('12345');
        $mail->assertSeeInHtml('REGISTRATION ID');
        $mail->assertSeeInText('REGISTRATION ID: 12345');
        $mail->assertSeeInHtml($registration->full_name);
        $mail->assertSeeInHtml($registration->phone);
        $mail->assertSeeInHtml('Optician');
        $mail->assertSeeInHtml('Please share travel information.');
        $mail->assertSeeInText($registration->organisation);
        $mail->assertSeeInText('Please share travel information.');
        $mail->assertSeeInText('12345');
    }

    public function test_all_free_text_is_escaped_in_the_organiser_email(): void
    {
        $registration = Registration::factory()->make([
            'reference' => '12345', 'created_at' => now(),
            'full_name' => '<img src=x onerror=alert(1)>', 'organisation' => '<script>practice</script>',
            'city' => '<svg onload=alert(2)>', 'state' => '<iframe>region</iframe>', 'message' => '<script>message</script>',
        ]);

        $html = (new RegistrationNotification($registration))->render();

        foreach (['full_name', 'organisation', 'city', 'state', 'message'] as $field) {
            $this->assertStringNotContainsString($registration->$field, $html);
            $this->assertStringContainsString(e($registration->$field), $html);
        }
    }
}
