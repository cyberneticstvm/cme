<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Mail\ParticipationCertificate;
use App\Models\Registration;
use App\Services\ParticipationCertificatePdf;
use App\Services\RegistrationCertificateService;
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

class RegistrationCertificateTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42', 'mail.default' => 'smtp']);
        Mail::fake();
        Storage::fake('certificates');
    }

    #[TestWith(['GET', '/admin/registrations/1/certificate'])]
    #[TestWith(['POST', '/admin/registrations/1/certificate'])]
    #[TestWith(['GET', '/admin/registrations/1/certificate/download'])]
    public function test_certificate_endpoints_require_admin_authentication(string $method, string $path): void
    {
        $this->call($method, $path)->assertRedirectToRoute('admin.login');

        Mail::assertNothingSent();
        Storage::disk('certificates')->assertDirectoryEmpty('/');
    }

    #[TestWith(['new', null])]
    #[TestWith(['cancelled', '2026-12-06 03:30:00'])]
    public function test_unchecked_and_cancelled_delegates_cannot_generate_or_download_certificates(string $status, ?string $checkedInAt): void
    {
        $registration = Registration::factory()->create(['status' => $status, 'checked_in_at' => $checkedInAt]);
        $this->signIn();

        $this->get(route('admin.registrations.certificate', $registration))->assertOk()->assertSeeText('Certificate locked')
            ->assertDontSee('data-admin-submit', false);
        $this->post(route('admin.registrations.certificate.send', $registration), ['checked_in_at' => now()->toDateTimeString()])
            ->assertSessionHasErrors(['certificate' => 'Certificates can be issued only to checked-in delegates with an active registration.']);
        $this->get(route('admin.registrations.certificate.download', $registration))->assertSessionHasErrors('certificate');

        $this->assertNull($registration->fresh()->certificate_id);
        Mail::assertNothingSent();
        Storage::disk('certificates')->assertDirectoryEmpty('/');
    }

    #[TestWith(['doctor'])]
    #[TestWith(['optometrist'])]
    #[TestWith(['optician'])]
    public function test_checked_in_delegates_receive_a_private_certificate_only_at_the_registered_email(string $profession): void
    {
        $this->freezeTime();
        $registration = $this->attended(['profession' => $profession]);
        $this->signIn();
        $this->mock(ParticipationCertificatePdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 certificate');

        $this->post(route('admin.registrations.certificate.send', $registration), ['email' => 'other@example.test', 'certificate_number' => 'FORGED'])
            ->assertRedirectToRoute('admin.registrations.certificate', $registration)->assertSessionHas('status')->assertSessionHasNoErrors();

        $registration->refresh();
        $this->assertTrue(Str::isUuid($registration->certificate_id));
        $this->assertSame('VX26-PC-'.$registration->reference, $registration->certificate_number);
        $this->assertSame('event-admin', $registration->certificate_issued_by);
        $this->assertSame(now()->toDateTimeString(), $registration->certificate_issued_at->toDateTimeString());
        $this->assertNotNull($registration->certificate_sent_at);
        $this->assertSame($registration->email, $registration->certificate_sent_to);
        Storage::disk('certificates')->assertExists($registration->certificatePath());
        Mail::assertSent(ParticipationCertificate::class, fn (ParticipationCertificate $mail): bool => $mail->hasTo($registration->email)
            && ! $mail->hasTo('other@example.test')
            && $mail->hasAttachment(Attachment::fromStorageDisk('certificates', $registration->certificatePath())
                ->as('VISION-X-2026-certificate-'.$registration->reference.'.pdf')->withMime('application/pdf')));
        Mail::assertSentCount(1);
        $this->get(route('admin.registrations.certificate.download', $registration))->assertOk()->assertDownload()
            ->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertSee('%PDF-1.4 certificate');
    }

    public function test_opening_the_certificate_screen_or_download_does_not_generate_or_send_a_certificate(): void
    {
        $registration = $this->attended();
        $this->signIn();

        $this->get(route('admin.registrations.certificate', $registration))->assertOk()->assertSeeText('Generate & email certificate');
        $this->get(route('admin.registrations.certificate.download', $registration))->assertNotFound();

        $this->assertNull($registration->fresh()->certificate_id);
        Mail::assertNothingSent();
        Storage::disk('certificates')->assertDirectoryEmpty('/');
    }

    public function test_service_rechecks_attendance_on_the_current_record(): void
    {
        $registration = $this->attended();
        Registration::whereKey($registration->id)->update(['checked_in_at' => null]);

        $this->expectException(ValidationException::class);
        app(RegistrationCertificateService::class)->generateAndSend($registration, 'event-admin');
    }

    #[TestWith(['log'])]
    #[TestWith(['array'])]
    public function test_local_mail_transports_keep_the_certificate_pending(string $transport): void
    {
        $registration = $this->attended();
        $this->signIn();
        config(['mail.default' => $transport]);
        $this->mock(ParticipationCertificatePdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 certificate');

        $this->post(route('admin.registrations.certificate.send', $registration))->assertSessionHas('warning');

        $registration->refresh();
        Storage::disk('certificates')->assertExists($registration->certificatePath());
        $this->assertNull($registration->certificate_sent_at);
        $this->assertNull($registration->certificate_sent_to);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_can_be_retried_without_regenerating_the_certificate(): void
    {
        $this->freezeTime();
        $registration = $this->attended();
        $this->signIn();
        $this->mock(ParticipationCertificatePdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 certificate');
        Exceptions::fake();
        $mailFake = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->once()->with($registration->email, $registration->full_name)->andThrow(new RuntimeException('SMTP unavailable'));

        $this->post(route('admin.registrations.certificate.send', $registration))->assertSessionHas('warning');

        $registration->refresh();
        $id = $registration->certificate_id;
        $number = $registration->certificate_number;
        $issuedAt = $registration->certificate_issued_at->toDateTimeString();
        Storage::disk('certificates')->assertExists($registration->certificatePath());
        $this->assertNull($registration->certificate_sent_at);
        Exceptions::assertReported(RuntimeException::class);

        Mail::swap($mailFake);
        $this->travel(1)->minutes();
        $this->post(route('admin.registrations.certificate.send', $registration))->assertSessionHas('status');
        $registration->refresh();
        $this->assertSame($id, $registration->certificate_id);
        $this->assertSame($number, $registration->certificate_number);
        $this->assertSame($issuedAt, $registration->certificate_issued_at->toDateTimeString());
        $this->assertNotNull($registration->certificate_sent_at);
        Mail::assertSent(ParticipationCertificate::class, 1);
    }

    public function test_resending_reuses_the_certificate_and_records_the_current_registered_recipient(): void
    {
        $registration = $this->issued();
        $id = $registration->certificate_id;
        Registration::whereKey($registration->id)->update(['email' => 'current@example.test']);

        $this->assertTrue(app(RegistrationCertificateService::class)->generateAndSend($registration, 'event-admin'));

        $registration->refresh();
        $this->assertSame($id, $registration->certificate_id);
        $this->assertSame('current@example.test', $registration->certificate_sent_to);
        Mail::assertSent(ParticipationCertificate::class, fn (ParticipationCertificate $mail): bool => $mail->hasTo('current@example.test'));
    }

    public function test_a_missing_private_file_is_regenerated_with_the_same_certificate_number(): void
    {
        $registration = $this->issued();
        $oldId = $registration->certificate_id;
        $number = $registration->certificate_number;
        Storage::disk('certificates')->delete($registration->certificatePath());
        $this->mock(ParticipationCertificatePdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 replacement');

        $this->assertTrue(app(RegistrationCertificateService::class)->generateAndSend($registration, 'event-admin'));

        $registration->refresh();
        $this->assertNotSame($oldId, $registration->certificate_id);
        $this->assertSame($number, $registration->certificate_number);
        Storage::disk('certificates')->assertExists($registration->certificatePath());
        Mail::assertSent(ParticipationCertificate::class, 1);
    }

    #[TestWith(['email', 'corrected@example.test'])]
    #[TestWith(['full_name', 'Corrected Delegate Name'])]
    #[TestWith(['profession', 'optician'])]
    #[TestWith(['organisation', 'Corrected Eye Clinic'])]
    #[TestWith(['status', 'cancelled'])]
    public function test_delegate_corrections_and_cancellation_revoke_the_old_certificate_without_resetting_attendance(string $field, string $value): void
    {
        $registration = $this->issued();
        $path = $registration->certificatePath();
        $this->signIn();

        $this->put(route('admin.registrations.update', $registration), $this->editPayload($registration, [$field => $value]))->assertSessionHasNoErrors();

        $registration->refresh();
        $this->assertNull($registration->certificate_id);
        $this->assertNull($registration->certificate_number);
        $this->assertNull($registration->certificate_sent_at);
        $this->assertNotNull($registration->checked_in_at);
        Storage::disk('certificates')->assertMissing($path);
        Mail::assertNothingSent();
    }

    public function test_editing_internal_notes_keeps_the_certificate(): void
    {
        $registration = $this->issued();
        $id = $registration->certificate_id;
        $this->signIn();

        $this->put(route('admin.registrations.update', $registration), $this->editPayload($registration, ['admin_notes' => 'Certificate checked.']))->assertSessionHasNoErrors();

        $this->assertSame($id, $registration->fresh()->certificate_id);
        Storage::disk('certificates')->assertExists($registration->certificatePath());
        Mail::assertNothingSent();
    }

    public function test_deleting_a_registration_removes_its_private_certificate(): void
    {
        $registration = $this->issued();
        $path = $registration->certificatePath();
        $this->signIn();

        $this->delete(route('admin.registrations.destroy', $registration), ['confirm_delete' => '1'])->assertRedirectToRoute('admin.dashboard');

        $this->assertModelMissing($registration);
        Storage::disk('certificates')->assertMissing($path);
        Mail::assertNothingSent();
    }

    public function test_public_registration_cannot_forge_certificate_or_attendance_metadata(): void
    {
        $payload = array_merge(Registration::factory()->raw(['profession' => 'doctor']), [
            'consent' => '1', 'certificate_id' => (string) Str::uuid(), 'certificate_number' => 'FORGED',
            'certificate_issued_at' => now()->toDateTimeString(), 'certificate_issued_by' => 'attacker',
            'certificate_sent_at' => now()->toDateTimeString(), 'certificate_sent_to' => 'other@example.test',
            'checked_in_at' => now()->toDateTimeString(),
        ]);

        $this->post(route('registrations.store'), $payload)->assertRedirectToRoute('registrations.thanks');

        $registration = Registration::sole();
        foreach (['certificate_id', 'certificate_number', 'certificate_issued_at', 'certificate_issued_by', 'certificate_sent_at', 'certificate_sent_to', 'checked_in_at'] as $field) {
            $this->assertNull($registration->{$field});
        }
        Mail::assertNotSent(ParticipationCertificate::class);
        Storage::disk('certificates')->assertDirectoryEmpty('/');
    }

    public function test_recorded_participation_is_eligible_with_a_legacy_refunded_registration(): void
    {
        $registration = $this->attended(['payment_status' => 'refunded']);
        $this->signIn();
        $this->mock(ParticipationCertificatePdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 certificate');

        $this->post(route('admin.registrations.certificate.send', $registration))->assertSessionHas('status');

        $this->assertNotNull($registration->fresh()->certificate_sent_at);
        Mail::assertSent(ParticipationCertificate::class, 1);
    }

    /** @param array<string, mixed> $overrides */
    private function attended(array $overrides = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'profession' => 'doctor', 'status' => 'confirmed',
            'checked_in_at' => now(), 'checked_in_by' => 'event-admin',
        ], $overrides));
    }

    private function issued(): Registration
    {
        $registration = $this->attended();
        $registration->certificate_id = (string) Str::uuid();
        $registration->certificate_number = 'VX26-PC-'.$registration->reference;
        $registration->certificate_issued_at = now();
        $registration->certificate_issued_by = 'event-admin';
        $registration->save();
        Storage::disk('certificates')->put($registration->certificatePath(), '%PDF-1.4 existing certificate');

        return $registration;
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function editPayload(Registration $registration, array $overrides = []): array
    {
        return array_merge($registration->only(['full_name', 'email', 'phone', 'organisation', 'city', 'state', 'message']), [
            'profession' => $registration->profession->value, 'status' => $registration->status->value, 'admin_notes' => null,
        ], $overrides);
    }

    private function signIn(): void
    {
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
    }
}
