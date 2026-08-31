<?php

namespace App\Foundation\Tenancy;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Verwaltet die bewusste Mandantenauswahl in der serverseitigen Sitzung.
 *
 * Die gespeicherte öffentliche ULID ist nur die Auswahl des Benutzers. Vor jeder Nutzung
 * wird sie erneut über den TenantContextResolver gegen Membership, Zeitraum und
 * Mandantenstatus geprüft. Sie ist daher niemals selbst ein Autorisierungsnachweis.
 */
final class TenantContextSession
{
    public const SESSION_KEY = 'active_tenant_public_id';

    /**
     * Nutzt für jede Sitzungsoperation den zentralen, fail-closed arbeitenden Resolver.
     */
    public function __construct(private readonly TenantContextResolver $resolver) {}

    /**
     * Liefert die ungeprüfte Auswahl ausschließlich zur Entscheidung, ob ein alter
     * Kontext vorhanden war. Fachcode darf diesen Wert nicht für Abfragen verwenden.
     */
    public function selectedPublicId(Request $request): ?string
    {
        $value = trim((string) $request->session()->get(self::SESSION_KEY));

        return $value === '' ? null : $value;
    }

    /**
     * Prüft und bindet die bestehende Auswahl oder entfernt einen unwirksamen Kontext.
     */
    public function current(Request $request, User $user): ?TenantContext
    {
        $tenantPublicId = $this->selectedPublicId($request);

        if ($tenantPublicId === null) {
            return null;
        }

        try {
            return $this->resolver->resolve($user, $tenantPublicId);
        } catch (ModelNotFoundException) {
            $this->clear($request);

            return null;
        }
    }

    /**
     * Wählt einen bereits über die Membership autorisierten Mandanten aus.
     *
     * Beim Wechsel werden alle bekannten stations- und berechtigungsgebundenen
     * Sitzungswerte verworfen. Die Sitzungs-ID und das CSRF-Token werden erneuert, damit
     * ein alter Browserzustand nicht unter dem neuen Tenant weiterverwendet wird.
     *
     * @throws ModelNotFoundException Wenn die Auswahl nicht zur Identität gehört.
     */
    public function select(Request $request, User $user, string $tenantPublicId): TenantContext
    {
        $context = $this->resolver->resolve($user, $tenantPublicId);

        $request->session()->forget([
            'active_station_public_id',
            'tenant_permission_cache',
            'tenant_navigation_cache',
        ]);
        $request->session()->put(self::SESSION_KEY, $context->tenant->public_id);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return $context;
    }

    /**
     * Entfernt einen nicht mehr wirksamen Mandanten- und Stationskontext vollständig.
     */
    public function clear(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_KEY,
            'active_station_public_id',
            'tenant_permission_cache',
            'tenant_navigation_cache',
        ]);
    }
}
