<?php

namespace App\Models;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Enums\TenantType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Hält die minimalen Daten einer noch nicht bestätigten Partnerregistrierung.
 *
 * Weder Passwort noch Klartext-Bestätigungsschlüssel werden gespeichert. Erst der
 * atomare Bestätigungsdienst darf Benutzer, Mandant und Trial mit diesem Vorgang verbinden.
 */
final class RegistrationIntent extends Model
{
    /**
     * Erzeugt vor der Speicherung eine nicht erratbare öffentliche ULID.
     */
    protected static function booted(): void
    {
        self::creating(function (RegistrationIntent $intent): void {
            $intent->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * Liefert die erst nach Bestätigung erzeugte Identität.
     */
    public function confirmedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_user_id');
    }

    /**
     * Liefert den durch genau diesen Vorgang erzeugten Mandanten.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Liefert die getrennten, versionierten Zustimmungsnachweise.
     */
    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'source' => RegistrationSource::class,
            'tenant_type' => TenantType::class,
            'token_expires_at' => 'immutable_datetime',
            'last_confirmation_sent_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
