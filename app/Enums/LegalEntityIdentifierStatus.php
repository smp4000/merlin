<?php

namespace App\Enums;

/**
 * Erhält historische Kennungen, ohne sie für aktuelle Vorgänge weiterzuverwenden.
 */
enum LegalEntityIdentifierStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
