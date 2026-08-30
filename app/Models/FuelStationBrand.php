<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Enthält eine zentral gepflegte, mandantenunabhängige Tankstellenmarke.
 */
final class FuelStationBrand extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['country_codes' => 'array'];
    }
}
