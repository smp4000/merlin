<?php

namespace App\Modules\Stations\Application\Data;

/**
 * Beschreibt bestätigte Stationsstammdaten ohne eine vom Browser übergebene Tenant-ID.
 */
final readonly class CreateStationData
{
    public function __construct(
        public string $legalEntityPublicId,
        public ?int $brandId,
        public string $name,
        public ?string $shortName,
        public string $street,
        public string $houseNumber,
        public ?string $addressAddition,
        public string $postalCode,
        public string $city,
        public string $region,
        public string $countryCode,
        public string $timezone,
        public string $defaultLocale,
        public string $sourceType,
        public ?string $providerKey = null,
        public ?string $externalStationId = null,
        public ?string $payloadChecksum = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $duplicateReason = null,
    ) {}
}
