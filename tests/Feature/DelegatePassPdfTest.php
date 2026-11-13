<?php

namespace Tests\Feature;

use App\Mail\DelegatePass;
use App\Models\Registration;
use App\Services\DelegatePassPdf;
use App\Services\PassQrCode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DelegatePassPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pdf_renderer_rejects_an_unpaid_registration(): void
    {
        $registration = Registration::factory()->create();
        $this->expectException(ValidationException::class);
        app(DelegatePassPdf::class)->render($registration);
    }

    #[TestWith(['Dr Meera Nair'])]
    #[TestWith(['Professor '.'Alexandria Catherine Krishnamurthy Narayanan Subramanian Ramanathan Srinivasan Viswanathan'])]
    #[TestWith(['Dr Meera Nair', 'VX26-01M7ANC74CE3W74B5R28GMEXBE'])]
    public function test_confirmed_delegate_pass_renders_as_one_pdf_page(string $name, ?string $reference = null): void
    {
        $registration = $this->issued($name);
        if ($reference !== null) {
            $registration->reference = $reference;
        }
        $bytes = app(DelegatePassPdf::class)->render($registration);
        $this->assertStringStartsWith('%PDF-', $bytes);
        preg_match('/\/Count\s+([1-9][0-9]*)/', $bytes, $pageCount);
        $this->assertSame('1', $pageCount[1] ?? null, 'Delegate passes must fit on one page.');
        $this->assertGreaterThan(15000, strlen($bytes));
    }

    public function test_pass_and_mail_templates_escape_delegate_text_and_include_correct_id(): void
    {
        $registration = $this->issued('<script>delegate</script>');
        $html = view('passes.delegate', ['registration' => $registration, 'qrDataUri' => app(PassQrCode::class)->dataUri($registration->pass_id)])->render();
        $this->assertStringContainsString('&lt;script&gt;delegate&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>delegate</script>', $html);
        $this->assertStringContainsString($registration->reference, $html);
        Storage::fake('passes');
        Storage::disk('passes')->put($registration->passPath(), '%PDF-1.4 test attachment');
        $mail = new DelegatePass($registration);
        $mail->assertSeeInHtml($registration->reference)->assertSeeInText($registration->reference);
        $this->assertStringContainsString($registration->reference, $mail->envelope()->subject);
    }

    public function test_pass_mail_contains_a_real_pdf_attachment_and_inline_brand_logos(): void
    {
        $registration = $this->issued('Dr Meera Nair');
        $bytes = app(DelegatePassPdf::class)->render($registration);
        Storage::fake('passes');
        Storage::disk('passes')->put($registration->passPath(), $bytes);
        config(['mail.mailers.preview' => ['transport' => 'array']]);
        $sent = Mail::mailer('preview')->to($registration->email)->send(new DelegatePass($registration));
        $email = $sent->getSymfonySentMessage()->getOriginalMessage();
        $attachments = array_values(array_filter($email->getAttachments(), fn ($attachment) => $attachment->getDisposition() === 'attachment'));
        $this->assertCount(1, $attachments);
        $this->assertSame('application', $attachments[0]->getMediaType());
        $this->assertSame('pdf', $attachments[0]->getMediaSubtype());
        $this->assertSame('VISION-X-2026-pass-'.$registration->reference.'.pdf', $attachments[0]->getFilename());
        $this->assertSame($bytes, $attachments[0]->getBody());
        $this->assertStringContainsString($registration->reference, $email->getHtmlBody());
        $this->assertStringContainsString($registration->reference, $email->getTextBody());
        $this->assertStringContainsString('cid:', $email->getHtmlBody());
    }

    private function issued(string $name): Registration
    {
        $registration = Registration::factory()->create([
            'full_name' => $name, 'payment_status' => 'confirmed', 'payment_confirmed_at' => now(), 'status' => 'new',
            'organisation' => str_repeat('Long Organisation Name ', 8),
        ]);
        $registration->pass_id = (string) Str::uuid();
        $registration->pass_issued_at = now();
        $registration->pass_qr_version = PassQrCode::VERSION;

        return $registration;
    }
}
