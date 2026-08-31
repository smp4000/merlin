<?php

namespace App\Modules\Stations\Application;

use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\Station;
use App\Models\StationSourceReference;
use App\Models\User;
use App\Modules\Stations\Domain\StationDetails;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Verknüpft eine bestehende Station bewusst mit einer externen Quelle.
 *
 * Der Dienst ändert ausdrücklich keine Stationsstammdaten. Abweichende Providerwerte
 * werden erst in einem späteren, separaten Änderungsvorschlagsprozess behandelt.
 */
final class LinkStationSourceReference
{
    public function __construct(
        private readonly TenantWriteGuard $writeGuard,
        private readonly AuditRecorder $audit,
    ) {}

    /** Speichert nur die Quellenreferenz innerhalb des aktuellen Mandanten. */
    public function handle(
        TenantContext $context,
        string $stationPublicId,
        StationDetails $details,
        User $actor,
        string $correlationId,
    ): StationSourceReference {
        $this->writeGuard->ensureBusinessWritesAllowed($context);
        $station = Station::query()
            ->where('tenant_id', $context->id())
            ->where('public_id', $stationPublicId)
            ->first();

        if ($station === null) {
            throw ValidationException::withMessages([
                'selectedReference' => __('stations.validation.station_invalid'),
            ]);
        }

        try {
            return DB::transaction(function () use ($context, $station, $details, $actor, $correlationId): StationSourceReference {
                $reference = new StationSourceReference;
                $reference->tenant_id = $context->id();
                $reference->station_id = $station->getKey();
                $reference->provider_key = $details->providerKey;
                $reference->external_station_id = $details->externalStationId;
                $reference->external_station_id_hash = hash_hmac(
                    'sha256',
                    $context->id().'|'.mb_strtolower($details->providerKey).'|'.$details->externalStationId,
                    (string) config('app.key'),
                );
                $reference->payload_checksum = $details->payloadChecksum;
                $reference->imported_at = now();
                $reference->last_checked_at = now();
                $reference->save();

                $station->source_checked_by_user_at = now();
                $station->save();

                $this->audit->record(
                    'station.source_linked',
                    'station',
                    (string) $station->public_id,
                    $correlationId,
                    ['provider_key' => $details->providerKey, 'master_data_changed' => false],
                    tenant: $context->tenant,
                    actor: $actor,
                );

                return $reference;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'selectedReference' => __('stations.validation.external_duplicate'),
            ]);
        }
    }
}
