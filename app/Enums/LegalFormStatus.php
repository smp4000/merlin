<?php

namespace App\Enums;

/**
 * Steuert, ob eine Rechtsform weiterhin für neue Gesellschaften gewählt werden darf.
 */
enum LegalFormStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
