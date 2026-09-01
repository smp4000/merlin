<?php

namespace App\Filament\Pages;

use App\Foundation\Stations\ActiveStationContext;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\Station;
use App\Models\User;
use App\Modules\Stations\Application\ActivateStation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * Aktiviert einen Entwurf über die zentrale, tenant- und rollengeschützte
     * Anwendungslogik. Fachliche Fehler werden unmittelbar an der Übersicht angezeigt.
     */
    public function activate(string $stationPublicId, ActivateStation $service): void
    {
        /** @var User $actor */
        $actor = auth()->user();

        try {
            $service->handle(
                app(TenantContext::class),
                $stationPublicId,
                $actor,
                (string) Str::uuid(),
            );
        } catch (TenantReadOnlyException) {
            Notification::make()->title(__('stations.search.read_only'))->danger()->send();

            return;
        } catch (ModelNotFoundException) {
            Notification::make()->title(__('stations.validation.station_invalid'))->danger()->send();

            return;
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? __('stations.validation.activation_failed'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title(__('stations.notifications.activated'))->success()->send();
    }

    /**
     * Wählt eine bereits aktive Station direkt aus der Übersicht als Arbeitskontext.
     * Die Stations-ID wird im Dienst erneut gegen Tenant und Status geprüft.
     */
    public function selectForWork(string $stationPublicId, ActiveStationContext $stationContext): mixed
    {
        try {
            $stationContext->select(app(TenantContext::class), $stationPublicId);
        } catch (ModelNotFoundException) {
            Notification::make()->title(__('stations.selection.invalid'))->danger()->send();

            return null;
        }

        Notification::make()->title(__('stations.selection.selected'))->success()->send();

        return $this->redirect(self::getUrl(), navigate: true);
    }

    /**
     * Liest Datensätze niemals global, sondern ausschließlich über den Request-Tenant.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);
        $activeStation = app(ActiveStationContext::class)->current($context);

        return [
            'stations' => Station::query()
                ->where('tenant_id', $context->id())
                ->with(['brand', 'legalEntity', 'sourceReferences'])
                ->orderBy('name')
                ->get(),
            'createUrl' => StationCreate::getUrl(),
            'activeStationPublicId' => $activeStation?->public_id,
        ];
    }
}
