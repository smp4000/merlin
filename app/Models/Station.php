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

    /** Liefert die tenantgebundenen externen Quellenbezüge dieser Station. */
    public function sourceReferences(): HasMany
    {
        return $this->hasMany(StationSourceReference::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'source_verified_at' => 'immutable_datetime',
            'source_checked_by_user_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
