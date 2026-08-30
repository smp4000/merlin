<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Speichert den geschäftlichen Ansprechpartner einer Station ohne daraus automatisch
 * ein Benutzerkonto oder weitergehende Berechtigungen abzuleiten.
 */
final class StationContact extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_station_manager' => 'boolean'];
    }
}
