<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Repräsentiert eine rechtliche Betreibergesellschaft innerhalb genau eines Mandanten.
 */
final class LegalEntity extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(LegalEntityBankAccount::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'vat_id' => 'encrypted',
        ];
    }
}
