<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Mail\DelegatePass;
use App\Models\Registration;
use App\PaymentStatus;
use App\Services\DelegatePassPdf;
use App\Services\RegistrationPassService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class RegistrationPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42', 'mail.default' => 'smtp']);
        Mail::fake();
        Storage::fake('passes');
    }

    #[TestWith(['GET', '/admin/registrations/1/payment'])]
    #[TestWith(['PUT', '/admin/registrations/1/payment'])]
    #[TestWith(['POST', '/admin/registrations/1/pass'])]
    #[TestWith(['GET', '/admin/registrations/1/pass'])]
    public function test_payment_and_pass_endpoints_require_admin_authentication(string $method, string $path): void
    {
        $this->call($method, $path)->assertRedirectToRoute('admin.login');
        Mail::assertNothingSent();
    }

    public function test_public_registration_cannot_set_payment_or_pass_fields(): void
    {
        $this->post(route('registrations.store'), array_merge(Registration::factory()->raw(['profession' => 'doctor', 'consented_at' => '2026-11-13 12:00:00']), [
            'consent' => '1', 'payment_status' => 'confirmed', 'payment_confirmed_at' => '2026-11-13 12:00:00', 'pass_id' => (string) Str::uuid(), 'checked_in_at' => '2026-11-13 12:00:00', 'pass_qr_version' => 1,
        ]))->assertRedirectToRoute('registrations.thanks');
        $registration = Registration::sole();
        $this->assertSame(PaymentStatus::Pending, $registration->payment_status);
        $this->assertNull($registration->payment_confirmed_at);
        $this->assertNull($registration->pass_id);
        $this->assertNull($registration->checked_in_at);
        $this->assertSame(0, $registration->pass_qr_version);
    }

    public function test_confirming_payment_requires_explicit_verification_and_valid_payment_details(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        $this->put(route('admin.registrations.payment.update', $registration), ['payment_status' => 'confirmed', 'payment_amount' => '-1'])
            ->assertSessionHasErrors(['confirm_payment', 'payment_amount']);
        $this->assertSame(PaymentStatus::Pending, $registration->fresh()->payment_status);
        $this->put(route('admin.registrations.payment.update', $registration), ['payment_status' => 'invalid'])->assertSessionHasErrors('payment_status');
        Mail::assertNothingSent();
    }

    public function test_payment_confirmation_records_the_admin_and_does_not_send_a_pass_automatically(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        $this->put(route('admin.registrations.payment.update', $registration), [
            'payment_status' => 'confirmed', 'confirm_payment' => '1', 'payment_amount' => '1250.50', 'payment_reference' => 'UPI-123', 'payment_notes' => 'Verified in bank statement.',
        ])->assertRedirectToRoute('admin.registrations.payment', $registration)->assertSessionHasNoErrors();
        $registration->refresh();
        $this->assertTrue($registration->canIssuePass());
        $this->assertSame('1250.50', $registration->payment_amount);
        $this->assertSame('event-admin', $registration->payment_confirmed_by);
        $this->assertNotNull($registration->payment_updated_at);
        $this->assertNull($registration->pass_id);
        Mail::assertNothingSent();
        $this->get(route('admin.registrations.payment', $registration))->assertOk()->assertSee('Generate &amp; email PDF pass', false);
    }

    #[TestWith(['pending', 'new', null])]
    #[TestWith(['refunded', 'new', null])]
    #[TestWith(['confirmed', 'cancelled', '2026-11-13 12:00:00'])]
    #[TestWith(['confirmed', 'new', null])]
    public function test_unpaid_refunded_cancelled_and_unverified_records_cannot_generate_or_download_passes(string $payment, string $status, ?string $confirmedAt): void
    {
        $registration = Registration::factory()->create(['payment_status' => $payment, 'status' => $status, 'payment_confirmed_at' => $confirmedAt]);
        $this->signIn();
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHasErrors('payment_status');
        $this->get(route('admin.registrations.pass.download', $registration))->assertSessionHasErrors('payment_status');
        $this->assertNull($registration->fresh()->pass_id);
        Storage::disk('passes')->assertDirectoryEmpty('/');
        Mail::assertNothingSent();
    }

    public function test_pass_service_rechecks_current_payment_instead_of_trusting_a_stale_model(): void
    {
        $registration = $this->paid();
        Registration::whereKey($registration->id)->update(['payment_status' => 'refunded']);
        $this->expectException(ValidationException::class);
        app(RegistrationPassService::class)->generateAndSend($registration);
    }

    public function test_confirmed_payment_can_generate_a_private_pdf_and_email_only_the_registered_delegate(): void
    {
        $registration = $this->paid();
        $this->signIn();
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 test document');
        $this->post(route('admin.registrations.pass.send', $registration), ['email' => 'attacker@example.test'])
            ->assertRedirectToRoute('admin.registrations.payment', $registration)->assertSessionHasNoErrors();
        $registration->refresh();
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->assertNotNull($registration->pass_sent_at);
        $this->assertSame($registration->email, $registration->pass_sent_to);
        Mail::assertSent(DelegatePass::class, fn ($mail) => $mail->hasTo($registration->email) && ! $mail->hasTo('attacker@example.test')
            && $mail->hasAttachment(Attachment::fromStorageDisk('passes', $registration->passPath())->as('VISION-X-2026-pass-'.$registration->reference.'.pdf')->withMime('application/pdf')));
        $this->get(route('admin.registrations.pass.download', $registration))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-1.4 test document');
        $passId = $registration->pass_id;
        $this->post(route('admin.registrations.pass.send', $registration))->assertRedirect();
        $this->assertSame($passId, $registration->fresh()->pass_id);
        Mail::assertSentCount(2);
    }

    public function test_log_transport_retains_pdf_but_leaves_email_pending(): void
    {
        $registration = $this->paid();
        $this->signIn();
        config(['mail.default' => 'log']);
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 test');
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHas('warning');
        $registration->refresh();
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->assertNull($registration->pass_sent_at);
        Mail::assertNothingSent();
    }

    #[TestWith(['pending'])]
    #[TestWith(['refunded'])]
    public function test_removing_confirmation_revokes_the_pdf_and_disables_delivery(string $status): void
    {
        $registration = $this->issued();
        $path = $registration->passPath();
        $this->signIn();
        $this->put(route('admin.registrations.payment.update', $registration), ['payment_status' => $status])->assertSessionHasNoErrors();
        $registration->refresh();
        $this->assertFalse($registration->canIssuePass());
        $this->assertNull($registration->pass_id);
        $this->assertNull($registration->pass_sent_at);
        Storage::disk('passes')->assertMissing($path);
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHasErrors('payment_status');
        Mail::assertNothingSent();
    }

    #[TestWith(['email', 'new@example.test'])]
    #[TestWith(['full_name', 'Updated Delegate'])]
    #[TestWith(['status', 'cancelled'])]
    public function test_contact_changes_and_cancellation_revoke_an_existing_pass(string $field, string $value): void
    {
        $registration = $this->issued();
        $path = $registration->passPath();
        $this->signIn();
        $data = array_merge($registration->only(['full_name', 'email', 'phone', 'organisation', 'city', 'state', 'message']), [
            'profession' => $registration->profession->value, 'status' => $registration->status->value, 'admin_notes' => null,
        ], [$field => $value]);
        $this->put(route('admin.registrations.update', $registration), $data)->assertSessionHasNoErrors();
        $this->assertNull($registration->fresh()->pass_id);
        Storage::disk('passes')->assertMissing($path);
        Mail::assertNothingSent();
    }

    public function test_deleting_registration_also_removes_the_private_pass(): void
    {
        $registration = $this->issued();
        $path = $registration->passPath();
        $this->signIn();
        $this->delete(route('admin.registrations.destroy', $registration), ['confirm_delete' => '1'])->assertRedirectToRoute('admin.dashboard');
        Storage::disk('passes')->assertMissing($path);
        $this->assertModelMissing($registration);
    }

    public function test_payment_filters_apply_to_dashboard_and_excel(): void
    {
        $registration = $this->paid();
        Registration::factory()->create(['full_name' => 'Unpaid Delegate']);
        $this->signIn();
        $this->get(route('admin.dashboard', ['payment_status' => 'confirmed']))->assertOk()->assertSee($registration->full_name)->assertDontSee('Unpaid Delegate');
        $this->get(route('admin.dashboard', ['payment_status' => 'invalid']))->assertSessionHasErrors('payment_status');
    }

    public function test_smtp_failure_keeps_pdf_private_and_allows_a_retry_without_regenerating(): void
    {
        $registration = $this->paid();
        $this->signIn();
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 test');
        Exceptions::fake();
        $mailFake = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->once()->with($registration->email, $registration->full_name)->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHas('warning');
        $registration->refresh();
        $passId = $registration->pass_id;
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->assertNull($registration->pass_sent_at);
        Exceptions::assertReported(RuntimeException::class);
        Mail::swap($mailFake);
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHas('status');
        $this->assertSame($passId, $registration->fresh()->pass_id);
        $this->assertNotNull($registration->fresh()->pass_sent_at);
        Mail::assertSent(DelegatePass::class);
    }

    public function test_confirmed_payment_without_a_generated_pass_has_no_download(): void
    {
        $registration = $this->paid();
        $this->signIn();
        $this->get(route('admin.registrations.pass.download', $registration))->assertNotFound();
        $this->assertNull($registration->fresh()->pass_id);
    }

    private function paid(): Registration
    {
        return Registration::factory()->create(['status' => 'new', 'payment_status' => 'confirmed', 'payment_confirmed_at' => now(), 'payment_confirmed_by' => 'event-admin']);
    }

    private function issued(): Registration
    {
        $registration = $this->paid();
        $registration->pass_id = (string) Str::uuid();
        $registration->pass_issued_at = now();
        $registration->save();
        Storage::disk('passes')->put($registration->passPath(), '%PDF-1.4 test');

        return $registration;
    }

    private function signIn(): void
    {
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
    }
}
