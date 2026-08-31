<?php

namespace Tests\Feature\Stations;

use App\Enums\TenantType;
use App\Filament\Pages\StationCreate;
use App\Filament\Pages\StationOverview;
use App\Foundation\Tenancy\TenantContext;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Prüft Navigation, deutsche Suchtexte und Cross-Tenant-Ausblendung der Seiten. */
final class StationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_sees_only_stations_of_active_tenant_and_creation_link(): void
    {
        [$user, $tenant, $entity] = $this->partner('Eigener Betrieb');
        $own = $this->station($tenant, $entity, 'Eigene Aral');
        [, $foreignTenant, $foreignEntity] = $this->partner('Fremder Betrieb');
        $this->station($foreignTenant, $foreignEntity, 'Fremde Shell');

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/stationen');

        $response->assertOk()
            ->assertSeeText($own->name)
            ->assertDontSeeText('Fremde Shell')
            ->assertSeeText('Tankstelle anlegen')
            ->assertSeeText('Mit Tankstellenverzeichnis verknüpfen');
    }

    public function test_creation_page_contains_all_confirmed_radii_source_notice_and_manual_fallback(): void
    {
        [$user, $tenant] = $this->partner('Pilotbetrieb');

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get('/admin/stationen/anlegen');

        $response->assertOk()
            ->assertSeeText('Die Suche fragt benzinpreis-aktuell.de ab.')
            ->assertSeeText('Tankstelle ohne Suche manuell anlegen');

        foreach ([2, 5, 10, 15, 20, 25] as $radius) {
            $response->assertSeeText($radius.' km');
        }
    }

    public function test_guest_cannot_open_station_management(): void
    {
        $this->get('/admin/stationen')->assertRedirect('/admin/login');
        $this->get('/admin/stationen/anlegen')->assertRedirect('/admin/login');
    }

    public function test_livewire_manual_flow_creates_station_through_application_service(): void
    {
        [$user, $tenant] = $this->partner('Livewire Pilot');
        $tenant->load('trial', 'memberships');
        $context = new TenantContext($tenant, $tenant->memberships->firstOrFail());
        app()->instance(TenantContext::class, $context);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        Livewire::test(StationCreate::class)
            ->call('startManual')
            ->set('name', 'Manuelle Pilotstation')
            ->set('street', 'Teststraße')
            ->set('houseNumber', '7')
            ->set('postalCode', '36100')
            ->set('city', 'Petersberg')
            ->set('region', 'Hessen')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(StationOverview::getUrl());

        $this->assertDatabaseHas('stations', [
            'tenant_id' => $tenant->getKey(),
            'name' => 'Manuelle Pilotstation',
            'source_type' => 'manual',
            'status' => 'draft',
        ]);
    }

    /** @return array{User, Tenant, LegalEntity} */
    private function partner(string $name): array
    {
        $user = User::factory()->create();
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData($name, TenantType::SingleOperator));
        $entity = LegalEntity::query()->forceCreate([
            'tenant_id' => $tenant->getKey(), 'legal_name' => $name, 'status' => 'active', 'is_primary' => true,
            'street' => 'Musterweg', 'house_number' => '1', 'postal_code' => '36100', 'city' => 'Petersberg',
            'region' => 'Hessen', 'country_code' => 'DE', 'billing_email' => 'mail@example.test',
        ]);

        return [$user, $tenant, $entity];
    }

    private function station(Tenant $tenant, LegalEntity $entity, string $name): Station
    {
        return Station::query()->forceCreate([
            'tenant_id' => $tenant->getKey(), 'legal_entity_id' => $entity->getKey(), 'name' => $name,
            'status' => 'active', 'street' => 'Teststraße', 'house_number' => '1', 'postal_code' => '36100',
            'city' => 'Petersberg', 'region' => 'Hessen', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin',
        ]);
    }
}
