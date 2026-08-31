<?php

namespace App\Modules\Partners\Application;

use App\Enums\LegalEntityStatus;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\LegalEntity;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Wechselt die aktive Hauptgesellschaft atomar innerhalb des aktuellen Mandanten.
 */
final readonly class SetPrimaryLegalEntity
{
    public function __construct(private TenantWriteGuard $writeGuard) {}

    /**
     * Verwendet eine öffentliche ULID und eine Tenantabfrage, damit fremde IDs weder
     * übernommen noch durch unterschiedliche Fehlermeldungen offengelegt werden.
     */
    public function handle(TenantContext $context, string $legalEntityPublicId): LegalEntity
    {
        $this->writeGuard->ensureBusinessWritesAllowed($context);

        return DB::transaction(function () use ($context, $legalEntityPublicId): LegalEntity {
            $tenantId = $context->id();
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $target = LegalEntity::query()
                ->where('tenant_id', $tenantId)
                ->where('public_id', $legalEntityPublicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($target->status !== LegalEntityStatus::Active) {
                throw ValidationException::withMessages([
                    'legal_entity' => 'Nur eine aktive Gesellschaft kann Hauptgesellschaft sein.',
                ]);
            }

            LegalEntity::query()
                ->where('tenant_id', $tenantId)
                ->where('is_primary', true)
                ->update(['is_primary' => false, 'primary_tenant_guard' => null]);

            $target->is_primary = true;
            $target->save();

            return $target->refresh();
        });
    }
}
