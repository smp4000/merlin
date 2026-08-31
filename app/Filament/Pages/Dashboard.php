<?php

namespace App\Filament\Pages;

use App\Foundation\Tenancy\TenantContext;
use App\Models\LegalEntity;
use App\Models\Station;
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

    /**
     * Ermittelt den Einrichtungsfortschritt ausschließlich aus dem durch die
     * Partner-Panel-Middleware gebundenen TenantContext.
     *
     * Die Seite besitzt keine eigene Auswahl- oder Fallbacklogik. Dadurch kann sie bei
     * mehreren Memberships weder einen Mandanten erraten noch von den zentralen Status-
     * und Zeitregeln abweichen. Noch nicht implementierte Schritte bleiben offen.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);

        $tenantId = $context->id();
        $completedSteps = [
            LegalEntity::query()->where('tenant_id', $tenantId)->exists(),
            Station::query()->where('tenant_id', $tenantId)->exists(),
            false,
            false,
        ];
        $completedCount = 0;

        foreach ($completedSteps as $isComplete) {
            if (! $isComplete) {
                break;
            }

            $completedCount++;
        }

        return ['tenantProgress' => [
            'tenant_name' => $context->tenant->display_name,
            'completed_steps' => $completedSteps,
            'completed_count' => $completedCount,
            'current_step' => min($completedCount + 1, count($completedSteps)),
            'total_steps' => count($completedSteps),
        ]];
    }
}
