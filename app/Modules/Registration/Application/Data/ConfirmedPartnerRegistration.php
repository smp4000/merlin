<?php

namespace App\Modules\Registration\Application\Data;

use App\Models\Tenant;
use App\Models\User;

/**
 * Liefert das Ergebnis einer erstmalig erfolgreichen Registrierungsbestätigung.
 */
final readonly class ConfirmedPartnerRegistration
{
    public function __construct(
        public User $user,
        public Tenant $tenant,
    ) {}
}
