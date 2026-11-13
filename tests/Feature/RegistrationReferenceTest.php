<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationReferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_registrations_receive_unique_five_digit_ids(): void
    {
        $registrations = Registration::factory()->count(25)->create();

        $ids = $registrations->pluck('reference')->all();

        $this->assertSame(25, count(array_unique($ids)));
        $this->assertSame('10000', $ids[0]);
        $this->assertSame('10024', $ids[24]);
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^[1-9][0-9]{4}$/', $id);
        }
    }

    public function test_deleting_a_registration_does_not_reuse_its_id(): void
    {
        $first = Registration::factory()->create();
        $first->delete();

        $next = Registration::factory()->create();

        $this->assertSame('10000', $first->reference);
        $this->assertSame('10001', $next->reference);
    }

    public function test_editing_details_preserves_the_issued_id(): void
    {
        $registration = Registration::factory()->create();
        $original = $registration->reference;

        $registration->update(['full_name' => 'Updated Delegate']);

        $this->assertSame($original, $registration->fresh()->reference);
    }

    public function test_capacity_limit_never_issues_a_six_digit_id_or_sends_a_false_acknowledgement(): void
    {
        DB::table('registration_sequences')->where('name', 'visionx-2026')->update(['last_number' => 99998]);
        $last = Registration::factory()->create();
        Mail::fake();
        $payload = array_merge(Registration::factory()->raw(['profession' => 'doctor']), ['consent' => '1']);

        $this->post(route('registrations.store'), $payload)->assertSessionHasErrors('registration');

        $this->assertSame('99999', $last->reference);
        $this->assertDatabaseCount('registrations', 1);
        $this->assertDatabaseHas('registration_sequences', ['name' => 'visionx-2026', 'last_number' => 99999]);
        Mail::assertNothingSent();
    }
}
