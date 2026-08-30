<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Zeigt den Merlin-Anmeldeeinstieg mit eigener, übersetzbarer Produktansprache.
 *
 * Die Klasse verändert keine Authentifizierungslogik von Filament. Passwortprüfung,
 * Sitzungsaufbau und Rate-Limiting verbleiben vollständig beim Framework.
 */
final class Login extends BaseLogin
{
    /**
     * Liefert den übersetzten Seitentitel für Browser und assistive Technologien.
     */
    public function getTitle(): string|Htmlable
    {
        return __('merlin.auth.login.title');
    }

    /**
     * Liefert die zentrale Handlungsaufforderung des Anmeldeformulars.
     */
    public function getHeading(): string|Htmlable
    {
        return __('merlin.auth.login.heading');
    }

    /**
     * Beschreibt knapp, welchen geschützten Bereich die Anmeldung öffnet.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return __('merlin.auth.login.subheading');
    }
}
