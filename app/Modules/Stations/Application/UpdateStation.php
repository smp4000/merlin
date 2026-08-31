<?php

namespace App\Modules\Stations\Application;

use App\Enums\TenantMembershipRole;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Stations\Application\Data\UpdateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ändert freigegebene Stationsgrunddaten innerhalb einer gesperrten Tenant-Transaktion.
 *
 * Status, Herkunft und externe Referenzen bleiben unverändert. Eine Adressänderung macht
 * dagegen alte Koordinaten und die frühere Quellenprüfung ungültig, damit nachfolgende
 * Module niemals mit einer scheinbar bestätigten, aber veralteten Position arbeiten.
 */
final class UpdateStation
{
    public function __construct(
        private readonly TenantWriteGuard $writeGuard,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Speichert die Änderung mit Rollen-, Tenant-, Dubletten- und Konfliktschutz.
     *
     * @throws AuthorizationException
     * @throws PotentialStationDuplicateException
     * @throws ValidationException
     */
    public function handle(
        TenantContext $context,
        string $stationPublicId,
        UpdateStationData $data,
        User $actor,
        string $correlationId,
    ): Station {
        if ((int) $context->membership->user_id !== (int) $actor->getKey()
            || $context->membership->role !== TenantMembershipRole::Administrator
            || ! $context->membership->isEffectiveAt(now())) {
            throw new AuthorizationException;
        }

        $this->writeGuard->ensureBusinessWritesAllowed($context);

        return DB::transaction(function () use ($context, $stationPublicId, $data, $actor, $correlationId): Station {
            $station = Station::query()
                ->where('tenant_id', $context->id())
                ->where('public_id', $stationPublicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->version($station) !== $data->expectedUpdatedAt) {
                throw ValidationException::withMessages([
                    'stationVersion' => __('stations.validation.edit_conflict'),
                ]);
            }

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
            $duplicate = $this->findAddressDuplicate($context, $station, $data);

            if ($duplicate !== null && blank($data->duplicateReason)) {
                throw new PotentialStationDuplicateException;
            }

            $addressChanged = $this->addressChanged($station, $data);
            $station->legal_entity_id = $legalEntity->getKey();
            $station->fuel_station_brand_id = $brand?->getKey();
            $station->name = trim($data->name);
            $station->short_name = filled($data->shortName) ? trim((string) $data->shortName) : null;
            $station->street = trim($data->street);
            $station->house_number = trim($data->houseNumber);
            $station->address_addition = filled($data->addressAddition) ? trim((string) $data->addressAddition) : null;
            $station->postal_code = trim($data->postalCode);
            $station->city = trim($data->city);
            $station->region = trim($data->region);
            $station->country_code = mb_strtoupper($data->countryCode);
            $station->timezone = $data->timezone;
            $station->default_locale = $data->defaultLocale;

            if ($addressChanged) {
                $station->latitude = null;
                $station->longitude = null;
                $station->source_verified_at = null;
            }

            $changedFields = array_keys($station->getDirty());
            $station->save();

            $this->audit->record(
                'station.updated',
                'station',
                (string) $station->public_id,
                $correlationId,
                [
                    'changed_fields' => implode(',', $changedFields),
                    'address_reverification_required' => $addressChanged,
                    'soft_duplicate_confirmed' => $duplicate !== null,
                    'duplicate_reason_provided' => filled($data->duplicateReason),
                ],
                tenant: $context->tenant,
                actor: $actor,
            );

            return $station->refresh();
        });
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

    private function findAddressDuplicate(TenantContext $context, Station $station, UpdateStationData $data): ?Station
    {
        return Station::query()
            ->where('tenant_id', $context->id())
            ->whereKeyNot($station->getKey())
            ->whereRaw('LOWER(postal_code) = ?', [mb_strtolower(trim($data->postalCode))])
            ->whereRaw('LOWER(street) = ?', [mb_strtolower(trim($data->street))])
            ->whereRaw('LOWER(house_number) = ?', [mb_strtolower(trim($data->houseNumber))])
            ->first();
    }

    private function addressChanged(Station $station, UpdateStationData $data): bool
    {
        return $station->street !== trim($data->street)
            || $station->house_number !== trim($data->houseNumber)
            || $station->address_addition !== (filled($data->addressAddition) ? trim((string) $data->addressAddition) : null)
            || $station->postal_code !== trim($data->postalCode)
            || $station->city !== trim($data->city)
            || $station->region !== trim($data->region)
            || $station->country_code !== mb_strtoupper($data->countryCode);
    }

    /** Liefert die unverfälschte Datenbankversion für den Schutz vor Lost Updates. */
    private function version(Station $station): string
    {
        return $station->updated_at->format('Y-m-d H:i:s.u');
    }
}
