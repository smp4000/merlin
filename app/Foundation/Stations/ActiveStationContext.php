<?php

namespace App\Foundation\Stations;

use App\Foundation\Tenancy\TenantContext;
use App\Models\Station;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Verwaltet die aktive Arbeitstankstelle innerhalb des bereits geprüften Mandanten.
 *
 * Die Sitzungs-ULID ist nur eine Benutzerauswahl. Jede Auflösung wird erneut mit
 * tenant_id und aktivem Stationsstatus eingeschränkt und stellt daher selbst keinen
 * Autorisierungsnachweis dar.
 */
final class ActiveStationContext
{
    public const SESSION_KEY = 'active_station_public_id';

    public function __construct(private readonly Session $session) {}

    /**
     * Liefert die weiterhin gültige Auswahl. Bei genau einer aktiven Station wird diese
     * komfortabel vorausgewählt; bei mehreren Standorten bleibt die Wahl ausdrücklich.
     */
    public function current(TenantContext $context): ?Station
    {
        $selectedPublicId = trim((string) $this->session->get(self::SESSION_KEY));

        if ($selectedPublicId !== '') {
            $selected = $this->activeStations($context)
                ->where('public_id', $selectedPublicId)
                ->first();

            if ($selected !== null) {
                return $selected;
            }

            $this->clear();
        }

        $stations = $this->activeStations($context)->limit(2)->get();

        if ($stations->count() === 1) {
            $station = $stations->firstOrFail();
            $this->session->put(self::SESSION_KEY, $station->public_id);

            return $station;
        }

        return null;
    }

    /**
     * Bindet ausschließlich eine aktive Station des aktuellen Mandanten an die Sitzung.
     * Entwürfe, geschlossene und fremde Stationen werden identisch als nicht vorhanden
     * behandelt, damit keine fremden Metadaten offengelegt werden.
     *
     * @throws ModelNotFoundException
     */
    public function select(TenantContext $context, string $stationPublicId): Station
    {
        $station = $this->activeStations($context)
            ->where('public_id', $stationPublicId)
            ->firstOrFail();

        $this->session->put(self::SESSION_KEY, $station->public_id);

        return $station;
    }

    /** Entfernt die stationsgebundene Auswahl, ohne den Mandantenkontext zu verändern. */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /** Liefert die Anzahl der im aktuellen Mandanten betrieblich auswählbaren Stationen. */
    public function activeCount(TenantContext $context): int
    {
        return $this->activeStations($context)->count();
    }

    /** Baut jede Stationsabfrage aus dem serverseitig gebundenen TenantContext auf. */
    private function activeStations(TenantContext $context): Builder
    {
        return Station::query()
            ->where('tenant_id', $context->id())
            ->where('status', 'active')
            ->orderBy('name');
    }
}
