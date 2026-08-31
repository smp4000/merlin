<?php

namespace Tests\Feature\Stations;

use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\AuditEvent;
use App\Models\FuelStationBrand;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Stations\Application\CreateStation;
use App\Modules\Stations\Application\Data\CreateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use App\Modules\Stations\Application\LinkStationSourceReference;
use App\Modules\Stations\Domain\StationDetails;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Beweist Tenantbindung, Nur-Lesen-Schutz und die beiden Dublettenstufen der Anlage.
 */
final class StationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_tenant_scoped_draft_with_encrypted_source_reference(): void
    {
        [$context, $user, $entity, $brand] = $this->context();

        $station = app(CreateStation::class)->handle(
            $context,
            $this->data($entity, $brand, externalId: 'provider-secret-id'),
            $user,
            'station-create-test',
        );

        self::assertSame('draft', $station->status);
        self::assertSame($context->id(), $station->tenant_id);
        $this->assertDatabaseHas('station_source_references', [
            'tenant_id' => $context->id(), 'station_id' => $station->getKey(), 'provider_key' => 'benzinpreis_aktuell',
        ]);
        self::assertStringNotContainsString(
            'provider-secret-id',
            (string) \DB::table('station_source_references')->value('external_station_id'),
        );
        self::assertSame('station.created', AuditEvent::query()->sole()->event_type);
    }

    public function test_foreign_legal_entity_is_rejected_without_creating_station(): void
    {
        [$context, $user, , $brand] = $this->context();
        [, , $foreignEntity] = $this->context();

        $this->expectException(ValidationException::class);
        try {
            app(CreateStation::class)->handle($context, $this->data($foreignEntity, $brand), $user, 'foreign');
        } finally {
            $this->assertDatabaseCount('stations', 0);
        }
    }

    public function test_read_only_tenant_cannot_create_station(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        $context->tenant->status = TenantStatus::ReadOnly;
        $context->tenant->save();
        $context = new TenantContext($context->tenant->fresh()->load('trial'), $context->membership);

        $this->expectException(TenantReadOnlyException::class);
        app(CreateStation::class)->handle($context, $this->data($entity, $brand), $user, 'read-only');
    }

    public function test_matching_address_requires_reason_but_can_be_confirmed(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        app(CreateStation::class)->handle($context, $this->data($entity, $brand, name: 'Erste'), $user, 'first');

        $this->expectException(PotentialStationDuplicateException::class);
        try {
            app(CreateStation::class)->handle($context, $this->data($entity, $brand, name: 'Zweite'), $user, 'blocked');
        } finally {
            self::assertSame(1, Station::query()->count());
        }
    }

    public function test_matching_address_with_reason_creates_second_station_and_audits_confirmation(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        app(CreateStation::class)->handle($context, $this->data($entity, $brand, name: 'Erste'), $user, 'first');
        $data = $this->data($entity, $brand, name: 'Zweite', duplicateReason: 'Zwei getrennte Betriebe auf demselben Grundstück.');

        app(CreateStation::class)->handle($context, $data, $user, 'confirmed');

        self::assertSame(2, Station::query()->count());
        self::assertTrue((bool) AuditEvent::query()->where('correlation_id', 'confirmed')->sole()->metadata['soft_duplicate_confirmed']);
    }

    public function test_same_external_station_id_is_hard_blocked_even_with_duplicate_reason(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        app(CreateStation::class)->handle(
            $context,
            $this->data($entity, $brand, name: 'Erste', externalId: 'same-provider-id'),
            $user,
            'first',
        );

        $this->expectException(ValidationException::class);
        try {
            app(CreateStation::class)->handle(
                $context,
                $this->data(
                    $entity,
                    $brand,
                    name: 'Zweite',
                    externalId: 'same-provider-id',
                    duplicateReason: 'Bewusster Test der harten Sperre.',
                ),
                $user,
                'second',
            );
        } finally {
            self::assertSame(1, Station::query()->count());
        }
    }

    public function test_existing_onboarding_station_is_linked_without_overwriting_master_data(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        $station = app(CreateStation::class)->handle(
            $context,
            $this->data($entity, $brand, name: 'Bestätigter Merlin-Name'),
            $user,
            'manual',
        );

        app(LinkStationSourceReference::class)->handle(
            $context,
            (string) $station->public_id,
            new StationDetails(
                'benzinpreis_aktuell',
                'directory-id',
                'Abweichender Verzeichnisname',
                'Andere Straße',
                '9',
                '36100',
                'Petersberg',
                50.56,
                9.71,
                hash('sha256', 'directory-id'),
            ),
            $user,
            'link',
        );

        self::assertSame('Bestätigter Merlin-Name', $station->fresh()->name);
        self::assertSame('Petersberger Straße', $station->fresh()->street);
        $this->assertDatabaseHas('station_source_references', [
            'tenant_id' => $context->id(),
            'station_id' => $station->getKey(),
            'provider_key' => 'benzinpreis_aktuell',
        ]);
        self::assertFalse((bool) AuditEvent::query()->where('correlation_id', 'link')->sole()->metadata['master_data_changed']);
    }

    /** @return array{TenantContext, User, LegalEntity, FuelStationBrand} */
    private function context(): array
    {
        $user = User::factory()->create();
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Testbetrieb', TenantType::SingleOperator));
        $entity = LegalEntity::query()->forceCreate([
            'tenant_id' => $tenant->getKey(), 'legal_name' => 'Testbetrieb e. K.', 'status' => 'active',
            'is_primary' => true, 'street' => 'Musterweg', 'house_number' => '1', 'postal_code' => '36100',
            'city' => 'Petersberg', 'region' => 'Hessen', 'country_code' => 'DE', 'billing_email' => 'test@example.test',
        ]);
        $brand = FuelStationBrand::query()->firstOrCreate(
            ['slug' => 'aral'],
            ['name' => 'Aral', 'country_codes' => ['DE'], 'status' => 'active'],
        );
        $tenant->load('trial', 'memberships');

        return [new TenantContext($tenant, $tenant->memberships->firstOrFail()), $user, $entity, $brand];
    }

    private function data(
        LegalEntity $entity,
        FuelStationBrand $brand,
        string $name = 'Aral Petersberg',
        ?string $externalId = null,
        ?string $duplicateReason = null,
    ): CreateStationData {
        return new CreateStationData(
            (string) $entity->public_id,
            $brand->getKey(),
            $name,
            null,
            'Petersberger Straße',
            '101',
            null,
            '36100',
            'Petersberg',
            'Hessen',
            'DE',
            'Europe/Berlin',
            'de',
            $externalId === null ? 'manual' : 'external_search',
            $externalId === null ? null : 'benzinpreis_aktuell',
            $externalId,
            $externalId === null ? null : hash('sha256', $externalId),
            duplicateReason: $duplicateReason,
        );
    }
}
