<?php

namespace App\Enums;

/**
 * Definiert die geschützten Rollen der ersten Partnerverwaltungsstufe.
 *
 * Frei konfigurierbare Rollen werden später über RoleAssignments ergänzt und ersetzen
 * nicht die separat am Mandanten gespeicherte Inhaber-Verantwortung.
 */
enum TenantMembershipRole: string
{
    case Administrator = 'administrator';
}
