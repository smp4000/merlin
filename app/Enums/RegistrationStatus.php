<?php

namespace App\Enums;

/**
 * Beschreibt den sicherheitsrelevanten Lebenszyklus einer Partnerregistrierung.
 */
enum RegistrationStatus: string
{
    case EmailPending = 'email_pending';
    case Confirmed = 'confirmed';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
