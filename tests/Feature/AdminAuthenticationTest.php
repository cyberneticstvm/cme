<?php

namespace Tests\Feature;

use App\Auth\EnvironmentAdminProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'event-admin', 'admin.password' => 'Simple-Test#42', 'admin.session_timeout' => 30]);
    }

    public function test_login_page_is_branded_private_and_never_exposes_the_configured_password(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSeeText('Welcome back.')
            ->assertDontSee('Simple-Test#42')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_valid_environment_credentials_start_an_admin_session_and_rotate_the_session_id(): void
    {
        $this->get(route('admin.login'));
        $before = session()->getId();

        $this->post(route('admin.login.store'), ['username' => 'event-admin', 'password' => 'Simple-Test#42'])
            ->assertRedirectToRoute('admin.dashboard')->assertSessionHasNoErrors()->assertSessionHas('admin_last_activity');

        $this->assertAuthenticated('admin');
        $this->assertNotSame($before, session()->getId());
        $this->assertGuest('web');
        $this->get(route('admin.dashboard'))->assertOk()->assertSeeText('Your delegate community');
    }

    public function test_invalid_credentials_show_a_generic_error_and_do_not_flash_the_password(): void
    {
        $this->from(route('admin.login'))->post(route('admin.login.store'), ['username' => 'wrong-user', 'password' => 'wrong-password'])
            ->assertRedirectToRoute('admin.login')->assertSessionHasErrors(['username' => 'The username or password is incorrect.'])
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest('admin');
    }

    public function test_unconfigured_credentials_cannot_authenticate_even_with_blank_values(): void
    {
        config(['admin.username' => '', 'admin.password' => '']);

        $this->post(route('admin.login.store'), ['username' => 'admin', 'password' => 'password'])->assertSessionHasErrors('username');

        $this->assertGuest('admin');
    }

    public function test_malformed_username_input_returns_a_usable_login_page(): void
    {
        $this->from(route('admin.login'))->post(route('admin.login.store'), ['username' => ['unexpected'], 'password' => 'wrong'])
            ->assertRedirectToRoute('admin.login')->assertSessionHasErrors('username');

        $this->get(route('admin.login'))->assertOk()->assertSeeText('Welcome back.');
        $this->assertGuest('admin');
    }

    public function test_login_attempts_are_rate_limited_with_a_branded_error(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.store'), ['username' => 'event-admin', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }

        $this->post(route('admin.login.store'), ['username' => 'event-admin', 'password' => 'Simple-Test#42'])
            ->assertTooManyRequests()->assertSeeText('Too many sign-in attempts.');

        $this->assertGuest('admin');
    }

    public function test_a_public_site_user_cannot_enter_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
    }

    public function test_inactive_admin_sessions_expire(): void
    {
        $user = app(EnvironmentAdminProvider::class)->retrieveById(app(EnvironmentAdminProvider::class)->identifier());
        $this->actingAs($user, 'admin')->withSession(['admin_last_activity' => now()->subMinutes(31)->timestamp]);

        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login')->assertSessionHas('status', 'Your session expired. Please sign in again.');

        $this->assertGuest('admin');
    }

    public function test_changing_environment_credentials_invalidates_existing_sessions(): void
    {
        $provider = app(EnvironmentAdminProvider::class);
        $this->actingAs($provider->retrieveById($provider->identifier()), 'admin')->withSession(['admin_last_activity' => now()->timestamp]);
        config(['admin.password' => 'Replacement-Test#52']);

        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');

        $this->assertGuest('admin');
    }

    public function test_logout_invalidates_the_session_and_protected_access(): void
    {
        $this->post(route('admin.login.store'), ['username' => 'event-admin', 'password' => 'Simple-Test#42'])->assertRedirectToRoute('admin.dashboard');

        $this->post(route('admin.logout'))->assertRedirectToRoute('admin.login')->assertSessionMissing('admin_last_activity');

        $this->assertGuest('admin');
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
    }
}
