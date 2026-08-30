<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Speichert eine Bankverbindung verschlüsselt und standardmäßig nur maskiert sichtbar.
 *
 * Der Fingerprint ist mandantengebunden und dient ausschließlich der Dublettenprüfung;
 * vollständige IBAN-Werte dürfen weder gesucht noch protokolliert werden.
 */
final class LegalEntityBankAccount extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['iban' => 'encrypted'];
    }
}
