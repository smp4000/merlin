<?php

namespace App\Filament\Pages;

use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Stations\Application\Data\UpdateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use App\Modules\Stations\Application\UpdateStation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Bearbeitet freigegebene Stationsgrunddaten im aktiven TenantContext.
 *
 * Die öffentliche Stations-ID aus der URL wird stets gemeinsam mit der serverseitigen
 * tenant_id aufgelöst. Status, Quelle und externe Referenzen sind nur sichtbar und können
 * in diesem Grunddatenschnitt nicht verändert werden.
 */
final class StationEdit extends Page
{
    protected static ?string $slug = 'stationen/{station}/bearbeiten';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.station-edit';

    public string $stationPublicId = '';

    public string $stationVersion = '';

    public string $activeTab = 'general';

    public bool $duplicateWarning = false;

    public string $legalEntityPublicId = '';

    public ?int $brandId = null;

    public string $name = '';

    public string $shortName = '';

    public string $street = '';

    public string $houseNumber = '';

    public string $addressAddition = '';

    public string $postalCode = '';

    public string $city = '';

    public string $region = '';

    public string $countryCode = 'DE';

    public string $timezone = 'Europe/Berlin';

    public string $defaultLocale = 'de';

    public string $duplicateReason = '';

    /** Lädt die Station ausschließlich aus dem aktiven Mandanten und befüllt das Tabformular. */
    public function mount(string $station): void
    {
        $record = $this->station($station);
        $this->stationPublicId = (string) $record->public_id;
        $this->stationVersion = $record->updated_at->format('Y-m-d H:i:s.u');
        $this->legalEntityPublicId = (string) $record->legalEntity->public_id;
        $this->brandId = $record->fuel_station_brand_id;
        $this->name = $record->name;
        $this->shortName = (string) $record->short_name;
        $this->street = $record->street;
        $this->houseNumber = $record->house_number;
        $this->addressAddition = (string) $record->address_addition;
        $this->postalCode = $record->postal_code;
        $this->city = $record->city;
        $this->region = $record->region;
        $this->countryCode = $record->country_code;
        $this->timezone = $record->timezone;
        $this->defaultLocale = $record->default_locale;
    }

    public function getTitle(): string|Htmlable
    {
        return __('stations.edit.title');
    }

    /**
     * Wechselt ohne Zwischenvalidierung zu einem freigegebenen Bearbeitungstab.
     *
     * Die Eingaben bleiben als Livewire-Zustand erhalten. Manipulierte Tabnamen werden
     * nicht als dynamische Views oder Methoden interpretiert, sondern neutral verworfen.
     */
    public function selectTab(string $tab): void
    {
        if (! in_array($tab, ['general', 'address'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /** Speichert den Grunddatensatz über den konflikt- und tenantgeschützten Dienst. */
    public function save(UpdateStation $service): mixed
    {
        try {
            $validated = $this->validate($this->stationRules(), attributes: $this->attributeLabels());
        } catch (ValidationException $exception) {
            // Das erste fehlerhafte Feld bestimmt den sichtbaren Tab. Dadurch bleiben
            // Validierungsfehler auch bei frei wechselbaren Bereichen niemals verborgen.
            $this->activeTab = $this->tabForValidationErrors(array_keys($exception->errors()));

            throw $exception;
        }

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $station = $service->handle(
                app(TenantContext::class),
                $this->stationPublicId,
                new UpdateStationData(
                    $validated['legalEntityPublicId'],
                    $validated['brandId'] ?? null,
                    $validated['name'],
                    $validated['shortName'] ?: null,
                    $validated['street'],
                    $validated['houseNumber'],
                    $validated['addressAddition'] ?: null,
                    $validated['postalCode'],
                    $validated['city'],
                    $validated['region'],
                    $validated['countryCode'],
                    $validated['timezone'],
                    $validated['defaultLocale'],
                    $validated['stationVersion'],
                    $validated['duplicateReason'] ?: null,
                ),
                $actor,
                (string) Str::uuid(),
            );
        } catch (PotentialStationDuplicateException) {
            $this->duplicateWarning = true;
            $this->activeTab = 'address';
            $this->addError('duplicateReason', __('stations.validation.duplicate_reason_required'));

            return null;
        } catch (TenantReadOnlyException) {
            $this->addError('stationVersion', __('stations.search.read_only'));

            return null;
        }

        $this->stationVersion = $station->updated_at->format('Y-m-d H:i:s.u');
        Notification::make()->title(__('stations.notifications.updated'))->success()->send();

        return $this->redirect(StationOverview::getUrl(), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);
        $station = $this->station($this->stationPublicId);

        return [
            'station' => $station,
            'legalEntities' => LegalEntity::query()->where('tenant_id', $context->id())->orderBy('legal_name')->get(),
            'brands' => FuelStationBrand::query()->where('status', 'active')->orderBy('name')->get()
                ->filter(fn (FuelStationBrand $brand): bool => in_array($this->countryCode, $brand->country_codes, true)),
            'backUrl' => StationOverview::getUrl(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function stationRules(): array
    {
        return array_merge($this->generalRules(), $this->addressRules(), [
            'stationVersion' => ['required', 'string'],
            'duplicateReason' => [$this->duplicateWarning ? 'required' : 'nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    private function generalRules(): array
    {
        return [
            'legalEntityPublicId' => ['required', 'string'],
            'brandId' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'shortName' => ['nullable', 'string', 'max:80'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function addressRules(): array
    {
        return [
            'street' => ['required', 'string', 'max:160'],
            'houseNumber' => ['required', 'string', 'max:30'],
            'addressAddition' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['required', 'regex:/^\d{5}$/'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'countryCode' => ['required', Rule::in(['DE'])],
            'timezone' => ['required', Rule::in(['Europe/Berlin'])],
            'defaultLocale' => ['required', Rule::in(config('merlin.registration.supported_locales'))],
        ];
    }

    /**
     * Ordnet Validierungsfehler dem fachlich passenden Tab zu; allgemeine Stammdaten
     * haben Vorrang, wenn mehrere Bereiche gleichzeitig unvollständig sind.
     *
     * @param  array<int, string>  $fields
     */
    private function tabForValidationErrors(array $fields): string
    {
        $generalFields = array_keys($this->generalRules());

        return collect($fields)->contains(
            fn (string $field): bool => in_array($field, $generalFields, true),
        ) ? 'general' : 'address';
    }

    /** @return array<string, string> */
    private function attributeLabels(): array
    {
        return [
            'legalEntityPublicId' => __('stations.fields.legal_entity'),
            'brandId' => __('stations.fields.brand'),
            'name' => __('stations.fields.name'),
            'shortName' => __('stations.fields.short_name'),
            'street' => __('stations.fields.street'),
            'houseNumber' => __('stations.fields.house_number'),
            'addressAddition' => __('stations.fields.address_addition'),
            'postalCode' => __('stations.fields.postal_code'),
            'city' => __('stations.fields.city'),
            'region' => __('stations.fields.region'),
            'duplicateReason' => __('stations.fields.duplicate_reason'),
        ];
    }

    private function station(string $publicId): Station
    {
        return Station::query()
            ->where('tenant_id', app(TenantContext::class)->id())
            ->where('public_id', $publicId)
            ->with(['brand', 'legalEntity', 'sourceReferences'])
            ->firstOrFail();
    }
}
