<?php

namespace App\Modules\Stations\Application\Exceptions;

use RuntimeException;
use Throwable;

/** Meldet einen technischen Ausfall der optionalen Suche ohne Providerdetails offenzulegen. */
final class StationSearchUnavailableException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Die Tankstellensuche ist momentan nicht verfügbar.', previous: $previous);
    }
}
