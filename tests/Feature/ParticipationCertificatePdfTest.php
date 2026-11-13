<?php

namespace Tests\Feature;

use App\Mail\ParticipationCertificate;
use App\Models\Registration;
use App\Services\ParticipationCertificatePdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Tests\TestCase;

class ParticipationCertificatePdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pdf_renderer_rejects_a_registration_without_attendance(): void
    {
        $registration = Registration::factory()->create();

        $this->expectException(ValidationException::class);
        app(ParticipationCertificatePdf::class)->render($registration);
    }

    #[TestWith(['Dr Meera Nair', 'doctor', null])]
    #[TestWith(['Professor Alexandria Catherine Krishnamurthy Narayanan Subramanian Ramanathan Srinivasan Viswanathan', 'optometrist', null])]
    #[TestWith(['Arun Menon', 'optician', 'VX26-01M7ANC74CE3W74B5R28GMEXBE'])]
    public function test_certificate_is_a_single_landscape_pdf_for_all_professions_and_legacy_ids(string $name, string $profession, ?string $reference): void
    {
        $registration = $this->issued(['full_name' => $name, 'profession' => $profession]);
        if ($reference !== null) {
            $registration->reference = $reference;
            $registration->certificate_number = 'VX26-PC-'.$reference;
        }

        $bytes = app(ParticipationCertificatePdf::class)->render($registration);

        $this->assertStringStartsWith('%PDF-', $bytes);
        preg_match('/\/Count\s+([1-9][0-9]*)/', $bytes, $pageCount);
        $this->assertSame('1', $pageCount[1] ?? null, 'Participation certificates must fit on one page.');
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[0(?:\.\d+)? 0(?:\.\d+)? 841\.\d+ 595\.\d+\]/', $bytes);
        $this->assertGreaterThan(15000, strlen($bytes));
    }

    public function test_certificate_and_email_escape_delegate_details_and_include_both_identifiers(): void
    {
        $registration = $this->issued(['full_name' => "O'Reilly <script>delegate</script>", 'organisation' => '<b>Eye Clinic</b>']);

        $html = view('certificates.participation', ['registration' => $registration])->render();

        $this->assertStringContainsString('&lt;script&gt;delegate&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;Eye Clinic&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<script>delegate</script>', $html);
        $this->assertStringNotContainsString('<b>Eye Clinic</b>', $html);
        $this->assertStringContainsString($registration->reference, $html);
        $this->assertStringContainsString($registration->certificate_number, $html);
        Storage::fake('certificates');
        Storage::disk('certificates')->put($registration->certificatePath(), '%PDF-1.4 certificate');
        $mail = new ParticipationCertificate($registration);
        $mail->assertSeeInHtml('&lt;script&gt;delegate&lt;/script&gt;', false)->assertDontSeeInHtml('<script>delegate</script>', false)
            ->assertSeeInHtml($registration->certificate_number)->assertSeeInText($registration->certificate_number)
            ->assertSeeInText("O'Reilly")->assertSeeInText($registration->reference);
        $this->assertStringContainsString($registration->reference, $mail->envelope()->subject);
    }

    public function test_email_contains_the_real_certificate_pdf_and_embedded_brand_logos(): void
    {
        $registration = $this->issued(['full_name' => 'Dr Meera Nair']);
        $bytes = app(ParticipationCertificatePdf::class)->render($registration);
        Storage::fake('certificates');
        Storage::disk('certificates')->put($registration->certificatePath(), $bytes);
        config(['mail.mailers.preview' => ['transport' => 'array']]);

        $sent = Mail::mailer('preview')->to($registration->email)->send(new ParticipationCertificate($registration));

        if ($sent === null) {
            $this->fail('The preview mailer must return a sent message.');
        }
        $email = $sent->getSymfonySentMessage()->getOriginalMessage();
        if (! $email instanceof Email) {
            $this->fail('The mailer must assemble a structured Symfony Email message.');
        }
        $attachments = array_values(array_filter($email->getAttachments(), fn (DataPart $part): bool => $part->getDisposition() === 'attachment'));
        $this->assertCount(1, $attachments);
        $this->assertSame('application', $attachments[0]->getMediaType());
        $this->assertSame('pdf', $attachments[0]->getMediaSubtype());
        $this->assertSame('VISION-X-2026-certificate-'.$registration->reference.'.pdf', $attachments[0]->getFilename());
        $this->assertSame($bytes, $attachments[0]->getBody());
        $this->assertStringContainsString($registration->certificate_number, $email->getHtmlBody());
        $this->assertStringContainsString($registration->certificate_number, $email->getTextBody());
        $this->assertStringContainsString('cid:', $email->getHtmlBody());
    }

    /** @param array<string, mixed> $overrides */
    private function issued(array $overrides = []): Registration
    {
        $registration = Registration::factory()->create(array_merge([
            'profession' => 'doctor', 'status' => 'confirmed', 'checked_in_at' => now(),
            'organisation' => str_repeat('Long Organisation Name ', 8),
        ], $overrides));
        $registration->certificate_id = (string) Str::uuid();
        $registration->certificate_number = 'VX26-PC-'.$registration->reference;
        $registration->certificate_issued_at = now();
        $registration->certificate_issued_by = 'event-admin';

        return $registration;
    }
}
