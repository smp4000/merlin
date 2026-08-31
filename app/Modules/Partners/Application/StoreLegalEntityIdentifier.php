<?php

namespace App\Modules\Partners\Application;

use App\Enums\LegalEntityIdentifierStatus;
use App\Enums\LegalEntityIdentifierType;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\LegalEntity;
use App\Models\LegalEntityIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Speichert eine Gesellschaftskennung verschlüsselt und ohne fremde Tenantreferenzen.
 */
final readonly class StoreLegalEntityIdentifier
{
    public function __construct(
        private TenantWriteGuard $writeGuard,
        private LegalEntityIdentifierProtector $protector,
    ) {}

    /**
     * Legt eine Kennung an oder reaktiviert exakt dieselbe tenantinterne Kennung.
     *
     * @param  array<string, string>|null  $metadata
     */
    public function handle(
        TenantContext $context,
        string $legalEntityPublicId,
        LegalEntityIdentifierType $type,
        string $countryCode,
        string $value,
        ?array $metadata = null,
        ?CarbonImmutable $validFrom = null,
        ?CarbonImmutable $validUntil = null,
    ): LegalEntityIdentifier {
        $this->writeGuard->ensureBusinessWritesAllowed($context);
        $normalized = $this->protector->normalize($type, $value);

        if ($normalized === '' || mb_strlen($normalized) > 120) {
            // Der Klarwert wird bewusst weder Bestandteil der Meldung noch einer Exception.
            throw ValidationException::withMessages([
                'identifier' => 'Die Kennung ist leer oder überschreitet die zulässige Länge.',
            ]);
        }

        $countryCode = mb_strtoupper($countryCode);

        if (! in_array($countryCode, ['DE', 'AT', 'CH'], true)) {
            throw ValidationException::withMessages([
                'country_code' => 'Das Land der Kennung wird derzeit nicht unterstützt.',
            ]);
        }

        if ($validFrom !== null && $validUntil !== null && $validUntil->isBefore($validFrom)) {
            throw ValidationException::withMessages([
                'valid_until' => 'Das Gültigkeitsende darf nicht vor dem Beginn liegen.',
            ]);
        }

        return DB::transaction(function () use (
            $context,
            $legalEntityPublicId,
            $type,
            $countryCode,
            $normalized,
            $metadata,
            $validFrom,
            $validUntil,
        ): LegalEntityIdentifier {
            $tenantId = $context->id();
            $legalEntity = LegalEntity::query()
                ->where('tenant_id', $tenantId)
                ->where('public_id', $legalEntityPublicId)
                ->lockForUpdate()
                ->firstOrFail();
            $fingerprint = $this->protector->fingerprint($tenantId, $type, $countryCode, $normalized);

            $identifier = LegalEntityIdentifier::query()->firstOrNew([
                'tenant_id' => $tenantId,
                'type' => $type,
                'country_code' => $countryCode,
                'fingerprint' => $fingerprint,
            ]);

            if ($identifier->exists && (int) $identifier->legal_entity_id !== (int) $legalEntity->getKey()) {
                throw ValidationException::withMessages([
                    'identifier' => 'Diese Kennung ist im aktuellen Betrieb bereits vergeben.',
                ]);
            }

            $identifier->tenant_id = $tenantId;
            $identifier->legal_entity_id = $legalEntity->getKey();
            $identifier->type = $type;
            $identifier->country_code = $countryCode;
            $identifier->value = $normalized;
            $identifier->value_masked = $this->protector->mask($normalized);
            $identifier->fingerprint = $fingerprint;
            $identifier->metadata = $this->sanitizeMetadata($type, $metadata);
            $identifier->valid_from = $validFrom;
            $identifier->valid_until = $validUntil;
            $identifier->status = LegalEntityIdentifierStatus::Active;
            $identifier->save();

            return $identifier;
        });
    }

    /**
     * Begrenzt Registermetadaten auf bekannte, nicht geheime Felder und kurze Texte.
     *
     * @param  array<string, string>|null  $metadata
     * @return array<string, string>|null
     */
    private function sanitizeMetadata(LegalEntityIdentifierType $type, ?array $metadata): ?array
    {
        if ($type !== LegalEntityIdentifierType::CommercialRegister || $metadata === null) {
            return null;
        }

        $sanitized = [];

        foreach (['register_type', 'register_court'] as $key) {
            $value = trim((string) ($metadata[$key] ?? ''));

            if ($value !== '') {
                $sanitized[$key] = mb_substr($value, 0, 160);
            }
        }

        return $sanitized === [] ? null : $sanitized;
    }
}
