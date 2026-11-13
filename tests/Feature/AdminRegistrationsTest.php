<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Mail\RegistrationAcknowledgement;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminRegistrationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42']);
    }

    #[TestWith(['GET', '/admin'])]
    #[TestWith(['GET', '/admin/registrations/export'])]
    #[TestWith(['GET', '/admin/registrations/1'])]
    #[TestWith(['PUT', '/admin/registrations/1'])]
    #[TestWith(['DELETE', '/admin/registrations/1'])]
    #[TestWith(['POST', '/admin/registrations/1/emails'])]
    public function test_every_registration_action_requires_admin_authentication(string $method, string $path): void
    {
        Mail::fake();

        $this->call($method, $path)->assertRedirectToRoute('admin.login');

        $this->assertDatabaseEmpty('registrations');
        Mail::assertNothingSent();
    }

    public function test_dashboard_totals_are_global_while_results_follow_profession_and_search(): void
    {
        $doctor = Registration::factory()->create(['full_name' => 'Dr Arun Nair', 'profession' => 'doctor']);
        $optometrist = Registration::factory()->create(['full_name' => 'Meera Menon', 'profession' => 'optometrist']);
        Registration::factory()->create(['full_name' => 'Biju Thomas', 'profession' => 'optician']);
        $this->signIn();

        $response = $this->get(route('admin.dashboard', ['q' => 'MEERA', 'profession' => 'optometrist']));

        $response->assertOk()->assertSeeText('Meera Menon')->assertDontSeeText('Dr Arun Nair')->assertDontSeeText('Biju Thomas')
            ->assertViewHas('total', 3)->assertViewHas('registrations', fn ($rows) => $rows->total() === 1 && $rows->first()->is($optometrist));
    }

    public function test_status_and_inclusive_date_filters_work_together(): void
    {
        Registration::factory()->create(['full_name' => 'Included Delegate', 'status' => 'reviewed', 'created_at' => '2026-11-13 18:29:59']);
        Registration::factory()->create(['full_name' => 'Earlier Delegate', 'status' => 'reviewed', 'created_at' => '2026-11-12 18:29:59']);
        Registration::factory()->create(['full_name' => 'Other Status', 'status' => 'confirmed', 'created_at' => '2026-11-13 15:00:00']);
        Registration::factory()->create(['full_name' => 'Following Day IST', 'status' => 'reviewed', 'created_at' => '2026-11-13 18:30:00']);
        $this->signIn();

        $this->get(route('admin.dashboard', ['status' => 'reviewed', 'from' => '2026-11-13', 'to' => '2026-11-13']))
            ->assertOk()->assertSeeText('Included Delegate')->assertDontSeeText('Earlier Delegate')->assertDontSeeText('Other Status')->assertDontSeeText('Following Day IST');
        $this->get(route('admin.dashboard', ['to' => '2026-11-13']))->assertOk()->assertViewHas('registrations', fn ($rows) => $rows->total() === 3);
    }

    public function test_search_treats_wildcards_as_literal_text(): void
    {
        Registration::factory()->create(['full_name' => '100% Vision']);
        Registration::factory()->create(['full_name' => 'Other Delegate']);
        $this->signIn();

        $this->get(route('admin.dashboard', ['q' => '%']))->assertOk()->assertSeeText('100% Vision')->assertDontSeeText('Other Delegate');
    }

    #[TestWith(['profession', 'nurse'])]
    #[TestWith(['status', 'unknown'])]
    #[TestWith(['from', 'not-a-date'])]
    public function test_invalid_filters_redirect_to_the_dashboard_with_errors(string $field, string $value): void
    {
        $this->signIn();

        $this->get(route('admin.dashboard', [$field => $value]))->assertRedirectToRoute('admin.dashboard')->assertSessionHasErrors($field);
    }

    public function test_admin_can_update_details_status_and_notes_without_sending_emails(): void
    {
        $registration = Registration::factory()->create(['email' => 'existing@example.test']);
        $this->signIn();
        Mail::fake();
        $payload = $this->payload($registration, ['email' => ' UPDATED@EXAMPLE.TEST ', 'phone' => '+91 (98765) 43210', 'status' => 'confirmed', 'admin_notes' => 'Confirmed by organising team.', 'reference' => 'visitor-controlled']);

        $this->put(route('admin.registrations.update', $registration), $payload)->assertRedirectToRoute('admin.registrations.show', $registration)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'email' => 'updated@example.test', 'phone' => '+919876543210', 'status' => 'confirmed', 'admin_notes' => 'Confirmed by organising team.', 'reference' => $registration->reference]);
        $this->assertNull($registration->fresh()->acknowledgement_sent_at);
        Mail::assertNothingSent();
    }

    public function test_editing_rejects_duplicate_email_and_invalid_status_without_changes(): void
    {
        $registration = Registration::factory()->create(['email' => 'existing@example.test']);
        Registration::factory()->create(['email' => 'duplicate@example.test']);
        $this->signIn();
        Mail::fake();

        $this->put(route('admin.registrations.update', $registration), $this->payload($registration, ['email' => 'DUPLICATE@EXAMPLE.TEST', 'status' => 'invalid']))->assertSessionHasErrors(['email', 'status']);

        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'email' => 'existing@example.test', 'status' => 'new']);
        Mail::assertNothingSent();
    }

    public function test_internal_notes_and_delegate_fields_are_escaped_in_admin_views(): void
    {
        $registration = Registration::factory()->create(['full_name' => '<script>delegate</script>', 'organisation' => '<svg onload=alert(1)>', 'message' => '</textarea><script>message</script>', 'admin_notes' => '</textarea><script>notes</script>']);
        $this->signIn();

        $this->get(route('admin.dashboard'))->assertOk()->assertSee($registration->full_name)->assertDontSee($registration->full_name, false);
        $this->get(route('admin.registrations.show', $registration))->assertOk()->assertSee($registration->message)->assertSee($registration->admin_notes)
            ->assertDontSee($registration->admin_notes, false)->assertDontSee($registration->organisation, false);
    }

    public function test_admin_can_send_only_pending_registration_emails(): void
    {
        $registration = Registration::factory()->create(['notification_sent_at' => now()]);
        $this->signIn();
        config(['mail.default' => 'smtp']);
        Mail::fake();

        $this->post(route('admin.registrations.emails', $registration))->assertRedirectToRoute('admin.registrations.show', $registration);
        $this->post(route('admin.registrations.emails', $registration))->assertRedirectToRoute('admin.registrations.show', $registration);

        $this->assertNotNull($registration->fresh()->acknowledgement_sent_at);
        Mail::assertSent(RegistrationAcknowledgement::class, 1);
        Mail::assertSentCount(1);
    }

    public function test_dashboard_paginates_without_loading_all_records(): void
    {
        Registration::factory()->count(16)->create();
        $this->signIn();

        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('registrations', fn ($rows) => $rows->total() === 16 && $rows->count() === 15 && $rows->hasMorePages());
    }

    public function test_admin_can_delete_only_the_confirmed_record_and_keep_active_filters(): void
    {
        $registration = Registration::factory()->create(['full_name' => 'Arun Menon', 'profession' => 'optometrist']);
        $other = Registration::factory()->create();
        $this->signIn();
        Mail::fake();

        $this->delete(route('admin.registrations.destroy', ['registration' => $registration, 'profession' => 'optometrist', 'q' => 'Arun']), ['confirm_delete' => '1'])
            ->assertRedirectToRoute('admin.dashboard', ['q' => 'Arun', 'profession' => 'optometrist'])
            ->assertSessionHas('status', 'Registration for Arun Menon was deleted.');

        $this->assertModelMissing($registration);
        $this->assertModelExists($other);
        $this->assertDatabaseCount('registrations', 1);
        Mail::assertNothingSent();
    }

    #[TestWith([[]])]
    #[TestWith([['confirm_delete' => '0']])]
    public function test_deletion_requires_explicit_confirmation(array $payload): void
    {
        $registration = Registration::factory()->create();
        $this->signIn();
        Mail::fake();

        $this->delete(route('admin.registrations.destroy', $registration), $payload)
            ->assertRedirectToRoute('admin.dashboard')->assertSessionHasErrors(['confirm_delete' => 'Please confirm the registration deletion.']);

        $this->assertModelExists($registration);
        Mail::assertNothingSent();
    }

    public function test_public_site_user_cannot_delete_a_registration(): void
    {
        $registration = Registration::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('admin.registrations.destroy', $registration), ['confirm_delete' => '1'])->assertRedirectToRoute('admin.login');

        $this->assertModelExists($registration);
    }

    public function test_deleting_an_already_missing_registration_returns_not_found(): void
    {
        $this->signIn();

        $this->delete(route('admin.registrations.destroy', 999999), ['confirm_delete' => '1'])->assertNotFound();
    }

    public function test_delete_buttons_identify_the_delegate_and_render_a_shared_confirmation_dialog(): void
    {
        $registration = Registration::factory()->create(['full_name' => '<script>delegate</script>']);
        $this->signIn();

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-delete-registration', false)
            ->assertSee('data-delegate-name="&lt;script&gt;delegate&lt;/script&gt;"', false)
            ->assertSee('Delete this registration?')->assertSee('Keep registration')
            ->assertSee('name="confirm_delete" value="1"', false)->assertSee('This action cannot be undone.');
        $this->get(route('admin.registrations.show', $registration))->assertOk()->assertSee('Remove registration')->assertSee('Delete this registration?');
    }

    public function test_public_registration_cannot_set_admin_status_or_notes(): void
    {
        Mail::fake();
        $payload = Registration::factory()->raw(['profession' => 'doctor', 'email' => 'newdelegate@example.test']);

        $this->post(route('registrations.store'), array_merge($payload, ['consent' => '1', 'status' => 'confirmed', 'admin_notes' => 'Untrusted notes']))
            ->assertRedirectToRoute('registrations.thanks');

        $this->assertDatabaseHas('registrations', ['email' => 'newdelegate@example.test', 'status' => 'new', 'admin_notes' => null]);
    }

    private function signIn(): void
    {
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(Registration $registration, array $overrides = []): array
    {
        return array_replace($registration->only(['full_name', 'email', 'phone', 'organisation', 'city', 'state', 'message']), ['profession' => $registration->profession->value, 'status' => 'reviewed', 'admin_notes' => null], $overrides);
    }
}
