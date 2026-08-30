<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Repräsentiert einen operativen Tankstellenstandort und bindet ihn unveränderlich an
 * Mandant und rechtlichen Betreiber.
 */
final class Station extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    protected static function booted(): void
    {
        self::creating(function (Station $station): void {
            $station->public_id ??= (string) Str::ulid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(FuelStationBrand::class, 'fuel_station_brand_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(StationContact::class);
    }
}
