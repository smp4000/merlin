<?php

namespace App\Models;

use App\Enums\TrialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Speichert die genau einmal verlängerbare Testphase eines Mandanten.
 */
final class Trial extends Model
{
    /**
     * Liefert den Mandanten der Testphase.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Liefert den Plattformbenutzer, der eine spätere Verlängerung durchgeführt hat.
     */
    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TrialStatus::class,
            'started_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'extended_at' => 'immutable_datetime',
            'extension_count' => 'integer',
        ];
    }
}
