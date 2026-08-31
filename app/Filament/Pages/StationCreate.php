<?php

namespace App\Filament\Pages;

use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Stations\Application\CreateStation;
use App\Modules\Stations\Application\Data\CreateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use App\Modules\Stations\Application\Exceptions\StationSearchUnavailableException;
use App\Modules\Stations\Application\LinkStationSourceReference;
use App\Modules\Stations\Contracts\StationSearchProvider;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Führt Partner durch Suche, Trefferprüfung und tenantgebundene Stationsanlage.
 *
 * Externe IDs und Stammdaten werden bei Auswahl und Speichern serverseitig erneut
 * verifiziert. Öffentliche Livewire-Properties gelten niemals als Autoritätsquelle.
 */
final class StationCreate extends Page
{
    protected static ?string $slug = 'stationen/anlegen';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.station-create';

    public string $postalCode = '';

    public int $radius = 10;

    /** @var list<array<string, mixed>> */
    public array $searchResults = [];

    public ?string $searchWarning = null;

    public bool $manualMode = false;

    public bool $detailsVisible = false;

    public bool $duplicateWarning = false;

    public ?string $selectedReference = null;

    public ?string $linkStationPublicId = null;

    /** @var array<string, mixed>|null */
    public ?array $linkComparison = null;

    public string $legalEntityPublicId = '';

    public ?int $brandId = null;

    public string $name = '';

    public string $shortName = '';

    public string $street = '';

    public string $houseNumber = '';

    public string $addressAddition = '';

    public string $city = '';

    public string $region = '';

    public string $countryCode = 'DE';

    public string $timezone = 'Europe/Berlin';

    public string $defaultLocale = 'de';

    public string $duplicateReason = '';

    /** Initialisiert Betreiber und optional eine bestehende Station zur Verknüpfung. */
    public function mount(): void
    {
        $context = app(TenantContext::class);
        $this->legalEntityPublicId = (string) LegalEntity::query()
            ->where('tenant_id', $context->id())
            ->orderByDesc('is_primary')
            ->value('public_id');

        $requestedStation = request()->query('station');
        if (is_string($requestedStation) && Station::query()
            ->where('tenant_id', $context->id())
            ->where('public_id', $requestedStation)
            ->exists()) {
            $this->linkStationPublicId = $requestedStation;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->linkStationPublicId === null
            ? __('stations.create.title')
            : __('stations.link.title');
    }

    /** Führt eine rate-limit-fähige, optionale Suche aus und protokolliert nur Minimaldaten. */
    public function search(
        StationSearchProvider $provider,
        AuditRecorder $audit,
        TenantWriteGuard $writeGuard,
    ): void {
        $this->validate([
            'postalCode' => ['required', 'regex:/^\d{5}$/'],
            'radius' => ['required', Rule::in(config('merlin.station_search.radii'))],
        ], attributes: [
            'postalCode' => __('stations.fields.postal_code'),
            'radius' => __('stations.fields.radius'),
        ]);

        $context = app(TenantContext::class);
        $rateLimitKey = 'station-search:'.$context->id().'|'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $this->addError('postalCode', __('stations.search.rate_limited'));

            return;
        }

        try {
            $writeGuard->ensureBusinessWritesAllowed($context);
            RateLimiter::hit($rateLimitKey, 60);
            $response = $provider->search($this->postalCode, $this->radius);
            $this->searchResults = array_map(fn ($result): array => $result->toArray(), $response->results);
            $this->searchWarning = $response->warning;
        } catch (StationSearchUnavailableException) {
            $this->searchResults = [];
            $this->searchWarning = __('stations.search.unavailable');
        } catch (TenantReadOnlyException) {
            $this->searchResults = [];
            $this->searchWarning = __('stations.search.read_only');
        }

        $audit->record(
            'station.search_performed',
            'station_search',
            hash('sha256', $this->postalCode.'|'.$this->radius),
            (string) Str::uuid(),
            ['radius' => $this->radius, 'result_count' => count($this->searchResults)],
            tenant: $context->tenant,
            actor: auth()->user(),
        );
    }

    /** Wählt einen signierten Treffer und befüllt ausschließlich bestätigbare Formularwerte. */
    public function selectResult(string $reference, StationSearchProvider $provider): void
    {
        $details = $provider->details($reference);
        $this->selectedReference = $reference;

        if ($this->linkStationPublicId !== null) {
            $station = $this->linkStation();
            $this->linkComparison = [
                'current' => $this->formatAddress($station->street, $station->house_number, $station->postal_code, $station->city),
                'external' => $this->formatAddress($details->street, $details->houseNumber, $details->postalCode, $details->city),
                'external_name' => $details->name,
            ];

            return;
        }

        $this->manualMode = false;
        $this->detailsVisible = true;
        $this->name = $details->name;
        $this->street = $details->street;
        $this->houseNumber = $details->houseNumber;
        $this->postalCode = $details->postalCode;
        $this->city = $details->city;
        $this->brandId = $this->guessBrandId($details->name);
    }

