<?php

namespace App\Foundation\Tenancy\Exceptions;

use RuntimeException;

/**
 * Signalisiert, dass eine fachliche Änderung wegen des Tenant-Lifecycles gesperrt ist.
 */
final class TenantReadOnlyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Der Mandant befindet sich im Nur-Lesen-Modus.');
    }
}
