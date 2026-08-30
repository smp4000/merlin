<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Speichert ein minimiertes, unveränderliches fachliches oder Security-Ereignis.
 *
 * Token, Passwörter, Bestätigungslinks und rohe E-Mail-Adressen sind in Auditfeldern
 * ausdrücklich verboten. Eine spätere Write-once-Ablage ergänzt diesen Anwendungsschutz.
 */
final class AuditEvent extends Model
{
    public $timestamps = false;

    /**
     * Sperrt versehentliche Änderungen und Löschungen über das Modell.
     */
    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Audit-Ereignisse dürfen nicht verändert werden.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Audit-Ereignisse dürfen nicht gelöscht werden.');
        });
    }

    /**
     * Liefert den Mandanten, sofern beim Ereignis bereits einer existierte.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Liefert die auslösende Identität, sofern sie bereits existierte.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
