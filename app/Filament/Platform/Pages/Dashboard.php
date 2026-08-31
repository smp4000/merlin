<?php

namespace App\Filament\Platform\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Zeigt den datenarmen Einstieg in die globale Merlin-Plattformverwaltung.
 *
 * Die Seite enthält bewusst keine operativen Gesellschafts-, Stations-, Mitarbeiter-
 * oder Bankverbindungsdaten eines Mandanten. Sie führt ausschließlich zu globalen
 * Metadaten und Katalogen, für die die Plattformrolle vorgesehen ist.
 */
final class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected string $view = 'filament.platform.pages.dashboard';

    /**
     * Bezeichnet den globalen Einstieg eindeutig getrennt von der Partnerübersicht.
     */
    public static function getNavigationLabel(): string
    {
        return __('merlin.platform_dashboard.navigation_label');
    }

    /**
     * Liefert den lokalisierten Titel ohne Mandantenbezug.
     */
    public function getTitle(): string|Htmlable
    {
        return __('merlin.platform_dashboard.title');
    }
}
