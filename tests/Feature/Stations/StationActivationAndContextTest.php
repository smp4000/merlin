<?php

namespace Tests\Feature\Stations;

use App\Enums\LegalEntityStatus;
use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Filament\Pages\StationOverview;
use App\Filament\Pages\StationSelection;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\AuditEvent;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Stations\Application\ActivateStation;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prüft die getrennten Regeln für fachliche Aktivierung und aktive Arbeitstankstelle.
 */
final class StationActivationAndContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_activates_complete_draft_with_minimized_audit(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Aktivierungsbetrieb');
        $station = $this->station($tenant, $entity, $brand, 'Vollständiger Entwurf', 'draft');
        $context = $this->context($tenant);

        self::assertSame(LegalEntityStatus::Active, $station->fresh()->legalEntity->status);
        self::assertSame('active', $station->fresh()->brand->status);
        self::assertSame(['DE'], $station->fresh()->brand->country_codes);
        self::assertSame('de', $station->fresh()->default_locale);

        $activated = app(ActivateStation::class)->handle(
            $context,
            (string) $station->public_id,
            $user,
            'activation-test',
        );

        self::assertSame('active', $activated->status);
        self::assertNotNull($activated->activated_at);
        $event = AuditEvent::query()->where('correlation_id', 'activation-test')->sole();
        self::assertSame('station.activated', $event->event_type);
        self::assertSame('draft', $event->metadata['previous_status']);
        self::assertArrayNotHasKey('name', $event->metadata);
    }

    public function test_incomplete_and_foreign_drafts_cannot_be_activated(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Eigener Betrieb');
        $incomplete = $this->station($tenant, $entity, null, 'Unvollständiger Entwurf', 'draft');
        [, $foreignTenant, $foreignEntity, $foreignBrand] = $this->partner('Fremder Betrieb');
        $foreign = $this->station($foreignTenant, $foreignEntity, $foreignBrand, 'Fremder Entwurf', 'draft');
        $context = $this->context($tenant);

        try {
            app(ActivateStation::class)->handle($context, (string) $incomplete->public_id, $user, 'incomplete');
            self::fail('Eine Station ohne Marke darf nicht aktiviert werden.');
        } catch (ValidationException) {
            self::assertSame('draft', $incomplete->fresh()->status);
        }

        $this->expectException(ModelNotFoundException::class);
        app(ActivateStation::class)->handle($context, (string) $foreign->public_id, $user, 'foreign');
    }

    public function test_foreign_actor_and_read_only_tenant_cannot_activate_draft(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Geschützter Betrieb');
        $draft = $this->station($tenant, $entity, $brand, 'Geschützter Entwurf', 'draft');
        $context = $this->context($tenant);

        try {
            app(ActivateStation::class)->handle(
                $context,
                (string) $draft->public_id,
                User::factory()->create(),
                'wrong-actor',
            );
            self::fail('Ein fremder Akteur darf den Stationsstatus nicht verändern.');
        } catch (AuthorizationException) {
            self::assertSame('draft', $draft->fresh()->status);
        }

        $context->tenant->status = TenantStatus::ReadOnly;
        $context->tenant->save();
        $readOnlyContext = $this->context($context->tenant->fresh());

        $this->expectException(TenantReadOnlyException::class);
        app(ActivateStation::class)->handle(
            $readOnlyContext,
            (string) $draft->public_id,
            $user,
            'read-only',
        );
    }

    public function test_overview_exposes_activation_and_livewire_activates_draft(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Livewire Aktivierung');
        $draft = $this->station($tenant, $entity, $brand, 'Entwurf Fulda', 'draft');

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/stationen')
            ->assertOk()
            ->assertSeeText('Aktivieren');

        app()->instance(TenantContext::class, $this->context($tenant));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(StationOverview::class)
            ->call('activate', (string) $draft->public_id)
            ->assertHasNoErrors();

        self::assertSame('active', $draft->fresh()->status);
    }

    public function test_station_selection_lists_only_active_stations_and_switches_context(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Mehrstationsbetrieb');
        $first = $this->station($tenant, $entity, $brand, 'Aral Fulda', 'active');
        $second = $this->station($tenant, $entity, $brand, 'Aral Petersberg', 'active');
        $this->station($tenant, $entity, $brand, 'Noch Entwurf', 'draft');

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeText('Tankstelle auswählen');

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/tankstelle-auswaehlen')
            ->assertOk()
            ->assertSeeText($first->name)
            ->assertSeeText($second->name)
            ->assertDontSeeText('Noch Entwurf');

        app()->instance(TenantContext::class, $this->context($tenant));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::withQueryParams([])
            ->test(StationSelection::class)
            ->call('selectStation', (string) $second->public_id)
            ->assertRedirect();

        self::assertSame($second->public_id, session('active_station_public_id'));
    }

    public function test_foreign_and_draft_stations_cannot_be_selected_as_work_context(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Auswahlbetrieb');
        $draft = $this->station($tenant, $entity, $brand, 'Eigener Entwurf', 'draft');
        [, $foreignTenant, $foreignEntity, $foreignBrand] = $this->partner('Fremdauswahl');
        $foreign = $this->station($foreignTenant, $foreignEntity, $foreignBrand, 'Fremde Station', 'active');

        app()->instance(TenantContext::class, $this->context($tenant));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(StationSelection::class)
            ->call('selectStation', (string) $draft->public_id)
            ->assertNotified(__('stations.selection.invalid'));
        self::assertNull(session('active_station_public_id'));

        Livewire::test(StationSelection::class)
            ->call('selectStation', (string) $foreign->public_id)
            ->assertNotified(__('stations.selection.invalid'));
        self::assertNull(session('active_station_public_id'));
    }

    public function test_single_active_station_is_selected_automatically(): void
    {
        [$user, $tenant, $entity, $brand] = $this->partner('Einzelstationsbetrieb');
        $station = $this->station($tenant, $entity, $brand, 'Einzige aktive Station', 'active');

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeText('Einzige aktive Station')
            ->assertSessionHas('active_station_public_id', $station->public_id);
    }

    /** @return array{User, Tenant, LegalEntity, FuelStationBrand} */
    private function partner(string $name): array
    {
        $user = User::factory()->create();
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData($name, TenantType::SingleOperator));
        $entity = LegalEntity::query()->forceCreate([
            'tenant_id' => $tenant->getKey(),
            'legal_name' => $name,
            'legal_form' => 'Einzelunternehmen',
            'status' => 'active',
            'is_primary' => true,
            'street' => 'Musterweg',
            'house_number' => '1',
            'postal_code' => '36100',
            'city' => 'Petersberg',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'billing_email' => 'mail@example.test',
        ]);
        $brand = FuelStationBrand::query()->firstOrCreate(
            ['slug' => 'aral'],
            ['name' => 'Aral', 'country_codes' => ['DE'], 'status' => 'active'],
        );

        return [$user, $tenant, $entity, $brand];
    }

    private function context(Tenant $tenant): TenantContext
    {
        $tenant->load('trial', 'memberships');

        return new TenantContext($tenant, $tenant->memberships->firstOrFail());
    }

    private function station(
        Tenant $tenant,
        LegalEntity $entity,
        ?FuelStationBrand $brand,
        string $name,
        string $status,
    ): Station {
        return Station::query()->forceCreate([
            'tenant_id' => $tenant->getKey(),
            'legal_entity_id' => $entity->getKey(),
            'fuel_station_brand_id' => $brand?->getKey(),
            'name' => $name,
            'status' => $status,
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '36100',
            'city' => 'Petersberg',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
            'default_locale' => 'de',
            'source_type' => 'manual',
            'activated_at' => $status === 'active' ? now() : null,
        ]);
    }
}
