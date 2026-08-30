<?php

namespace Tests\Feature\Privacy;

use Tests\TestCase;

/**
 * Prüft die datensparsame Einwilligungsoberfläche auf allen öffentlichen Einstiegsseiten.
 *
 * Die Tests sichern insbesondere ab, dass die Auswahl erreichbar ist und noch keine
 * Drittanbieter-Ressource vor einer späteren, ausdrücklichen Integration ausgeliefert wird.
 */
class PrivacyConsentTest extends TestCase
{
    public function test_landing_page_contains_accessible_privacy_settings_without_third_party_scripts(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Datenschutz-Einstellungen')
            ->assertSee('data-privacy-consent', false)
            ->assertSee('data-privacy-analytics', false)
            ->assertSee('data-privacy-external-media', false)
            ->assertSee('aria-modal="true"', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('google-analytics.com', false)
            ->assertDontSee('youtube.com/embed', false);
    }

    public function test_registration_page_uses_the_same_privacy_settings(): void
    {
        $this->get('/registrieren')
            ->assertOk()
            ->assertSee('data-privacy-consent', false)
            ->assertSee('Nur notwendige')
            ->assertSee('Auswahl speichern')
            ->assertSee('Alle akzeptieren');
    }
}
