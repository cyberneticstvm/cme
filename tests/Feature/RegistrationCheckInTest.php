<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Mail\DelegatePass;
use App\Models\Registration;
use App\Models\User;
use App\Services\DelegatePassPdf;
use App\Services\PassQrCode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class RegistrationCheckInTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Test-Only#2026', 'mail.default' => 'smtp']);
    }

    #[TestWith(['GET', '/admin/check-in'])]
    #[TestWith(['POST', '/admin/check-in/verify'])]
    #[TestWith(['POST', '/admin/check-in'])]
    public function test_check_in_endpoints_require_an_admin_session(string $method, string $path): void
    {
        $this->call($method, $path)->assertRedirectToRoute('admin.login');
    }

    public function test_an_ordinary_user_cannot_use_the_check_in_desk(): void
    {
        $registration = $this->issued();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.check-in.store'), ['code' => $this->code($registration)])->assertRedirectToRoute('admin.login');
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_verification_shows_valid_details_without_recording_attendance_or_sending_mail(): void
    {
        Mail::fake();
        $registration = $this->issued();
        $this->signIn();
        $this->postJson(route('admin.check-in.verify'), ['code' => $this->code($registration)])
            ->assertOk()->assertJsonPath('state', 'ready')->assertJsonPath('can_check_in', true)
            ->assertJsonPath('registration.reference', $registration->reference)->assertJsonPath('registration.full_name', $registration->full_name)
            ->assertJsonMissingPath('registration.payment_status')->assertJsonMissingPath('registration.pass_id')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->get(route('admin.check-in.index', ['code' => $this->code($registration)]))->assertOk()->assertSee('Verify &amp; welcome', false);
        $this->assertNull($registration->fresh()->checked_in_at);
        Mail::assertNothingSent();
    }

    public function test_attendance_is_recorded_once_with_server_time_and_admin_identity(): void
    {
        $registration = $this->issued();
        $other = $this->issued();
        $this->signIn();
        $this->travelTo(now()->startOfSecond());
        $payload = ['code' => $this->code($registration), 'registration_id' => $other->id, 'checked_in_at' => '2020-01-01', 'checked_in_by' => 'attacker'];
        $this->postJson(route('admin.check-in.store'), $payload)->assertOk()->assertJsonPath('state', 'checked_in')->assertJsonPath('can_check_in', false)->assertJsonPath('checked_in_total', 1);
        $registration->refresh();
        $this->assertTrue($registration->checked_in_at->equalTo(now()));
        $this->assertSame('event-admin', $registration->checked_in_by);
        $this->assertSame($registration->pass_id, $registration->checked_in_pass_id);
        $this->assertNull($other->fresh()->checked_in_at);
        $firstCheckIn = $registration->checked_in_at;
        $this->travel(5)->minutes();
        $this->postJson(route('admin.check-in.verify'), ['code' => $this->code($registration)])->assertJsonPath('state', 'already_checked_in')->assertJsonPath('can_check_in', false);
        $this->postJson(route('admin.check-in.store'), $payload)->assertConflict()->assertJsonPath('state', 'already_checked_in');
        $this->assertTrue($registration->fresh()->checked_in_at->equalTo($firstCheckIn));
    }

    #[TestWith(['cancelled', 1, true, 'cancelled'])]
    #[TestWith(['new', 0, true, 'invalid'])]
    #[TestWith(['new', 1, false, 'invalid'])]
    public function test_cancelled_registrations_and_invalid_passes_cannot_check_in(string $status, int $version, bool $issued, string $state): void
    {
        $registration = $this->issued(['status' => $status, 'pass_qr_version' => $version, 'pass_issued_at' => $issued ? now() : null]);
        $this->signIn();
        $payload = ['code' => $this->code($registration)];
        $this->postJson(route('admin.check-in.verify'), $payload)->assertOk()->assertJsonPath('state', $state)->assertJsonPath('can_check_in', false);
        $this->postJson(route('admin.check-in.store'), $payload)->assertConflict()->assertJsonPath('state', $state);
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    #[TestWith(['12345'])]
    #[TestWith(['https://example.test/other-event'])]
    #[TestWith(['VISIONX2026:2:d1e39c1a-c5c5-4776-a3b9-f03c04a66276'])]
    #[TestWith(['VISIONX2026:1:d1e39c1a-c5c5-4776-a3b9-f03c04a66276'])]
    public function test_unknown_or_malformed_codes_do_not_reveal_a_registration(string $code): void
    {
        $this->issued();
        $this->signIn();
        $this->postJson(route('admin.check-in.verify'), ['code' => $code])->assertOk()->assertJsonPath('state', 'invalid')->assertJsonPath('registration', null);
        $this->postJson(route('admin.check-in.store'), ['code' => $code])->assertConflict()->assertJsonPath('registration', null);
        $this->assertSame(0, Registration::whereNotNull('checked_in_at')->count());
    }

    public function test_invalid_request_shapes_do_not_mark_attendance(): void
    {
        $registration = $this->issued();
        $this->signIn();
        foreach ([null, ['array'], str_repeat('x', 101)] as $code) {
            $this->postJson(route('admin.check-in.store'), ['code' => $code])->assertUnprocessable()->assertJsonValidationErrors('code');
        }
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_a_cancellation_between_verification_and_confirmation_blocks_entry(): void
    {
        Storage::fake('passes');
        $registration = $this->issued();
        $code = $this->code($registration);
        $this->signIn();
        $this->postJson(route('admin.check-in.verify'), ['code' => $code])->assertJsonPath('state', 'ready');
        Registration::whereKey($registration->id)->update(['status' => 'cancelled']);
        $this->postJson(route('admin.check-in.store'), ['code' => $code])->assertConflict()->assertJsonPath('state', 'cancelled');
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_replacing_a_pass_never_resets_attendance_or_allows_a_second_entry(): void
    {
        Storage::fake('passes');
        Mail::fake();
        $registration = $this->issued();
        $oldCode = $this->code($registration);
        $oldId = $registration->pass_id;
        $this->signIn();
        $this->postJson(route('admin.check-in.store'), ['code' => $oldCode])->assertOk();
        $checkInTime = $registration->fresh()->checked_in_at;
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 test');
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHasNoErrors();
        $registration->refresh();
        $this->assertNotSame($oldId, $registration->pass_id);
        $this->assertSame($oldId, $registration->checked_in_pass_id);
        $this->assertTrue($registration->checked_in_at->equalTo($checkInTime));
        $this->postJson(route('admin.check-in.store'), ['code' => $oldCode])->assertConflict()->assertJsonPath('state', 'invalid');
        $this->postJson(route('admin.check-in.store'), ['code' => $this->code($registration)])->assertConflict()->assertJsonPath('state', 'already_checked_in');
        Mail::assertSent(DelegatePass::class, fn ($mail) => $mail->hasTo($registration->email));
    }

    public function test_legacy_pdf_is_replaced_with_a_new_qr_pass_when_emailed(): void
    {
        Storage::fake('passes');
        Mail::fake();
        $registration = $this->issued(['pass_qr_version' => 0]);
        $oldPath = $registration->passPath();
        $oldCode = $this->code($registration);
        Storage::disk('passes')->put($oldPath, '%PDF-1.4 legacy');
        $this->mock(DelegatePassPdf::class)->shouldReceive('render')->once()->andReturn('%PDF-1.4 updated');
        $this->signIn();
        $this->post(route('admin.registrations.pass.send', $registration))->assertSessionHasNoErrors();
        $registration->refresh();
        $this->assertSame(1, $registration->pass_qr_version);
        Storage::disk('passes')->assertMissing($oldPath);
        Storage::disk('passes')->assertExists($registration->passPath());
        $this->postJson(route('admin.check-in.verify'), ['code' => $oldCode])->assertJsonPath('state', 'invalid');
        $this->postJson(route('admin.check-in.verify'), ['code' => $this->code($registration)])->assertJsonPath('state', 'ready');
        Mail::assertSent(DelegatePass::class);
    }

    public function test_qr_payload_contains_only_the_random_pass_identifier_and_encodes_as_png(): void
    {
        $registration = $this->issued();
        $qr = app(PassQrCode::class);
        $this->assertSame('VISIONX2026:1:'.$registration->pass_id, $qr->payload($registration->pass_id));
        $this->assertSame($registration->pass_id, $qr->token('  '.strtoupper($this->code($registration)).'  '));
        $this->assertSame($registration->pass_id, $qr->token($registration->pass_id));
        $uri = $qr->dataUri($registration->pass_id);
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", base64_decode(substr($uri, 22)));
        $this->assertStringNotContainsString($registration->email, $this->code($registration));
    }

    public function test_attendance_is_visible_in_admin_and_filters_and_excel_reports(): void
    {
        $registration = $this->issued(['full_name' => 'Checked Delegate', 'checked_in_at' => '2026-12-06 03:30:00', 'checked_in_by' => 'door-admin']);
        $this->issued(['full_name' => 'Not Arrived Delegate']);
        $this->signIn();
        $this->get(route('admin.dashboard', ['attendance' => 'checked_in']))->assertOk()->assertSee('Checked Delegate')->assertDontSee('Not Arrived Delegate');
        $this->get(route('admin.dashboard', ['attendance' => 'not_checked_in']))->assertOk()->assertSee('Not Arrived Delegate')->assertDontSee('Checked Delegate');
        $this->get(route('admin.check-in.index'))->assertOk()->assertSee('Checked Delegate')->assertSee('data-checked-in-total', false);
        $this->get(route('admin.registrations.show', $registration))->assertOk()->assertSee('door-admin');
        $response = $this->get(route('admin.registrations.export', ['attendance' => 'checked_in']));
        $response->assertOk()->assertDownload();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('Checked Delegate', $xml);
            $this->assertStringNotContainsString('Not Arrived Delegate', $xml);
            $this->assertStringContainsString('door-admin', $xml);
            $this->assertStringContainsString('2026-12-06 09:00:00', $xml);
            $zip->close();
        } finally {
            unset($zip);
            unlink($path);
        }
    }

    public function test_untrusted_recent_delegate_names_are_escaped(): void
    {
        $this->issued(['full_name' => '<script>delegate</script>', 'checked_in_at' => now()]);
        $this->signIn();
        $this->get(route('admin.check-in.index'))->assertOk()->assertSee('&lt;script&gt;delegate&lt;/script&gt;', false)->assertDontSee('<script>delegate</script>', false);
    }

    private function issued(array $attributes = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'status' => 'new',
            'pass_id' => (string) Str::uuid(), 'pass_qr_version' => 1, 'pass_issued_at' => now(),
        ], $attributes));
    }

    private function code(Registration $registration): string
    {
        return 'VISIONX2026:1:'.$registration->pass_id;
    }

    private function signIn(): void
    {
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
    }
}
