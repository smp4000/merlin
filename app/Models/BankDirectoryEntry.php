<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enthält einen unveränderten fachlichen Datensatz einer Bundesbank-Verzeichnisversion.
 */
final class BankDirectoryEntry extends Model
{
    protected $guarded = ['id'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BankDirectoryVersion::class, 'bank_directory_version_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['deletion_announced' => 'boolean'];
    }
}
