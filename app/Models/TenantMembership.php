<?php

namespace App\Models;

use App\Enums\TenantMembershipRole;
use App\Enums\TenantMembershipStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verknüpft eine globale Benutzeridentität mit genau einem Mandantenkontext.
 *
 * Dieses Auswahlmodell erhält bewusst keinen automatischen Tenant-Global-Scope: Vor der
 * Tenantwahl muss der angemeldete Benutzer seine eigenen aktiven Memberships auflösen
 * können. Jede Abfrage wird daher zentral über den TenantContextResolver eingeschränkt.
 */
final class TenantMembership extends Model
{
    /**
     * Liefert den zugehörigen Mandanten.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Liefert die globale Benutzeridentität dieser Membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Prüft Status und zeitliche Gültigkeit zu einem expliziten Zeitpunkt.
     */
    public function isEffectiveAt(CarbonInterface $moment): bool
    {
        return $this->status === TenantMembershipStatus::Active
            && $this->valid_from->lessThanOrEqualTo($moment)
            && ($this->valid_until === null || $this->valid_until->isAfter($moment));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TenantMembershipRole::class,
            'status' => TenantMembershipStatus::class,
            'valid_from' => 'immutable_datetime',
            'valid_until' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
