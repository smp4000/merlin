<?php

namespace App\Modules\Partners\Application\Data;

use App\Enums\LegalEntityStatus;
use Carbon\CarbonImmutable;

/**
 * Transportiert validierbare Gesellschafts- und Geschäftskontaktdaten ohne Tenant-ID.
 *
 * Der Mandant stammt ausschließlich aus dem serverseitigen TenantContext. Dadurch kann
 * weder ein HTTP-Formular noch ein späterer Queue-Auftrag den Zielmandanten überschreiben.
 */
final readonly class CreateLegalEntityData
{
    public function __construct(
        public ?int $legalFormId,
        public ?string $legalName,
        public ?string $tradeName,
        public LegalEntityStatus $status,
        public bool $makePrimary,
        public ?string $street,
        public ?string $houseNumber,
        public ?string $addressAddition,
        public ?string $postalCode,
        public ?string $city,
        public ?string $region,
        public ?string $countryCode,
        public ?string $businessEmail,
        public ?string $businessPhone = null,
        public ?string $businessFax = null,
        public ?string $website = null,
        public ?string $contactFirstName = null,
        public ?string $contactLastName = null,
        public ?CarbonImmutable $effectiveFrom = null,
        public ?string $postalStreet = null,
        public ?string $postalHouseNumber = null,
        public ?string $postalAddressAddition = null,
        public ?string $postalPostalCode = null,
        public ?string $postalCity = null,
        public ?string $postalRegion = null,
        public ?string $postalCountryCode = null,
    ) {}
}
