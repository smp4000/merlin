<?php

namespace App\Models;

use App\Enums\LegalFormStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Repräsentiert eine zentrale, historisch stabile Rechtsform im DACH-Katalog.
 *
 * Referenzierte Rechtsformen werden nicht gelöscht. Der Status entscheidet lediglich,
 * ob sie für neue Gesellschaften auswählbar sind; Bestandsdaten bleiben lesbar.
 */
final class LegalForm extends Model
{
    protected $guarded = ['id'];

    /**
     * Liefert alle Gesellschaften, die diese Rechtsform historisch oder aktuell nutzen.
     */
    public function legalEntities(): HasMany
    {
        return $this->hasMany(LegalEntity::class);
    }

    /**
     * Liefert die lokalisierte Bezeichnung mit einem sicheren deutschen Fallback.
     */
    public function label(string $locale = 'de'): string
    {
        return $this->labels[$locale] ?? $this->labels['de'] ?? $this->key;
    }

    /**
     * Prüft Land, Status und zeitliche Gültigkeit für eine Neuauswahl.
     */
    public function isSelectableFor(string $countryCode): bool
    {
        $today = today();

        return $this->status === LegalFormStatus::Active
            && in_array(mb_strtoupper($countryCode), $this->country_codes, true)
            && ($this->valid_from === null || ! $this->valid_from->isAfter($today))
            && ($this->valid_until === null || ! $this->valid_until->isBefore($today));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'country_codes' => 'array',
            'status' => LegalFormStatus::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
