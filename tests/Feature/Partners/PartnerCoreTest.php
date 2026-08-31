<?php

namespace Tests\Feature\Partners;

use App\Enums\LegalEntityIdentifierType;
use App\Enums\LegalEntityStatus;
use App\Enums\LegalFormStatus;
use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\LegalEntity;
use App\Models\LegalForm;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Partners\Application\ChangeLegalEntityStatus;
use App\Modules\Partners\Application\CreateLegalEntity;
use App\Modules\Partners\Application\Data\CreateLegalEntityData;
use App\Modules\Partners\Application\SetPrimaryLegalEntity;
use App\Modules\Partners\Application\StoreLegalEntityIdentifier;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Database\Seeders\LegalFormSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Beweist Katalogstabilität, Hauptgesellschaft, Verschlüsselung und Tenantgrenzen.
 */
final class PartnerCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_dach_legal_form_seeder_is_complete_and_idempotent(): void
    {
        $this->seed(LegalFormSeeder::class);
        $firstCount = LegalForm::query()->count();
        $this->seed(LegalFormSeeder::class);

        self::assertGreaterThanOrEqual(35, $firstCount);
        self::assertSame($firstCount, LegalForm::query()->count());
        self::assertTrue(LegalForm::query()->where('key', 'de_sole_proprietorship')->firstOrFail()->isSelectableFor('DE'));
        self::assertTrue(LegalForm::query()->where('key', 'at_gmbh')->firstOrFail()->isSelectableFor('AT'));
        self::assertTrue(LegalForm::query()->where('key', 'ch_ag')->firstOrFail()->isSelectableFor('CH'));
    }

    public function test_service_creates_tenant_bound_entity_contact_and_encrypted_identifier(): void
    {
        [$context] = $this->context('Eigener Betrieb');
        $entity = app(CreateLegalEntity::class)->handle($context, $this->entityData());

        $identifier = app(StoreLegalEntityIdentifier::class)->handle(
            $context,
            $entity->public_id,
            LegalEntityIdentifierType::VatId,
            'DE',
            'DE 123 456 789',
        );

        self::assertSame($context->id(), $entity->tenant_id);
        self::assertSame($context->id(), $context->tenant->businessContact()->firstOrFail()->tenant_id);
        self::assertTrue(hash_equals(hash('sha256', 'DE123456789'), hash('sha256', $identifier->value)));
        self::assertStringEndsWith('6789', $identifier->value_masked);
        self::assertStringNotContainsString(
            'DE123456789',
            (string) DB::table('legal_entity_identifiers')->where('id', $identifier->getKey())->value('value'),
        );
        self::assertNull(DB::table('legal_entities')->where('id', $entity->getKey())->value('vat_id'));
    }

    public function test_incomplete_draft_can_be_saved_without_placeholder_values(): void
    {
        [$context] = $this->context('Unternehmensgruppe');

        $draft = app(CreateLegalEntity::class)->handle($context, new CreateLegalEntityData(
            legalFormId: null,
            legalName: null,
            tradeName: 'Geplanter Standortbetrieb',
            status: LegalEntityStatus::Draft,
            makePrimary: true,
            street: null,
            houseNumber: null,
            addressAddition: null,
            postalCode: null,
            city: null,
            region: null,
            countryCode: null,
            businessEmail: null,
        ));

        self::assertSame(LegalEntityStatus::Draft, $draft->status);
        self::assertNull($draft->legal_form_id);
        self::assertNull($draft->legal_name);
        self::assertFalse($draft->is_primary);
        self::assertDatabaseCount('tenant_business_contacts', 0);
    }

    public function test_primary_entity_switch_is_atomic_and_keeps_exactly_one_primary(): void
    {
        [$context] = $this->context('Unternehmensgruppe');
        $first = app(CreateLegalEntity::class)->handle($context, $this->entityData('Erste GmbH', true));
        $second = app(CreateLegalEntity::class)->handle($context, $this->entityData('Zweite GmbH', false));

        self::assertSame(1, $this->activePrimaryCount($context->id()));
        app(SetPrimaryLegalEntity::class)->handle($context, $second->public_id);

        self::assertSame(1, $this->activePrimaryCount($context->id()));
        self::assertFalse($first->fresh()->is_primary);
        self::assertTrue($second->fresh()->is_primary);
        self::assertSame($context->id(), $second->fresh()->primary_tenant_guard);
    }

    public function test_database_guard_rejects_a_second_active_primary(): void
    {
        [$context] = $this->context('Geschützter Betrieb');
        app(CreateLegalEntity::class)->handle($context, $this->entityData('Erste GmbH', true));

        $this->expectException(QueryException::class);
        $this->insertDirectEntity($context->id(), 'Zweite GmbH', $context->id());
    }

    public function test_last_active_primary_cannot_be_deactivated(): void
    {
        [$context] = $this->context('Geschützter Betrieb');
        $entity = app(CreateLegalEntity::class)->handle($context, $this->entityData());

        try {
            app(ChangeLegalEntityStatus::class)->handle($context, $entity->public_id, LegalEntityStatus::Inactive);
            self::fail('Die letzte Hauptgesellschaft muss aktiv bleiben.');
        } catch (ValidationException $exception) {
            self::assertSame(
                'Die letzte aktive Hauptgesellschaft kann nicht deaktiviert werden.',
                $exception->errors()['status'][0],
            );
            self::assertSame(1, $this->activePrimaryCount($context->id()));
        }
    }

    public function test_deactivating_primary_promotes_another_active_entity_atomically(): void
    {
        [$context] = $this->context('Unternehmensgruppe');
        $first = app(CreateLegalEntity::class)->handle($context, $this->entityData('Erste GmbH', true));
        $second = app(CreateLegalEntity::class)->handle($context, $this->entityData('Zweite GmbH', false));

        app(ChangeLegalEntityStatus::class)->handle($context, $first->public_id, LegalEntityStatus::Inactive);

        self::assertSame(LegalEntityStatus::Inactive, $first->fresh()->status);
        self::assertTrue($second->fresh()->is_primary);
        self::assertSame(1, $this->activePrimaryCount($context->id()));
    }

    public function test_foreign_entity_is_not_revealed_or_changed_by_identifier_service(): void
    {
        [$ownContext] = $this->context('Eigener Betrieb');
        [$foreignContext] = $this->context('Fremder Betrieb');
        $foreignEntity = app(CreateLegalEntity::class)->handle($foreignContext, $this->entityData('Fremde GmbH'));

        try {
            app(StoreLegalEntityIdentifier::class)->handle(
                $ownContext,
                $foreignEntity->public_id,
                LegalEntityIdentifierType::NationalTaxNumber,
                'DE',
                '188 806 0014',
            );
            self::fail('Eine fremde Gesellschaft darf nicht aufgelöst werden.');
        } catch (ModelNotFoundException) {
            self::assertDatabaseCount('legal_entity_identifiers', 0);
        }
    }

    public function test_composite_foreign_key_rejects_cross_tenant_identifier_relation(): void
    {
        [$ownContext] = $this->context('Eigener Betrieb');
        [$foreignContext] = $this->context('Fremder Betrieb');
        $foreignEntity = app(CreateLegalEntity::class)->handle($foreignContext, $this->entityData('Fremde GmbH'));

        $this->expectException(QueryException::class);
        DB::table('legal_entity_identifiers')->insert([
            'tenant_id' => $ownContext->id(),
            'legal_entity_id' => $foreignEntity->getKey(),
            'type' => LegalEntityIdentifierType::VatId->value,
            'country_code' => 'DE',
            'value' => 'ciphertext',
            'value_masked' => '••••6789',
            'fingerprint' => str_repeat('a', 64),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_inactive_or_foreign_country_legal_form_is_rejected_without_entity_creation(): void
    {
        [$context] = $this->context('Eigener Betrieb');
        $form = LegalForm::query()->where('key', 'de_gmbh')->firstOrFail();
        $form->status = LegalFormStatus::Inactive;
        $form->save();

        try {
            app(CreateLegalEntity::class)->handle($context, $this->entityData(legalFormId: $form->getKey()));
            self::fail('Eine inaktive Rechtsform darf nicht neu verwendet werden.');
        } catch (ValidationException $exception) {
            self::assertSame('Die gewählte Rechtsform ist für dieses Land nicht verfügbar.', $exception->errors()['legal_form'][0]);
            self::assertDatabaseCount('legal_entities', 0);
        }
    }

    public function test_read_only_tenant_cannot_write_partner_core_data(): void
    {
        [$context, $tenant] = $this->context('Nur Lesen');
        $tenant->status = TenantStatus::ReadOnly;
        $tenant->save();
        $context = new TenantContext($tenant->fresh()->load('trial'), $context->membership);

        $this->expectException(TenantReadOnlyException::class);
        app(CreateLegalEntity::class)->handle($context, $this->entityData());
    }

    public function test_validation_exception_never_contains_complete_identifier(): void
    {
        [$context] = $this->context('Eigener Betrieb');
        $entity = app(CreateLegalEntity::class)->handle($context, $this->entityData());
        $sensitiveValue = str_repeat('X', 121);

        try {
            app(StoreLegalEntityIdentifier::class)->handle(
                $context,
                $entity->public_id,
                LegalEntityIdentifierType::EconomicId,
                'DE',
                $sensitiveValue,
            );
            self::fail('Die zu lange Kennung muss abgewiesen werden.');
        } catch (ValidationException $exception) {
            self::assertStringNotContainsString($sensitiveValue, $exception->getMessage());
            self::assertStringNotContainsString($sensitiveValue, json_encode($exception->errors(), JSON_THROW_ON_ERROR));
        }
    }

    /** @return array{TenantContext, Tenant} */
    private function context(string $displayName): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle(
            $user,
            new CreateTenantData($displayName, TenantType::CompanyGroup),
        );

        return [new TenantContext($tenant, $tenant->memberships->firstOrFail()), $tenant];
    }

    private function entityData(
        string $legalName = 'Welle Tankstellen GmbH',
        bool $makePrimary = true,
        ?int $legalFormId = null,
    ): CreateLegalEntityData {
        $legalFormId ??= LegalForm::query()->where('key', 'de_gmbh')->value('id');

        return new CreateLegalEntityData(
            legalFormId: (int) $legalFormId,
            legalName: $legalName,
            tradeName: null,
            status: LegalEntityStatus::Active,
            makePrimary: $makePrimary,
            street: 'Petersberger Straße',
            houseNumber: '101',
            addressAddition: null,
            postalCode: '36100',
            city: 'Petersberg',
            region: 'Hessen',
            countryCode: 'DE',
            businessEmail: 'kontakt@example.test',
        );
    }

    private function activePrimaryCount(int $tenantId): int
    {
        return LegalEntity::query()
            ->where('tenant_id', $tenantId)
            ->where('status', LegalEntityStatus::Active)
            ->where('is_primary', true)
            ->count();
    }

    private function insertDirectEntity(int $tenantId, string $legalName, int $primaryGuard): void
    {
        DB::table('legal_entities')->insert([
            'tenant_id' => $tenantId,
            'public_id' => (string) Str::ulid(),
            'legal_name' => $legalName,
            'legal_form' => 'de_gmbh',
            'legal_form_id' => LegalForm::query()->where('key', 'de_gmbh')->value('id'),
            'is_primary' => true,
            'primary_tenant_guard' => $primaryGuard,
            'status' => LegalEntityStatus::Active->value,
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '36037',
            'city' => 'Fulda',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'billing_email' => 'kontakt@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
