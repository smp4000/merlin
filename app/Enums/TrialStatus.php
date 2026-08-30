<?php

namespace App\Enums;

/**
 * Bildet die einmalig verlängerbare Testphase eines Mandanten ab.
 */
enum TrialStatus: string
{
    case Active = 'active';
    case ExtendedOnce = 'extended_once';
    case ReadOnly = 'read_only';
}
