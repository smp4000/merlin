<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Konfiguriert die zugelassene öffentliche Quelle des globalen Bankverzeichnisses.
 */
final class BankDirectorySource extends Model
{
    protected $guarded = ['id'];

    public function versions(): HasMany
    {
        return $this->hasMany(BankDirectoryVersion::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_imported_at' => 'datetime',
        ];
    }
}
