<?php

namespace App\Foundation\Settings;

use App\Enums\ThemePalette;
use App\Foundation\Tenancy\TenantContext;

/**
 * Löst das mandantenweite Farbschema ausschließlich aus dem aktiven TenantContext auf.
 *
 * Ohne gebundenen Kontext – etwa auf der Anmeldung – bleibt das neutrale Merlin-Schema
 * aktiv. Eine Darstellungseinstellung kann dadurch niemals zwischen Mandanten auslaufen.
 */
final class TenantTheme
{
    /** Liefert die gespeicherte Palette oder den neutralen Merlin-Standard. */
    public function current(): ThemePalette
    {
        if (! app()->bound(TenantContext::class)) {
            return ThemePalette::default();
        }

        $context = app(TenantContext::class);
        $setting = $context->tenant->appearanceSetting()->first();

        return $setting?->theme_key ?? ThemePalette::default();
    }
}
