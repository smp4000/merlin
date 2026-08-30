<?php

namespace App\Modules\Registration\Application\Data;

use App\Enums\TenantType;

/**
 * Transportiert ausschließlich die freigegebenen datenarmen Registrierungsfelder.
 */
final readonly class StartPartnerRegistrationData
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
        public bool $termsAccepted,
        public string $termsVersion,
        public string $termsDigest,
        public bool $privacyAcknowledged,
        public string $privacyVersion,
        public string $privacyDigest,
    ) {}
}
