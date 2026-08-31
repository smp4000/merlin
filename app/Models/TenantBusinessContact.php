<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hält den primären geschäftlichen Kontakt genau eines Mandanten.
 *
 * Personenfelder bleiben optional, damit Merlin keine unnötigen personenbezogenen Daten
 * verlangt, wenn eine allgemeine Unternehmensadresse fachlich ausreicht.
 */
final class TenantBusinessContact extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    /**
     * Liefert den Mandanten, zu dem dieser Kontakt unveränderlich gehört.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
