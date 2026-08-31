<?php

namespace App\Filament\Pages;

use App\Foundation\Tenancy\TenantContext;
use App\Models\Station;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Zeigt ausschließlich die Stationen des aktuell gebundenen Partner-Mandanten.
 */
final class StationOverview extends Page
{
    protected static ?string $slug = 'stationen';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected string $view = 'filament.pages.station-overview';

    public static function getNavigationLabel(): string
    {
        return __('stations.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('stations.navigation.group');
    }

    public function getTitle(): string|Htmlable
    {
        return __('stations.overview.title');
    }

    /**
     * Liest Datensätze niemals global, sondern ausschließlich über den Request-Tenant.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);

        return [
            'stations' => Station::query()
                ->where('tenant_id', $context->id())
                ->with(['brand', 'legalEntity', 'sourceReferences'])
                ->orderBy('name')
                ->get(),
            'createUrl' => StationCreate::getUrl(),
        ];
    }
}
