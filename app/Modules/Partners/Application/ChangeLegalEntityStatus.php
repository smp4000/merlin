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
 * Ändert den Gesellschaftsstatus und erhält dabei immer eine aktive Hauptgesellschaft.
 */
final readonly class ChangeLegalEntityStatus
{
    public function __construct(private TenantWriteGuard $writeGuard) {}

    /**
     * Führt ausschließlich freigegebene Zustandswechsel im aktuellen Tenant aus.
     */
    public function handle(
        TenantContext $context,
        string $legalEntityPublicId,
        LegalEntityStatus $targetStatus,
    ): LegalEntity {
        $this->writeGuard->ensureBusinessWritesAllowed($context);

        return DB::transaction(function () use ($context, $legalEntityPublicId, $targetStatus): LegalEntity {
            $tenantId = $context->id();
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $legalEntity = LegalEntity::query()
                ->with('legalForm')
                ->where('tenant_id', $tenantId)
                ->where('public_id', $legalEntityPublicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($legalEntity->status === $targetStatus) {
                return $legalEntity;
            }

            $allowed = match ($legalEntity->status) {
                LegalEntityStatus::Draft => $targetStatus === LegalEntityStatus::Active,
                LegalEntityStatus::Active => $targetStatus === LegalEntityStatus::Inactive,
                LegalEntityStatus::Inactive => $targetStatus === LegalEntityStatus::Active,
            };

            if (! $allowed) {
                throw ValidationException::withMessages([
                    'status' => 'Dieser Statuswechsel ist nicht zulässig.',
                ]);
            }

            if ($targetStatus === LegalEntityStatus::Active) {
                $this->ensureActivationRequirements($context, $legalEntity);
                $hasPrimary = LegalEntity::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', LegalEntityStatus::Active)
                    ->where('is_primary', true)
                    ->exists();
                $legalEntity->is_primary = ! $hasPrimary;
            } elseif ($legalEntity->is_primary) {
                $replacement = LegalEntity::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKeyNot($legalEntity->getKey())
                    ->where('status', LegalEntityStatus::Active)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($replacement === null) {
                    throw ValidationException::withMessages([
                        'status' => 'Die letzte aktive Hauptgesellschaft kann nicht deaktiviert werden.',
                    ]);
                }

                // Zuerst wird der alte Unique-Guard freigegeben, anschließend übernimmt
                // der Ersatz. Beide Änderungen sind Teil derselben Transaktion.
                $legalEntity->is_primary = false;
                $legalEntity->save();
                $replacement->is_primary = true;
                $replacement->save();
            }

            $legalEntity->status = $targetStatus;

            if ($targetStatus !== LegalEntityStatus::Active) {
                $legalEntity->is_primary = false;
            }

            $legalEntity->save();

            return $legalEntity->refresh();
        });
    }

    /**
     * Wiederholt die Aktivierungsbedingungen unabhängig von einer späteren Oberfläche.
     */
    private function ensureActivationRequirements(TenantContext $context, LegalEntity $legalEntity): void
    {
        $requiredValues = [
            $legalEntity->legal_name,
            $legalEntity->street,
            $legalEntity->house_number,
            $legalEntity->postal_code,
            $legalEntity->city,
            $legalEntity->region,
            $legalEntity->country_code,
        ];
        $hasCompleteAddress = collect($requiredValues)->every(fn ($value): bool => filled($value));
        $hasBusinessContact = $context->tenant->businessContact()->whereNotNull('email')->exists();
        $hasSelectableLegalForm = $legalEntity->legalForm?->isSelectableFor($legalEntity->country_code) === true;

        if (! $hasCompleteAddress || ! $hasBusinessContact || ! $hasSelectableLegalForm) {
            throw ValidationException::withMessages([
                'status' => 'Die Gesellschaft erfüllt noch nicht alle Voraussetzungen für die Aktivierung.',
            ]);
        }
    }
}
