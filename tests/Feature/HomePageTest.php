<?php

namespace Tests\Feature;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    #[TestWith(['home'])]
    #[TestWith(['registrations.create'])]
    public function test_public_pages_show_payment_details_before_registration(string $routeName): void
    {
        config(['services.whatsapp.number' => '+919497005735']);

        $this->get(route($routeName))->assertOk()
            ->assertSee('href="#registration-payment"', false)
            ->assertSee('id="registration-payment"', false)
            ->assertSee('css/payment.css')
            ->assertSee('images/visionx-payment-qr.png')
            ->assertSeeText('Rs.500/-')
            ->assertSeeText('Register first to receive your five-digit registration ID.')
            ->assertSeeText('Greetings from DEVI EYE HOSPITAL.')
            ->assertSeeText('WhatsApp (+919497005735)')
            ->assertSeeText('issue your gate pass');
    }

    public function test_program_schedule_is_linked_and_publishes_sessions_and_workshops(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('href="'.route('program-schedule').'"', false)
            ->assertSeeText('Rs.500/-');

        $this->get(route('program-schedule'))->assertOk()
            ->assertSeeText('Gemini Heights Auditorium')
            ->assertSeeTextInOrder(['Inauguration', 'Kishor Kumar K R', 'Dr Anju Mery Thomas', 'Dr Roshni R S', 'Abdul Majeed P', 'Refreshment Break', 'Sreelatha O K', 'Manjulata Devi Sharma', 'Lunch'])
            ->assertSeeTextInOrder(['Hands-on workshops', 'Low Vision Aid', 'OCT', 'Prism', 'A-Scan', 'B-Scan'])
            ->assertSee('href="'.route('registrations.create').'"', false);
    }

    public function test_footer_uses_the_current_year_while_event_date_remains_in_2026(): void
    {
        $this->travelTo(new DateTimeImmutable('2028-02-01 12:00:00'));

        $response = $this->get('/');

        $response->assertOk()
            ->assertSeeText('2028 VISION X. All rights reserved.')
            ->assertSeeText('06 December 2026');
    }

    public function test_homepage_punctuation_and_icons_do_not_contain_encoding_corruption(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertDontSee([
            "\u{00C2}",
            "\u{00E2}\u{20AC}",
            "\u{00E2}\u{2020}",
            "\u{FFFD}",
        ], false);
    }
}
