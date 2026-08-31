<?php

namespace App\Models;

use App\Enums\ThemePalette;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hält das mandantenweit gewählte, zentral geprüfte Erscheinungsbild.
 *
 * Die Entität speichert nur den Enum-Schlüssel. Statusfarben sowie sicherheitsrelevante
 * Warnfarben bleiben unabhängig von dieser Auswahl systemweit unverändert.
 */
final class TenantAppearanceSetting extends Model
{
    /** Liefert den zugehörigen isolierten Mandantenraum. */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Liefert die Person, die das Schema zuletzt gespeichert hat. */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['theme_key' => ThemePalette::class];
    }
}
