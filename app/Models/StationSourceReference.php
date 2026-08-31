<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verknüpft eine Merlin-Station mit einer externen, tenantgebundenen Datenquelle.
 *
 * Die externe Kennung wird verschlüsselt gespeichert. Für Eindeutigkeit wird nur ein
 * tenantgebundener HMAC verwendet, der keine Stationskennung offenlegt.
 */
final class StationSourceReference extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    /** Liefert die Station derselben, durch den Fremdschlüssel erzwungenen Tenantgrenze. */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'external_station_id' => 'encrypted',
            'imported_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
        ];
    }
}
