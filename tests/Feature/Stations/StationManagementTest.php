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
use App\Modules\Stations\Application\Data\UpdateStationData;
use App\Modules\Stations\Application\Exceptions\PotentialStationDuplicateException;
use App\Modules\Stations\Application\LinkStationSourceReference;
use App\Modules\Stations\Application\UpdateStation;
use App\Modules\Stations\Domain\StationDetails;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function test_owner_updates_ground_data_without_changing_status_source_or_directory_link(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        $station = app(CreateStation::class)->handle(
            $context,
            $this->data($entity, $brand, externalId: 'edit-provider-id'),
            $user,
            'create-before-edit',
        );
        $station->forceFill([
            'status' => 'active',
            'latitude' => 50.56,
            'longitude' => 9.71,
            'source_verified_at' => now(),
        ])->save();
        $station->refresh();

        $updated = app(UpdateStation::class)->handle(
            $context,
            (string) $station->public_id,
            $this->updateData($station, $entity, $brand, name: 'Aral Fulda Neu', street: 'Neue Straße'),
            $user,
            'station-update-test',
        );

        self::assertSame('Aral Fulda Neu', $updated->name);
        self::assertSame('Neue Straße', $updated->street);
        self::assertSame('active', $updated->status);
        self::assertSame('external_search', $updated->source_type);
        self::assertNull($updated->latitude);
        self::assertNull($updated->longitude);
        self::assertNull($updated->source_verified_at);
        $this->assertDatabaseHas('station_source_references', ['station_id' => $station->getKey()]);

        $event = AuditEvent::query()->where('correlation_id', 'station-update-test')->sole();
        self::assertSame('station.updated', $event->event_type);
        self::assertStringContainsString('name', $event->metadata['changed_fields']);
        self::assertStringNotContainsString('Aral Fulda Neu', json_encode($event->metadata, JSON_THROW_ON_ERROR));
    }

    public function test_update_rejects_foreign_station_and_stale_form_version(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        [$foreignContext, $foreignUser, $foreignEntity, $foreignBrand] = $this->context();
        $foreignStation = app(CreateStation::class)->handle(
            $foreignContext,
            $this->data($foreignEntity, $foreignBrand),
            $foreignUser,
            'foreign-station',
        );

        try {
            app(UpdateStation::class)->handle(
                $context,
                (string) $foreignStation->public_id,
                $this->updateData($foreignStation, $entity, $brand),
                $user,
                'foreign-update',
            );
            self::fail('Eine fremde Station darf nicht aufgelöst werden.');
        } catch (ModelNotFoundException) {
            self::assertSame('Aral Petersberg', $foreignStation->fresh()->name);
        }

        $station = app(CreateStation::class)->handle($context, $this->data($entity, $brand), $user, 'own-station');
        $stale = $this->updateData($station, $entity, $brand, expectedVersion: '2000-01-01 00:00:00.000000');

        $this->expectException(ValidationException::class);
        app(UpdateStation::class)->handle($context, (string) $station->public_id, $stale, $user, 'stale-update');
    }

    public function test_update_to_existing_address_requires_documented_reason(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        $first = app(CreateStation::class)->handle($context, $this->data($entity, $brand, name: 'Erste'), $user, 'first-edit');
        $second = app(CreateStation::class)->handle(
            $context,
            new CreateStationData(
                (string) $entity->public_id,
                $brand->getKey(),
                'Zweite',
                null,
                'Andere Straße',
                '9',
                null,
                '36100',
                'Petersberg',
                'Hessen',
                'DE',
                'Europe/Berlin',
                'de',
                'manual',
            ),
            $user,
            'second-edit',
        );

        $this->expectException(PotentialStationDuplicateException::class);
        app(UpdateStation::class)->handle(
            $context,
            (string) $second->public_id,
            $this->updateData($second, $entity, $brand, street: $first->street),
            $user,
            'duplicate-edit',
        );
    }

    public function test_read_only_tenant_and_foreign_actor_cannot_update_station(): void
    {
        [$context, $user, $entity, $brand] = $this->context();
        $station = app(CreateStation::class)->handle($context, $this->data($entity, $brand), $user, 'before-guard');
        $foreignActor = User::factory()->create();

        try {
            app(UpdateStation::class)->handle(
                $context,
                (string) $station->public_id,
                $this->updateData($station, $entity, $brand),
                $foreignActor,
                'wrong-actor',
            );
            self::fail('Ein fremder Akteur darf die Membership des Kontexts nicht verwenden.');
        } catch (AuthorizationException) {
            self::assertSame('Aral Petersberg', $station->fresh()->name);
        }

        $context->tenant->status = TenantStatus::ReadOnly;
        $context->tenant->save();
        $readOnlyContext = new TenantContext($context->tenant->fresh()->load('trial'), $context->membership);

        $this->expectException(TenantReadOnlyException::class);
        app(UpdateStation::class)->handle(
            $readOnlyContext,
            (string) $station->public_id,
            $this->updateData($station, $entity, $brand, name: 'Verbotene Änderung'),
            $user,
            'read-only-update',
        );
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

    private function updateData(
        Station $station,
        LegalEntity $entity,
        FuelStationBrand $brand,
        string $name = 'Aral Petersberg',
        string $street = 'Petersberger Straße',
        ?string $expectedVersion = null,
    ): UpdateStationData {
        return new UpdateStationData(
            (string) $entity->public_id,
            $brand->getKey(),
            $name,
            null,
            $street,
            '101',
            null,
            '36100',
            'Petersberg',
            'Hessen',
            'DE',
            'Europe/Berlin',
            'de',
            $expectedVersion ?? $station->updated_at->format('Y-m-d H:i:s.u'),
            null,
        );
    }
}
