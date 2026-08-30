<?php

namespace App\Modules\Tenants\Application\Data;

use App\Enums\TenantType;

/**
 * Transportiert bereits validierte Daten zur atomaren Mandantenanlage.
 */
final readonly class CreateTenantData
{
    public function __construct(
        public string $displayName,
        public TenantType $type,
        public string $countryCode = 'DE',
        public string $defaultLocale = 'de',
        public string $timezone = 'Europe/Berlin',
    ) {}
}
