<?php

namespace App\Models;

use App\Enums\LegalEntityIdentifierStatus;
use App\Enums\LegalEntityIdentifierType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Speichert eine typisierte Gesellschaftskennung verschlüsselt und tenantgebunden.
 *
 * Der Klarwert darf ausschließlich über den verschlüsselten Cast verarbeitet werden.
 * Listen, Audit und normale Oberflächen verwenden immer nur `value_masked`.
 */
final class LegalEntityIdentifier extends Model
{
    protected $guarded = ['id', 'tenant_id', 'legal_entity_id', 'fingerprint'];

    /**
     * Liefert die zugehörige Gesellschaft; der Anwendungsdienst prüft zusätzlich den Tenant.
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    /**
     * Liefert den unveränderlichen Mandanten der Kennung.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LegalEntityIdentifierType::class,
            'value' => 'encrypted',
            'metadata' => 'array',
            'status' => LegalEntityIdentifierStatus::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
