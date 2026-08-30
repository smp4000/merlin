<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Erzeugt Benutzeravatare vollständig lokal aus den Initialen des Anzeigenamens.
 *
 * Filaments Standardanbieter ruft einen externen Avatar-Dienst auf und würde dabei den
 * Namen des angemeldeten Benutzers übertragen. Merlin vermeidet diesen unnötigen Abfluss
 * personenbezogener Daten durch ein eingebettetes, lokal erzeugtes SVG.
 */
final class InitialsAvatarProvider implements AvatarProvider
{
    /**
     * Liefert ein lokales SVG als Data-URI, ohne Netzwerkzugriff oder persistierte Bilddatei.
     */
    public function get(Model $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(static fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
            ->join('');

        $safeInitials = htmlspecialchars($initials !== '' ? $initials : 'M', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96">
                <rect width="96" height="96" rx="28" fill="#102a43"/>
                <text x="48" y="51" fill="#ccfbf1" font-family="system-ui, sans-serif" font-size="34" font-weight="700" text-anchor="middle" dominant-baseline="middle">{$safeInitials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
