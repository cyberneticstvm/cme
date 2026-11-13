<?php

namespace Tests\Feature;

use App\Mail\RegistrationIdReminder;
use App\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class RegistrationRecoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'smtp']);
        Mail::fake();
    }

    public function test_email_check_normalises_email_and_exposes_no_delegate_details(): void
    {
        $registration = Registration::factory()->create(['email' => 'registered@example.test']);
        $this->postJson(route('registrations.check-email'), ['email' => ' REGISTERED@EXAMPLE.TEST '])
            ->assertOk()->assertExactJson(['registered' => true])->assertHeader('Cache-Control', 'no-store, private')
            ->assertDontSee($registration->reference)->assertDontSee($registration->full_name);
        $this->postJson(route('registrations.check-email'), ['email' => 'new@example.test'])->assertExactJson(['registered' => false]);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_checks_validate_input_and_are_rate_limited(): void
    {
        $this->postJson(route('registrations.check-email'), ['email' => ['bad']])->assertUnprocessable()->assertJsonValidationErrors('email');
        for ($attempt = 0; $attempt < 29; $attempt++) {
            $this->postJson(route('registrations.check-email'), ['email' => 'new@example.test'])->assertOk();
        }
        $this->postJson(route('registrations.check-email'), ['email' => 'new@example.test'])->assertTooManyRequests();
    }

    public function test_recovery_sends_the_existing_id_only_to_the_canonical_registered_address(): void
    {
        $registration = Registration::factory()->create(['email' => 'registered@example.test']);
        $this->post(route('registrations.recover.send'), ['email' => ' REGISTERED@EXAMPLE.TEST ', 'recipient' => 'attacker@example.test', 'reference' => '99999'])
            ->assertRedirectToRoute('registrations.recover')->assertSessionHas('recovery_status')->assertSessionMissing('reference');
        Mail::assertSent(RegistrationIdReminder::class, fn ($mail) => $mail->hasTo($registration->email)
            && ! $mail->hasTo('attacker@example.test') && $mail->registration->reference === $registration->reference);
        Mail::assertSentCount(1);
        $this->get(route('registrations.recover'))->assertOk()->assertSee('Check your inbox')->assertDontSee($registration->reference);
        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_unknown_addresses_receive_the_same_generic_success_without_an_email(): void
    {
        $this->post(route('registrations.recover.send'), ['email' => 'unknown@example.test'])
            ->assertRedirectToRoute('registrations.recover')->assertSessionHas('recovery_status');
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('registrations');
    }

    public function test_recovery_cannot_repeatedly_email_the_same_address_even_from_another_ip(): void
    {
        Registration::factory()->create(['email' => 'registered@example.test']);
        $this->post(route('registrations.recover.send'), ['email' => 'registered@example.test'])->assertRedirect();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.33'])->post(route('registrations.recover.send'), ['email' => ' REGISTERED@EXAMPLE.TEST '])->assertTooManyRequests();
        Mail::assertSentCount(1);
    }

    #[TestWith(['log'])]
    #[TestWith(['array'])]
    public function test_local_mail_transports_do_not_claim_the_id_was_emailed(string $transport): void
    {
        config(['mail.default' => $transport]);
        Registration::factory()->create(['email' => 'registered@example.test']);
        $this->from(route('registrations.recover'))->post(route('registrations.recover.send'), ['email' => 'registered@example.test'])
            ->assertSessionHasErrors('email')->assertSessionMissing('recovery_status');
        Mail::assertNothingSent();
    }

    public function test_recovery_smtp_failure_reports_an_error_and_keeps_registration_unchanged(): void
    {
        $registration = Registration::factory()->create(['email' => 'registered@example.test']);
        Exceptions::fake();
        Mail::shouldReceive('to')->once()->with($registration->email, $registration->full_name)->andThrow(new RuntimeException('SMTP unavailable'));
        $this->from(route('registrations.recover'))->post(route('registrations.recover.send'), ['email' => $registration->email])
            ->assertSessionHasErrors('email')->assertSessionMissing('recovery_status');
        $this->assertSame($registration->reference, $registration->fresh()->reference);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_reminder_html_and_plain_text_include_the_id_and_escape_delegate_content(): void
    {
        $registration = Registration::factory()->create(['full_name' => "<script>O'Connell & delegate</script>"]);
        $mail = new RegistrationIdReminder($registration);
        $html = $mail->render();
        $this->assertStringContainsString($registration->reference, $html);
        $this->assertStringContainsString('&lt;script&gt;O&#039;Connell &amp; delegate&lt;/script&gt;', $html);
        $this->assertStringNotContainsString("<script>O'Connell & delegate</script>", $html);
        $text = view('mail.registrations.reminder-text', ['registration' => $registration])->render();
        $this->assertStringContainsString($registration->reference, $text);
        $this->assertStringContainsString($registration->full_name, $text);
    }
}
