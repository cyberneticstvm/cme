<?php

namespace Tests\Feature;

use App\Mail\RegistrationAcknowledgement;
use App\Mail\RegistrationNotification;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class RegistrationMailTest extends TestCase
{
    #[TestWith([RegistrationAcknowledgement::class])]
    #[TestWith([RegistrationNotification::class])]
    public function test_emails_are_assembled_with_inline_logos_and_plain_text(string $mailableClass): void
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
        $this->assertCount(2, $email->getAttachments());
        foreach ($email->getAttachments() as $attachment) {
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
        $mail->assertSeeInHtml('Shastamcotta, Kerala');
        $mail->assertSeeInHtml('Optometrist');
        $mail->assertSeeInText($registration->email);
        $mail->assertSeeInText('12345');
        $mail->assertSeeInText('6 December 2026');
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
