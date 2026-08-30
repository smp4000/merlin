<?php

namespace App\Enums;

/**
 * Beschreibt, ob eine Mitgliedschaft aktuell zur Tenant-Auswahl berechtigt.
 */
enum TenantMembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';
    case Ended = 'ended';
}
