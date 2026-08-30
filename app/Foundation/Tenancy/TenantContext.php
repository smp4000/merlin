<?php

namespace App\Foundation\Tenancy;

use App\Models\Tenant;
use App\Models\TenantMembership;

/**
 * Hält den nach Login und Membership-Prüfung unveränderlich gebundenen Mandantenkontext.
 *
 * Ein Context wird pro Request beziehungsweise Job neu erzeugt. Er kann nicht nachträglich
 * auf einen anderen Tenant umgebogen werden; ein Tenantwechsel erzeugt einen neuen Context.
 */
final readonly class TenantContext
{
    public function __construct(
        public Tenant $tenant,
        public TenantMembership $membership,
    ) {}

    /**
     * Liefert den internen Schlüssel ausschließlich für serverseitige Scopes.
     */
    public function id(): int
    {
        return (int) $this->tenant->getKey();
    }
}
