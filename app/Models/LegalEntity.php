<?php

namespace App\Models;

use App\Enums\LegalEntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Repräsentiert eine rechtliche Betreibergesellschaft innerhalb genau eines Mandanten.
 */
final class LegalEntity extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    /**
     * Vergibt eine öffentliche, nicht erratbare Kennung unabhängig vom Datenbank-ID-Raum.
     */
    protected static function booted(): void
    {
        self::creating(function (LegalEntity $legalEntity): void {
            $legalEntity->public_id ??= (string) Str::ulid();
        });

        self::saving(function (LegalEntity $legalEntity): void {
            // Nur aktive Hauptgesellschaften belegen den eindeutigen Tenant-Guard. Alle
            // übrigen Datensätze tragen NULL und können deshalb historisch erhalten bleiben.
            $isActive = $legalEntity->status instanceof LegalEntityStatus
                ? $legalEntity->status === LegalEntityStatus::Active
                : $legalEntity->status === LegalEntityStatus::Active->value;
            $legalEntity->primary_tenant_guard = $isActive && (bool) $legalEntity->is_primary
                ? $legalEntity->tenant_id
                : null;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Liefert die zentrale Rechtsform; bei ungeklärten Legacydaten kann sie leer sein.
     */
    public function legalForm(): BelongsTo
    {
        return $this->belongsTo(LegalForm::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(LegalEntityBankAccount::class);
    }

    /**
     * Liefert die vertraulichen, typisierten Behörden- und Registerkennungen.
     */
    public function identifiers(): HasMany
    {
        return $this->hasMany(LegalEntityIdentifier::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'status' => LegalEntityStatus::class,
            'effective_from' => 'date',
            'legal_form_confirmed_at' => 'datetime',
            'vat_id' => 'encrypted',
        ];
    }
}
