<?php

namespace App\Foundation\Tenancy;

use App\Enums\TrialStatus;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;

/**
 * Erzwingt den zentralen Schreibschutz unabhängig von Filament oder HTTP-Routen.
 */
final class TenantWriteGuard
{
    /**
     * Erlaubt fachliche Änderungen nur in schreibfähigen Tenantzuständen.
     *
     * Sicherheitsaktionen wie Sitzungswiderruf erhalten später einen ausdrücklich
     * getrennten Dienst und dürfen diesen fachlichen Guard nicht pauschal umgehen.
     *
     * @throws TenantReadOnlyException
     */
    public function ensureBusinessWritesAllowed(TenantContext $context): void
    {
        if (! $context->tenant->status->allowsBusinessWrites()) {
            throw new TenantReadOnlyException;
        }

        $trial = $context->tenant->trial;

        if ($trial === null
            || ! in_array($trial->status, [TrialStatus::Active, TrialStatus::ExtendedOnce], true)
            || ! $trial->ends_at->isAfter(now())) {
            throw new TenantReadOnlyException;
        }
    }
}
