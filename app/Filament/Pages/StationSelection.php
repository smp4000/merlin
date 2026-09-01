<?php

namespace App\Filament\Pages;

use App\Foundation\Stations\ActiveStationContext;
use App\Foundation\Tenancy\TenantContext;
use App\Models\Station;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Ermöglicht die bewusste Auswahl des Stationskontexts für operative Merlin-Module.
 */
final class StationSelection extends Page
{
    protected static ?string $slug = 'tankstelle-auswaehlen';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.station-selection';

    public function getTitle(): string|Htmlable
    {
        return __('stations.selection.title');
    }

    /**
     * Wählt serverseitig ausschließlich eine aktive Station des gebundenen Mandanten.
     */
    public function selectStation(string $stationPublicId, ActiveStationContext $stationContext): mixed
    {
        try {
            $stationContext->select(app(TenantContext::class), $stationPublicId);
        } catch (ModelNotFoundException) {
            Notification::make()->title(__('stations.selection.invalid'))->danger()->send();

            return null;
        }

        Notification::make()->title(__('stations.selection.selected'))->success()->send();

        return $this->redirect(Dashboard::getUrl(), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);
        $activeStation = app(ActiveStationContext::class)->current($context);

        return [
            'stations' => Station::query()
                ->where('tenant_id', $context->id())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'activeStation' => $activeStation,
            'stationOverviewUrl' => StationOverview::getUrl(),
        ];
    }
}
