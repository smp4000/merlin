<?php

namespace App\Modules\Stations\Application;

use App\Enums\LegalEntityStatus;
use App\Enums\TenantMembershipRole;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\Station;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aktiviert einen vollständig gepflegten Stationsentwurf im gebundenen Mandanten.
 *
 * Die Aktivierung ist eine fachliche Statusänderung und ausdrücklich nicht mit der
 * Auswahl der aktuellen Arbeitstankstelle gleichzusetzen. Mehrere Standorte eines
 * Partners dürfen gleichzeitig aktiv sein.
 */
final class ActivateStation
{
    public function __construct(
        private readonly TenantWriteGuard $writeGuard,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Prüft Rolle, Schreibfähigkeit, Tenantgrenze und Mindeststammdaten und aktiviert
     * den Entwurf anschließend gemeinsam mit dem minimierten Audit atomar.
     *
     * @throws AuthorizationException Wenn der Akteur nicht die gebundene Administration ist.
     * @throws ValidationException Wenn Status oder Pflichtstammdaten keine Aktivierung erlauben.
     */
    public function handle(
        TenantContext $context,
        string $stationPublicId,
        User $actor,
        string $correlationId,
    ): Station {
        if ((int) $context->membership->user_id !== (int) $actor->getKey()
            || $context->membership->role !== TenantMembershipRole::Administrator
            || ! $context->membership->isEffectiveAt(now())) {
            throw new AuthorizationException;
        }

        $this->writeGuard->ensureBusinessWritesAllowed($context);

        return DB::transaction(function () use ($context, $stationPublicId, $actor, $correlationId): Station {
            $station = Station::query()
                ->where('tenant_id', $context->id())
                ->where('public_id', $stationPublicId)
                ->with(['brand', 'legalEntity'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($station->status === 'active') {
                return $station;
            }

            if (! in_array($station->status, ['draft', 'review'], true)) {
                throw ValidationException::withMessages([
                    'station' => __('stations.validation.activation_status'),
                ]);
            }

            $this->ensureRequiredMasterData($station);

            $previousStatus = $station->status;
            $station->status = 'active';
            $station->activated_at = now();
            $station->closed_at = null;
            $station->save();

            $this->audit->record(
                'station.activated',
                'station',
                (string) $station->public_id,
                $correlationId,
                ['previous_status' => $previousStatus, 'new_status' => 'active'],
                tenant: $context->tenant,
                actor: $actor,
            );

            return $station->refresh();
        });
    }

    /**
     * Prüft den heute implementierten Grunddatenumfang. Öffnungszeiten werden nach
     * Einführung ihres versionierten Teilmoduls als eigene Aktivierungsregel ergänzt.
     */
    private function ensureRequiredMasterData(Station $station): void
    {
        $requiredValues = [
            $station->name,
            $station->street,
            $station->house_number,
            $station->postal_code,
            $station->city,
            $station->region,
            $station->country_code,
            $station->timezone,
            $station->default_locale,
        ];

        $brandIsAllowed = $station->brand !== null
            && $station->brand->status === 'active'
            && in_array($station->country_code, $station->brand->country_codes, true);
        $legalEntityIsActive = $station->legalEntity?->status === LegalEntityStatus::Active;

        if (collect($requiredValues)->contains(fn (mixed $value): bool => blank($value))
            || ! preg_match('/^\d{5}$/', $station->postal_code)
            || ! $brandIsAllowed
            || ! $legalEntityIsActive) {
            throw ValidationException::withMessages([
                'station' => __('stations.validation.activation_incomplete'),
            ]);
        }
    }
}
