<?php

namespace App\Enums;

/**
 * Beschreibt den fachlichen Lebenszyklus einer rechtlichen Gesellschaft.
 */
enum LegalEntityStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
}
