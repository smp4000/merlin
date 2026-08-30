<?php

namespace App\Modules\Registration\Application\Data;

use App\Enums\TenantType;

/**
 * Transportiert die vom Plattform-Admin freigegebenen Daten einer Owner-Einladung.
 */
final readonly class InvitePartnerData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $partnerDisplayName,
        public TenantType $tenantType,
        public string $countryCode,
        public string $locale,
        public string $correlationId,
    ) {}
}
