<?php

namespace Tests\Feature\Partners;

use App\Enums\LegalEntityIdentifierType;
use App\Models\LegalEntityIdentifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Prüft die vorwärtsgerichtete Übernahme realitätsnaher Pilotdaten ohne Klartextausgabe.
 */
final class PartnerCoreMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_partner_data_is_migrated_without_loss_or_multiple_primaries(): void
    {
        $migration = require database_path('migrations/2026_08_31_120000_harden_partner_legal_entity_model.php');
        $migration->down();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenantId = DB::table('tenants')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'owner_user_id' => $user->getKey(),
            'display_name' => 'Pilotbetrieb',
            'type' => 'company_group',
            'status' => 'active',
            'country_code' => 'DE',
            'default_locale' => 'de',
            'timezone' => 'Europe/Berlin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sensitiveValue = 'DE123456789';

        $this->insertLegacyEntity($tenantId, 'Erste Gesellschaft', 'sole_proprietorship', true, Crypt::encryptString($sensitiveValue));
        $this->insertLegacyEntity($tenantId, 'Zweite Gesellschaft', 'historische Sonderform', true, null);

        $migration->up();

        $entities = DB::table('legal_entities')->where('tenant_id', $tenantId)->orderBy('id')->get();
        self::assertCount(2, $entities);
        self::assertNotNull($entities[0]->public_id);
        self::assertNotNull($entities[0]->legal_form_id);
        self::assertNull($entities[0]->vat_id);
        self::assertSame('historische Sonderform', $entities[1]->legacy_legal_form_label);
        self::assertSame(1, $entities->where('is_primary', 1)->count());
        self::assertSame(1, $entities->whereNotNull('primary_tenant_guard')->count());
        self::assertDatabaseHas('tenant_business_contacts', [
            'tenant_id' => $tenantId,
            'email' => 'rechnung@example.test',
        ]);

        $identifier = LegalEntityIdentifier::query()
            ->where('tenant_id', $tenantId)
            ->where('type', LegalEntityIdentifierType::VatId)
            ->firstOrFail();
        self::assertTrue(hash_equals(hash('sha256', $sensitiveValue), hash('sha256', $identifier->value)));
        self::assertStringNotContainsString(
            $sensitiveValue,
            (string) DB::table('legal_entity_identifiers')->where('id', $identifier->getKey())->value('value'),
        );
    }

    private function insertLegacyEntity(
        int $tenantId,
        string $legalName,
        string $legalForm,
        bool $isPrimary,
        ?string $encryptedVatId,
    ): void {
        DB::table('legal_entities')->insert([
            'tenant_id' => $tenantId,
            'legal_name' => $legalName,
            'legal_form' => $legalForm,
            'is_primary' => $isPrimary,
            'status' => 'active',
            'street' => 'Petersberger Straße',
            'house_number' => '101',
            'postal_code' => '36100',
            'city' => 'Petersberg',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'billing_email' => 'rechnung@example.test',
            'vat_id' => $encryptedVatId,
            'vat_id_masked' => $encryptedVatId === null ? null : '•••••••6789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
