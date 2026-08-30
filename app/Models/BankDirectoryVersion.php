<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Friert einen erfolgreich validierten Import unveränderlich für Nachvollziehbarkeit ein.
 */
final class BankDirectoryVersion extends Model
{
    protected $guarded = ['id'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(BankDirectorySource::class, 'bank_directory_source_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BankDirectoryEntry::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['imported_at' => 'datetime', 'activated_at' => 'datetime'];
    }
}
