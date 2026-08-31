<?php

namespace App\Modules\Stations\Domain;

/**
 * Enthält ausschließlich die für einen Stationsentwurf zulässigen Standortstammdaten.
 */
final readonly class StationDetails
{
    public function __construct(
        public string $providerKey,
        public string $externalStationId,
        public string $name,
        public string $street,
        public string $houseNumber,
        public string $postalCode,
        public string $city,
        public ?float $latitude,
        public ?float $longitude,
        public string $payloadChecksum,
    ) {}
}
