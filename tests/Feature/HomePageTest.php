<?php

namespace Tests\Feature;

use DateTimeImmutable;
use Tests\TestCase;

class HomePageTest extends TestCase
{
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
