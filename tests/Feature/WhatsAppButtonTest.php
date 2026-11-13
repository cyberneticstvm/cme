<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppButtonTest extends TestCase
{
    public function test_configured_number_opens_the_correct_chat_with_an_encoded_message(): void
    {
        config([
            'services.whatsapp.number' => '+91 (94970) 05735',
            'services.whatsapp.message' => 'Hello! CME fees & registration?',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('href="https://wa.me/919497005735?text=Hello%21%20CME%20fees%20%26%20registration%3F"', false)
            ->assertSee('Chat with the VISION X team on WhatsApp');
    }

    public function test_whatsapp_button_is_hidden_without_a_contact_number(): void
    {
        config(['services.whatsapp.number' => '']);

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('https://wa.me/', false);
    }

    public function test_invalid_contact_number_does_not_render_a_broken_chat_link(): void
    {
        config(['services.whatsapp.number' => 'not-a-phone-number']);

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('https://wa.me/', false);
    }
}
