<?php

namespace App\Foundation\Tenancy;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Löst einen Tenant ausschließlich über eine wirksame Membership des Benutzers auf.
 *
 * Die übertragene `public_id` ist nur ein Auswahlwunsch. Die Abfrage beginnt bei den
 * Memberships des authentifizierten Benutzers und verhindert damit eine Auskunft darüber,
 * ob ein fremder Mandant oder eine fremde Membership überhaupt existiert.
 */
final class TenantContextResolver
{
    /**
     * Verwendet dieselbe Membership-Quelle wie Panelzugang und Betriebsauswahl.
     */
    public function __construct(private readonly AccessibleTenantMemberships $memberships) {}

    /**
     * Erzeugt einen unveränderlichen Context oder antwortet absichtlich wie bei „nicht gefunden“.
     *
     * @throws ModelNotFoundException Wenn Membership, Zeitraum oder Tenantstatus nicht wirksam sind.
     */
    public function resolve(User $user, string $tenantPublicId): TenantContext
    {
        $membership = $this->memberships
            ->queryFor($user)
            ->whereHas('tenant', function ($query) use ($tenantPublicId): void {
                $query->where('public_id', $tenantPublicId);
            })
            ->firstOrFail();

        return new TenantContext($membership->tenant, $membership);
    }
}
