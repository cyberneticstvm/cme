<?php

namespace Tests\Feature;

use App\Mail\RegistrationAcknowledgement;
use App\Mail\RegistrationNotification;
use App\Models\Registration;
use App\PaymentStatus;
use App\RegistrationStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'smtp', 'services.registration.receiver_email' => 'organiser@example.test']);
    }

    public function test_page_is_public_and_homepage_registration_links_open_the_form(): void
    {
        $this->get(route('registrations.create'))->assertOk()->assertSee('Your delegate details')->assertSee('name="_token"', false)->assertSeeText('Rs.500/-');
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('registrations.create').'"', false)->assertDontSee('Registration opening soon');
    }

    #[TestWith(['doctor'])]
    #[TestWith(['optometrist'])]
    #[TestWith(['optician'])]
    public function test_each_profession_can_register_and_receives_both_emails(string $profession): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfSecond());
        $payload = $this->payload(['profession' => $profession, 'email' => ' ARUN@EXAMPLE.TEST ', 'phone' => '+91 (94970) 05735', 'reference' => 'visitor-supplied', 'notification_sent_at' => now()]);

        $response = $this->post(route('registrations.store'), $payload);

        $response->assertRedirectToRoute('registrations.thanks')->assertSessionHasNoErrors()->assertSessionHas('registration_receipt.email', 'arun@example.test');
        $this->assertDatabaseHas('registrations', ['full_name' => 'Dr Arun Nair', 'profession' => $profession, 'email' => 'arun@example.test', 'phone' => '+919497005735', 'organisation' => 'Vision Clinic', 'city' => 'Kollam', 'state' => 'Kerala', 'message' => 'Please share the programme.']);
        $registration = Registration::sole();
        $this->assertMatchesRegularExpression('/^[1-9][0-9]{4}$/', $registration->reference);
        $this->assertNotSame('visitor-supplied', $registration->reference);
        $this->assertTrue($registration->consented_at->equalTo(now()));
        $this->assertNotNull($registration->acknowledgement_sent_at);
        $this->assertNotNull($registration->notification_sent_at);
        Mail::assertSent(RegistrationAcknowledgement::class, fn ($mail) => $mail->hasTo('arun@example.test') && $mail->registration->is($registration));
        Mail::assertSent(RegistrationNotification::class, fn ($mail) => $mail->hasTo('organiser@example.test') && $mail->registration->is($registration));
        Mail::assertSentCount(2);
        $this->get(route('registrations.thanks'))->assertOk()
            ->assertSeeText($registration->reference)->assertSeeText('arun@example.test')
            ->assertSeeText('Rs.500/-')->assertSee('Devi Eye Hospital payment QR code')
            ->assertDontSeeText('Your email acknowledgement is pending')->assertDontSee('@else');
    }

    public function test_signed_payment_page_prefills_the_merchant_fee_and_registration_id(): void
    {
        config(['services.whatsapp.number' => '+919497005735']);
        $registration = Registration::factory()->create(['full_name' => 'Private Delegate']);
        $url = URL::signedRoute('registrations.payment', ['reference' => $registration->reference]);

        $this->get($url)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('href="upi://pay?', false)
            ->assertSee('href="gpay://upi/pay?', false)
            ->assertSee('pa=vyapar.175693565052%40hdfcbank', false)
            ->assertSee('tn='.$registration->reference.'&amp;am=500.00&amp;cu=INR', false)
            ->assertSeeText('WhatsApp (+919497005735)')
            ->assertSee('Devi Eye Hospital payment QR code')
            ->assertDontSee($registration->full_name)
            ->assertDontSee($registration->email);
    }

    public function test_an_unsigned_payment_link_cannot_open_the_payment_page(): void
    {
        $registration = Registration::factory()->create();

        $this->get(route('registrations.payment', ['reference' => $registration->reference]))
            ->assertForbidden();
    }

    #[TestWith([PaymentStatus::Confirmed, RegistrationStatus::Confirmed])]
    #[TestWith([PaymentStatus::Refunded, RegistrationStatus::Confirmed])]
    #[TestWith([PaymentStatus::Pending, RegistrationStatus::Cancelled])]
    public function test_payment_page_hides_payment_options_after_resolution(PaymentStatus $paymentStatus, RegistrationStatus $registrationStatus): void
    {
        $registration = Registration::factory()->create(['payment_status' => $paymentStatus, 'status' => $registrationStatus]);

        $this->get(URL::signedRoute('registrations.payment', ['reference' => $registration->reference]))
            ->assertOk()
            ->assertDontSee('Devi Eye Hospital payment QR code')
            ->assertDontSee('data-upi-payment');
    }

    public function test_missing_required_details_do_not_save_or_send_emails(): void
    {
        Mail::fake();

        $response = $this->from(route('registrations.create'))->post(route('registrations.store'), []);

        $response->assertRedirectToRoute('registrations.create')->assertSessionHasErrors(['full_name', 'profession', 'email', 'phone', 'city', 'state', 'consent']);
        $this->assertDatabaseEmpty('registrations');
        Mail::assertNothingSent();
    }

    #[TestWith(['profession', 'nurse', 'Please choose Doctor, Optometrist or Optician.'])]
    #[TestWith(['email', 'invalid-email', 'The email field must be a valid email address.'])]
    #[TestWith(['phone', '9497letters005735', 'Enter a valid mobile number with 10 to 15 digits, including the country code if applicable.'])]
    #[TestWith(['phone', '123', 'Enter a valid mobile number with 10 to 15 digits, including the country code if applicable.'])]
    #[TestWith(['phone', '+1234567890123456', 'Enter a valid mobile number with 10 to 15 digits, including the country code if applicable.'])]
    #[TestWith(['consent', '0', 'Please agree to the use of your details for your registration and event updates.'])]
    #[TestWith(['website', 'https://spam.example', 'We could not accept this submission. Please leave the website field empty.'])]
    public function test_invalid_details_are_rejected_without_side_effects(string $field, string $value, string $message): void
    {
        Mail::fake();

        $response = $this->post(route('registrations.store'), $this->payload([$field => $value]));

        $response->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseEmpty('registrations');
        Mail::assertNothingSent();
    }

    #[TestWith(['full_name', 121])]
    #[TestWith(['organisation', 181])]
    #[TestWith(['city', 101])]
    #[TestWith(['state', 101])]
    #[TestWith(['message', 1501])]
    public function test_text_fields_have_reasonable_length_limits(string $field, int $length): void
    {
        Mail::fake();

        $response = $this->post(route('registrations.store'), $this->payload([$field => str_repeat('a', $length)]));

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseEmpty('registrations');
        Mail::assertNothingSent();
    }

    public function test_duplicate_email_is_case_insensitive_and_does_not_send_again(): void
    {
        Registration::factory()->create(['email' => 'arun@example.test']);
        Mail::fake();

        $response = $this->post(route('registrations.store'), $this->payload(['email' => 'ARUN@EXAMPLE.TEST']));

        $response->assertSessionHasErrors(['email' => 'This email is already registered for VISION X 2026. Use the Forgot registration ID option to retrieve your existing ID.']);
        $this->assertDatabaseCount('registrations', 1);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_keeps_registration_saved_and_still_attempts_the_organiser_email(): void
    {
        $exception = new RuntimeException('SMTP unavailable');
        Exceptions::fake();
        $notification = Mockery::mock(PendingMail::class);
        $notification->shouldReceive('send')->once()->with(Mockery::type(RegistrationNotification::class));
        Mail::shouldReceive('to')->once()->with('arun@example.test', 'Dr Arun Nair')->andThrow($exception);
        Mail::shouldReceive('to')->once()->with('organiser@example.test', 'VISION X Organising Team')->andReturn($notification);

        $response = $this->post(route('registrations.store'), $this->payload());

        $response->assertRedirectToRoute('registrations.thanks')->assertSessionHas('registration_receipt.email_sent', false);
        $registration = Registration::sole();
        $this->assertNull($registration->acknowledgement_sent_at);
        $this->assertNotNull($registration->notification_sent_at);
        Exceptions::assertReported(RuntimeException::class);
        $this->get(route('registrations.thanks'))->assertOk()
            ->assertSeeText('Your email acknowledgement is pending')
            ->assertDontSeeText('Please check your inbox and spam folder.');
    }

    public function test_missing_receiver_remains_pending_and_retry_sends_only_the_missing_email(): void
    {
        Mail::fake();
        config(['services.registration.receiver_email' => '']);
        $this->post(route('registrations.store'), $this->payload())->assertRedirectToRoute('registrations.thanks');
        $registration = Registration::sole();
        $this->assertNotNull($registration->acknowledgement_sent_at);
        $this->assertNull($registration->notification_sent_at);
        config(['services.registration.receiver_email' => 'organiser@example.test']);

        $this->artisan('registrations:retry-emails')->assertSuccessful();
        $this->artisan('registrations:retry-emails')->assertSuccessful();

        $this->assertNotNull($registration->fresh()->notification_sent_at);
        Mail::assertSent(RegistrationAcknowledgement::class, 1);
        Mail::assertSent(RegistrationNotification::class, 1);
        Mail::assertSentCount(2);
    }

    public function test_log_transport_does_not_claim_emails_were_delivered(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

        $this->post(route('registrations.store'), $this->payload())->assertRedirectToRoute('registrations.thanks')->assertSessionHas('registration_receipt.email_sent', false);

        $registration = Registration::sole();
        $this->assertNull($registration->acknowledgement_sent_at);
        $this->assertNull($registration->notification_sent_at);
        Mail::assertNothingSent();
        $this->artisan('registrations:retry-emails')->assertFailed();
    }

    public function test_receipt_cannot_be_viewed_without_the_visitors_session(): void
    {
        $this->get(route('registrations.thanks'))->assertRedirectToRoute('registrations.create');
    }

    public function test_submission_endpoint_is_rate_limited(): void
    {
        Mail::fake();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('registrations.store'), [])->assertSessionHasErrors();
        }

        $this->post(route('registrations.store'), $this->payload())->assertTooManyRequests();

        $this->assertDatabaseEmpty('registrations');
        Mail::assertNothingSent();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace(Registration::factory()->raw([
            'full_name' => 'Dr Arun Nair', 'profession' => 'doctor', 'email' => 'arun@example.test',
            'phone' => '+919497005735', 'organisation' => 'Vision Clinic', 'city' => 'Kollam',
            'state' => 'Kerala', 'message' => 'Please share the programme.',
        ]), ['consent' => '1'], $overrides);
    }
}
