<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Mail\DelegatePass;
use App\Models\Registration;
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

class RegistrationPassTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42', 'mail.default' => 'smtp']);
        Mail::fake();
        Storage::fake('passes');
    }

    #[TestWith(['POST', '/admin/registrations/1/pass'])]
    #[TestWith(['GET', '/admin/registrations/1/pass'])]
    #[TestWith(['GET', '/admin/registrations/1/pass/manage'])]
    public function test_pass_endpoints_require_admin_authentication(string $method, string $path): void
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
        $this->assertSame('pending', $registration->fresh()->payment_status);
        $this->assertNull($registration->payment_confirmed_at);
        $this->assertNull($registration->pass_id);
        $this->assertNull($registration->checked_in_at);
        $this->assertSame(0, $registration->pass_qr_version);
    }

    public function test_cancelled_registrations_cannot_generate_or_download_passes(): void
    {
        $registration = Registration::factory()->create(['status' => 'cancelled']);
        $this->signIn();
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHasErrors('registration');
        $this->get(route('admin.registrations.pass.download', $registration))->assertForbidden();
        $this->assertNull($registration->fresh()->pass_id);
        Storage::disk('passes')->assertDirectoryEmpty('/');
        Mail::assertNothingSent();
    }

    public function test_pass_service_rechecks_current_registration_status_instead_of_trusting_a_stale_model(): void
    {
        $registration = Registration::factory()->create();
        Registration::whereKey($registration->id)->update(['status' => 'cancelled']);
        $this->expectException(ValidationException::class);
        app(RegistrationPassService::class)->generateAndSend($registration);
    }

    public function test_admin_can_generate_a_private_pdf_and_email_only_the_registered_delegate(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->times(3)->andReturn('%PDF-1.4 test document');
        $this->post(route('admin.registrations.pass.send', $registration), ['email' => 'attacker@example.test'])
            ->assertRedirectToRoute('admin.registrations.pass', $registration)->assertSessionHasNoErrors();
        $registration->refresh();
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->assertNotNull($registration->pass_sent_at);
        $this->assertSame($registration->email, $registration->pass_sent_to);
        Mail::assertSent(DelegatePass::class, fn ($mail) => $mail->hasTo($registration->email) && ! $mail->hasTo('attacker@example.test')
            && $mail->hasAttachment(Attachment::fromStorageDisk('passes', $registration->passPath())->as('VISION-X-2026-pass-'.$registration->reference.'.pdf')->withMime('application/pdf')));
        Storage::disk('passes')->put($registration->passPath(), '%PDF-1.4 old copy');
        $this->get(route('admin.registrations.pass.download', $registration))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-1.4 test document');
        $passId = $registration->pass_id;
        $this->post(route('admin.registrations.pass.send', $registration))->assertRedirect();
        $this->assertSame($passId, $registration->fresh()->pass_id);
        $this->assertSame('%PDF-1.4 test document', Storage::disk('passes')->get($registration->passPath()));
        Mail::assertSentCount(2);
    }

    public function test_log_transport_retains_pdf_but_leaves_email_pending(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        config(['mail.default' => 'log']);
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 test');
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHas('warning');
        $registration->refresh();
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->assertNull($registration->pass_sent_at);
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

    public function test_admin_can_manage_passes_for_legacy_pending_and_refunded_registrations(): void
    {
        $this->signIn();

        foreach (['pending', 'refunded'] as $status) {
            $registration = Registration::factory()->create(['payment_status' => $status]);
            $this->get(route('admin.registrations.pass', $registration))->assertOk()
                ->assertSeeText('Generate & email PDF pass')->assertDontSeeText('Payment');
        }
    }

    public function test_smtp_failure_keeps_pdf_private_and_allows_a_retry_with_the_same_pass_id(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->twice()->andReturn('%PDF-1.4 test');
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

    public function test_registration_without_a_generated_pass_has_no_download(): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        $this->get(route('admin.registrations.pass.download', $registration))->assertNotFound();
        $this->assertNull($registration->fresh()->pass_id);
    }

    private function issued(): Registration
    {
        $registration = Registration::factory()->create(['status' => 'new']);
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
