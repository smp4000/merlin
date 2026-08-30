<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Dokumentiert einen getrennten, versionierten Informations- oder Vertragsnachweis.
 *
 * Der Nachweis ist unveränderlich und speichert bewusst keine IP-Adresse, keinen
 * vollständigen User-Agent und keinen Inhalt des zugrunde liegenden Dokuments.
 */
final class ConsentRecord extends Model
{
    public $timestamps = false;

    /**
     * Verhindert nachträgliche Änderungen und Löschungen über Eloquent.
     */
    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Zustimmungsnachweise dürfen nicht verändert werden.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Zustimmungsnachweise dürfen nicht einzeln gelöscht werden.');
        });
    }

    /**
     * Liefert den Registrierungsvorgang des Nachweises.
     */
    public function registrationIntent(): BelongsTo
    {
        return $this->belongsTo(RegistrationIntent::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'immutable_datetime',
        ];
    }
}
