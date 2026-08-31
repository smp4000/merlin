<?php

namespace App\Modules\Stations\Application;

use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\StationSourceReference;
use App\Models\User;
use App\Modules\Stations\Application\Data\CreateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Legt einen Stationsentwurf ausschließlich innerhalb des geprüften TenantContext an.
 *
 * Legal Entity, Brand, Dubletten und Quellenreferenzen werden serverseitig geprüft;
 * Livewire- oder Requestwerte können dadurch keinen fremden Mandanten referenzieren.
 */
final class CreateStation
{
    public function __construct(
        private readonly TenantWriteGuard $writeGuard,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Speichert Entwurf, optionale Quellenreferenz und minimiertes Audit atomar.
     *
     * @throws PotentialStationDuplicateException
     * @throws ValidationException
     */
    public function handle(
        TenantContext $context,
        CreateStationData $data,
        User $actor,
        string $correlationId,
    ): Station {
        $this->writeGuard->ensureBusinessWritesAllowed($context);

        $legalEntity = LegalEntity::query()
            ->where('tenant_id', $context->id())
            ->where('public_id', $data->legalEntityPublicId)
            ->first();

        if ($legalEntity === null) {
            throw ValidationException::withMessages([
                'legalEntityPublicId' => __('stations.validation.legal_entity_invalid'),
            ]);
        }

        $brand = $this->resolveBrand($data->brandId, $data->countryCode);
        $duplicate = $this->findAddressDuplicate($context, $data);

        if ($duplicate !== null && blank($data->duplicateReason)) {
            throw new PotentialStationDuplicateException;
        }

        try {
            return DB::transaction(function () use ($context, $data, $actor, $correlationId, $legalEntity, $brand, $duplicate): Station {
                $station = new Station;
                $station->tenant_id = $context->id();
                $station->legal_entity_id = $legalEntity->getKey();
                $station->fuel_station_brand_id = $brand?->getKey();
                $station->name = trim($data->name);
                $station->short_name = filled($data->shortName) ? trim((string) $data->shortName) : null;
                $station->status = 'draft';
                $station->street = trim($data->street);
                $station->house_number = trim($data->houseNumber);
                $station->address_addition = filled($data->addressAddition) ? trim((string) $data->addressAddition) : null;
                $station->postal_code = trim($data->postalCode);
                $station->city = trim($data->city);
                $station->region = trim($data->region);
                $station->country_code = mb_strtoupper($data->countryCode);
                $station->latitude = $data->latitude;
                $station->longitude = $data->longitude;
                $station->timezone = $data->timezone;
                $station->default_locale = $data->defaultLocale;
                $station->source_type = $data->sourceType;
                $station->source_verified_at = $data->externalStationId === null ? null : now();
                $station->source_checked_by_user_at = now();
                $station->save();

                if ($data->providerKey !== null && $data->externalStationId !== null) {
                    $reference = new StationSourceReference;
                    $reference->tenant_id = $context->id();
                    $reference->station_id = $station->getKey();
                    $reference->provider_key = $data->providerKey;
                    $reference->external_station_id = $data->externalStationId;
                    $reference->external_station_id_hash = $this->externalIdHash(
                        $context->id(),
                        $data->providerKey,
                        $data->externalStationId,
                    );
                    $reference->payload_checksum = $data->payloadChecksum;
                    $reference->imported_at = now();
                    $reference->last_checked_at = now();
                    $reference->save();
                }

                $this->audit->record(
                    'station.created',
                    'station',
                    (string) $station->public_id,
                    $correlationId,
                    [
                        'source_type' => $data->sourceType,
                        'provider_key' => $data->providerKey,
                        'soft_duplicate_confirmed' => $duplicate !== null,
                        'duplicate_reason_provided' => filled($data->duplicateReason),
                    ],
                    tenant: $context->tenant,
                    actor: $actor,
                );

                return $station;
            });
        } catch (UniqueConstraintViolationException) {
            // Die Datenbank bleibt die letzte Instanz gegen konkurrierende Übernahmen
            // derselben Provider-ID. Die Oberfläche erhält keinen fremden Datensatz.
            throw ValidationException::withMessages([
                'selectedReference' => __('stations.validation.external_duplicate'),
            ]);
        }
    }

    private function resolveBrand(?int $brandId, string $countryCode): ?FuelStationBrand
    {
        if ($brandId === null) {
            return null;
        }

        $brand = FuelStationBrand::query()->whereKey($brandId)->where('status', 'active')->first();

        if ($brand === null || ! in_array(mb_strtoupper($countryCode), $brand->country_codes, true)) {
            throw ValidationException::withMessages(['brandId' => __('stations.validation.brand_invalid')]);
        }

        return $brand;
    }

    private function findAddressDuplicate(TenantContext $context, CreateStationData $data): ?Station
    {
        return Station::query()
            ->where('tenant_id', $context->id())
            ->whereRaw('LOWER(postal_code) = ?', [mb_strtolower(trim($data->postalCode))])
            ->whereRaw('LOWER(street) = ?', [mb_strtolower(trim($data->street))])
            ->whereRaw('LOWER(house_number) = ?', [mb_strtolower(trim($data->houseNumber))])
            ->first();
    }

    private function externalIdHash(int $tenantId, string $providerKey, string $externalId): string
    {
        return hash_hmac(
            'sha256',
            $tenantId.'|'.mb_strtolower(trim($providerKey)).'|'.trim($externalId),
            (string) config('app.key'),
        );
    }
}
