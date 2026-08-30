<?php

namespace App\Enums;

/**
 * Kennzeichnet, wer einen Partnerregistrierungsvorgang fachlich angestoßen hat.
 *
 * Die Quelle steuert keine Berechtigung. Sie dient der nachvollziehbaren Darstellung
 * und entscheidet, ob rechtliche Bestätigungen erst durch den eingeladenen Owner
 * erbracht werden müssen.
 */
enum RegistrationSource: string
{
    case SelfService = 'self_service';
    case PlatformInvitation = 'platform_invitation';
}
