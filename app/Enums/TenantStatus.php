<?php

namespace App\Enums;

/**
 * Beschreibt den fachlichen und sicherheitsbezogenen Lebenszyklus eines Mandanten.
 */
enum TenantStatus: string
{
    case Onboarding = 'onboarding';
    case Active = 'active';
    case ReadOnly = 'read_only';
    case ClosureRequested = 'closure_requested';
    case Suspended = 'suspended';
    case Closed = 'closed';

    /**
     * Gibt an, ob eine aktive Membership den Mandanten grundsätzlich öffnen darf.
     */
    public function allowsAccess(): bool
    {
        return match ($this) {
            self::Onboarding, self::Active, self::ReadOnly, self::ClosureRequested => true,
            self::Suspended, self::Closed => false,
        };
    }

    /**
     * Fachliche Änderungen sind nur während Onboarding und aktivem Betrieb erlaubt.
     */
    public function allowsBusinessWrites(): bool
    {
        return match ($this) {
            self::Onboarding, self::Active => true,
            self::ReadOnly, self::ClosureRequested, self::Suspended, self::Closed => false,
        };
    }
}
