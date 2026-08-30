<?php

namespace App\Foundation\Tenancy;

use App\Enums\TenantMembershipStatus;
use App\Models\TenantMembership;
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
     * Erzeugt einen unveränderlichen Context oder antwortet absichtlich wie bei „nicht gefunden“.
     *
     * @throws ModelNotFoundException Wenn Membership, Zeitraum oder Tenantstatus nicht wirksam sind.
     */
    public function resolve(User $user, string $tenantPublicId): TenantContext
    {
        $now = now();

        $membership = TenantMembership::query()
            ->with('tenant')
            ->where('user_id', $user->getKey())
            ->where('status', TenantMembershipStatus::Active)
            ->where('valid_from', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>', $now);
            })
            ->whereHas('tenant', function ($query) use ($tenantPublicId): void {
                $query->where('public_id', $tenantPublicId);
            })
            ->firstOrFail();

        if (! $membership->tenant->status->allowsAccess()) {
            throw (new ModelNotFoundException)->setModel(TenantMembership::class);
        }

        return new TenantContext($membership->tenant, $membership);
    }
}
