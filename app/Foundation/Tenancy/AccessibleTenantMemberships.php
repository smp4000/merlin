<?php

namespace App\Foundation\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Liefert die aktuell wirksamen Mandantenzugehörigkeiten einer globalen Identität.
 *
 * Diese Klasse ist die gemeinsame Quelle für Panelzugang, Betriebsauswahl und
 * TenantContext-Auflösung. Dadurch verwenden diese Schutzschichten dieselben Status-
 * und Zeitregeln und können nicht durch voneinander abweichende Abfragen auseinanderlaufen.
 */
final class AccessibleTenantMemberships
{
    /**
     * Erstellt eine ausschließlich auf den angemeldeten Benutzer begrenzte Abfrage.
     *
     * Der Mandantenstatus wird bereits in der Datenbank eingeschränkt. Die Existenz
     * fremder Memberships oder Mandanten kann über diese Abfrage nicht erkannt werden.
     *
     * @return Builder<TenantMembership>
     */
    public function queryFor(User $user): Builder
    {
        $now = now();
        $accessibleTenantStatuses = collect(TenantStatus::cases())
            ->filter(fn (TenantStatus $status): bool => $status->allowsAccess())
            ->map(fn (TenantStatus $status): string => $status->value)
            ->all();

        return TenantMembership::query()
            ->with('tenant')
            ->where('tenant_memberships.user_id', $user->getKey())
            ->where('tenant_memberships.status', TenantMembershipStatus::Active)
            ->where('tenant_memberships.valid_from', '<=', $now)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('tenant_memberships.valid_until')
                    ->orWhere('tenant_memberships.valid_until', '>', $now);
            })
            ->whereHas('tenant', function (Builder $query) use ($accessibleTenantStatuses): void {
                $query->whereIn('status', $accessibleTenantStatuses);
            });
    }

    /**
     * Gibt die für eine bewusste Betriebsauswahl sichtbaren Memberships sortiert zurück.
     *
     * @return Collection<int, TenantMembership>
     */
    public function getFor(User $user): Collection
    {
        return $this->queryFor($user)
            ->join('tenants', 'tenant_memberships.tenant_id', '=', 'tenants.id')
            ->orderBy('tenants.display_name')
            ->select('tenant_memberships.*')
            ->get();
    }

    /**
     * Prüft datenarm, ob die Identität das Partner-Panel grundsätzlich betreten darf.
     */
    public function existsFor(User $user): bool
    {
        return $this->queryFor($user)->exists();
    }
}
