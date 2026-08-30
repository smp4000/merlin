<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Stellt die eigenständige Merlin-Startseite des Backoffice bereit.
 *
 * Die Seite zeigt bewusst nur den aktuellen Aufbaufortschritt. Operative Kennzahlen werden
 * erst ergänzt, wenn Tenant- und StationContext sicher gebunden und autorisiert sind.
 */
final class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.pages.dashboard';

    /**
     * Liefert die übersetzte Bezeichnung in der Hauptnavigation.
     */
    public static function getNavigationLabel(): string
    {
        return __('merlin.dashboard.navigation_label');
    }

    /**
     * Liefert den übersetzten Seitentitel.
     */
    public function getTitle(): string|Htmlable
    {
        return __('merlin.dashboard.title');
    }
}
