<?php

namespace App\Modules\Stations\Application\Data;

/**
 * Transportiert ausschließlich die im ersten Bearbeitungsschnitt freigegebenen Grunddaten.
 *
 * tenant_id, Status, Quelle, Koordinaten und Verzeichnisreferenzen fehlen absichtlich und
 * können deshalb weder über Livewire noch durch Mass Assignment verändert werden.
 */
final readonly class UpdateStationData
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
        public string $expectedUpdatedAt,
        public ?string $duplicateReason,
    ) {}
}
