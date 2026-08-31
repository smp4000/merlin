<?php

namespace App\Modules\Stations\Application\Exceptions;

use RuntimeException;

/** Meldet eine weiche tenantinterne Dublette, die eine Begründung erfordert. */
final class PotentialStationDuplicateException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Eine ähnliche Tankstelle ist bereits vorhanden.');
    }
}