    /** Öffnet eine vollständig manuelle Anlage ohne versteckte Providerabhängigkeit. */
    public function startManual(TenantWriteGuard $writeGuard): void
    {
        abort_if($this->linkStationPublicId !== null, 403);
        try {
            $writeGuard->ensureBusinessWritesAllowed(app(TenantContext::class));
        } catch (TenantReadOnlyException) {
            $this->searchWarning = __('stations.search.read_only');

            return;
        }
        $this->manualMode = true;
        $this->detailsVisible = true;
        $this->selectedReference = null;
        $this->searchWarning = null;
    }

    /** Speichert einen Entwurf über die zentrale, tenantgebundene Anwendungsgrenze. */
    public function save(CreateStation $creator, StationSearchProvider $provider): mixed
    {
        abort_if($this->linkStationPublicId !== null, 403);
        $validated = $this->validate($this->stationRules(), attributes: $this->attributeLabels());
        $externalDetails = $this->selectedReference === null ? null : $provider->details($this->selectedReference);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $creator->handle(
                app(TenantContext::class),
                new CreateStationData(
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
                    $externalDetails === null ? 'manual' : 'external_search',
                    $externalDetails?->providerKey,
                    $externalDetails?->externalStationId,
                    $externalDetails?->payloadChecksum,
                    $externalDetails?->latitude,
                    $externalDetails?->longitude,
                    $validated['duplicateReason'] ?: null,
                ),
                $actor,
                (string) Str::uuid(),
            );
        } catch (PotentialStationDuplicateException) {
            $this->duplicateWarning = true;
            $this->addError('duplicateReason', __('stations.validation.duplicate_reason_required'));

            return null;
        } catch (TenantReadOnlyException) {
            $this->addError('name', __('stations.search.read_only'));

            return null;
        }

        Notification::make()->title(__('stations.notifications.created'))->success()->send();

        return $this->redirect(StationOverview::getUrl(), navigate: true);
    }

    /** Bestätigt nur die Quellenverknüpfung; Stationswerte bleiben unverändert. */
    public function confirmLink(LinkStationSourceReference $linker, StationSearchProvider $provider): mixed
    {
        if ($this->linkStationPublicId === null || $this->selectedReference === null) {
            throw ValidationException::withMessages(['selectedReference' => __('stations.validation.reference_invalid')]);
        }

        /** @var User $actor */
        $actor = auth()->user();
        try {
            $linker->handle(
                app(TenantContext::class),
                $this->linkStationPublicId,
                $provider->details($this->selectedReference),
                $actor,
                (string) Str::uuid(),
            );
        } catch (TenantReadOnlyException) {
            $this->addError('selectedReference', __('stations.search.read_only'));

            return null;
        }

        Notification::make()->title(__('stations.notifications.linked'))->success()->send();

        return $this->redirect(StationOverview::getUrl(), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $context = app(TenantContext::class);

        return [
            'legalEntities' => LegalEntity::query()->where('tenant_id', $context->id())->orderBy('legal_name')->get(),
            'brands' => FuelStationBrand::query()->where('status', 'active')->orderBy('name')->get()
                ->filter(fn (FuelStationBrand $brand): bool => in_array($this->countryCode, $brand->country_codes, true)),
            'radii' => config('merlin.station_search.radii'),
            'searchEnabled' => (bool) config('merlin.station_search.enabled'),
            'linkStation' => $this->linkStationPublicId === null ? null : $this->linkStation(),
            'backUrl' => StationOverview::getUrl(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function stationRules(): array
    {
        return [
            'legalEntityPublicId' => ['required', 'string'],
            'brandId' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'shortName' => ['nullable', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:160'],
            'houseNumber' => ['required', 'string', 'max:30'],
            'addressAddition' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['required', 'regex:/^\d{5}$/'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'countryCode' => ['required', Rule::in(['DE'])],
            'timezone' => ['required', Rule::in(['Europe/Berlin'])],
            'defaultLocale' => ['required', Rule::in(config('merlin.registration.supported_locales'))],
            'duplicateReason' => [$this->duplicateWarning ? 'required' : 'nullable', 'string', 'max:500'],
        ];
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
            'postalCode' => __('stations.fields.postal_code'),
            'city' => __('stations.fields.city'),
            'region' => __('stations.fields.region'),
            'duplicateReason' => __('stations.fields.duplicate_reason'),
        ];
    }

    private function linkStation(): Station
    {
        return Station::query()
            ->where('tenant_id', app(TenantContext::class)->id())
            ->where('public_id', $this->linkStationPublicId)
            ->firstOrFail();
    }

    private function guessBrandId(string $stationName): ?int
    {
        return FuelStationBrand::query()->where('status', 'active')->get()
            ->first(fn (FuelStationBrand $brand): bool => str_contains(mb_strtolower($stationName), mb_strtolower($brand->name)))
            ?->getKey();
    }

    private function formatAddress(string $street, string $number, string $postalCode, string $city): string
    {
        return trim($street.' '.$number.', '.$postalCode.' '.$city);
    }
}
