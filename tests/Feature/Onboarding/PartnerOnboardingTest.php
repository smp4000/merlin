<?php

namespace Tests\Feature\Onboarding;

use App\Enums\TenantType;
use App\Models\FuelStationBrand;
use App\Models\LegalEntityBankAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Beweist Happy Path, Verschlüsselung und serverseitige Tenantbindung des Onboardings.
 */
final class PartnerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_filament_login_instead_of_receiving_an_error(): void
    {
        $this->get(route('onboarding.show'))
            ->assertRedirect(route('filament.admin.auth.login'));

        // Der Fehlerbericht betraf ausdrücklich POST /onboarding nach Ablauf der Sitzung.
        // CSRF wird hier isoliert ausgeblendet, damit der Test die anschließende
        // Auth-Weiterleitung und nicht den vorgelagerten Formularschutz beweist.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('onboarding.store'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_onboarding_page_contains_all_confirmed_sections(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Test Partner', TenantType::SingleOperator));
        $this->brand();

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertSeeText('Rechnungsanschrift')
            ->assertSeeText('Tankstelle')
            ->assertSeeText('Stationsleitung')
            ->assertSeeText('Bankverbindung')
            ->assertSeeText('IBAN berechnen')
            ->assertSee('data-bank-code', false)
            ->assertSee('data-account-number', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_confirmed_owner_can_complete_onboarding_with_bank_account(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Test Partner', TenantType::SingleOperator));
        $brand = $this->brand();

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->post(route('onboarding.store'), $this->validPayload($brand->getKey()));

        $response->assertRedirect('/admin/dashboard');
        $this->assertDatabaseHas('legal_entities', ['tenant_id' => $tenant->getKey(), 'legal_name' => 'Welle Tankstellen']);
        $this->assertDatabaseHas('stations', ['tenant_id' => $tenant->getKey(), 'name' => 'Aral Petersberg']);
        $this->assertDatabaseHas('station_contacts', ['tenant_id' => $tenant->getKey(), 'email' => 'leitung@example.test']);

        $account = LegalEntityBankAccount::query()->firstOrFail();
        self::assertSame('DE89370400440532013000', $account->iban);
        self::assertStringNotContainsString('DE89370400440532013000', (string) DB::table('legal_entity_bank_accounts')->value('iban'));
        self::assertSame($tenant->getKey(), $account->tenant_id);
        self::assertSame('active', $tenant->fresh()->status->value);
    }

    public function test_conditional_bank_errors_are_rendered_with_understandable_german_labels(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Test Partner', TenantType::SingleOperator));
        $payload = $this->validPayload($this->brand()->getKey());
        unset($payload['account_holder'], $payload['confirm_iban_result']);

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->from(route('onboarding.show'))
            ->followingRedirects()
            ->post(route('onboarding.store'), $payload);

        $response->assertOk()
            ->assertSee('Kontoinhaber ist erforderlich, wenn eine Bankverbindung hinterlegt wird.')
            ->assertSee('Prüfung der Bankverbindung ist erforderlich, wenn eine Bankverbindung hinterlegt wird.')
            ->assertDontSee('validation.required_if');
    }

    public function test_sensitive_tax_and_bank_values_are_not_flashed_after_validation_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Test Partner', TenantType::SingleOperator));
        $payload = $this->validPayload($this->brand()->getKey());
        unset($payload['account_holder']);

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), $payload)
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('account_holder');

        self::assertNull(session()->getOldInput('vat_id'));
        self::assertNull(session()->getOldInput('account_number'));
        self::assertNull(session()->getOldInput('iban'));
    }

    public function test_request_cannot_move_records_to_another_tenant(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Eigener Partner', TenantType::SingleOperator));
        $foreignTenant = Tenant::factory()->create();
        $payload = $this->validPayload($this->brand()->getKey());
        $payload['tenant_id'] = $foreignTenant->getKey();

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->post(route('onboarding.store'), $payload)
            ->assertRedirect('/admin/dashboard');

        $this->assertDatabaseMissing('stations', ['tenant_id' => $foreignTenant->getKey(), 'name' => 'Aral Petersberg']);
        $this->assertDatabaseHas('stations', ['tenant_id' => $tenant->getKey(), 'name' => 'Aral Petersberg']);
    }

    public function test_completed_onboarding_cannot_be_submitted_twice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Eigener Partner', TenantType::SingleOperator));
        $payload = $this->validPayload($this->brand()->getKey());

        $this->actingAs($user)->withSession(['active_tenant_public_id' => $tenant->public_id]);
        $this->post(route('onboarding.store'), $payload)->assertRedirect('/admin/dashboard');
        $this->post(route('onboarding.store'), $payload)->assertForbidden();

        $this->assertDatabaseCount('legal_entities', 1);
        $this->assertDatabaseCount('stations', 1);
    }

    public function test_brand_from_another_country_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData('Eigener Partner', TenantType::SingleOperator));
        $brand = FuelStationBrand::query()->create([
            'slug' => 'austria-only', 'name' => 'Austria Only', 'country_codes' => ['AT'], 'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_public_id' => $tenant->public_id])
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), $this->validPayload($brand->getKey()))
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('fuel_station_brand_id');

        $this->assertDatabaseCount('stations', 0);
    }

    private function brand(): FuelStationBrand
    {
        return FuelStationBrand::query()->create([
            'slug' => 'aral', 'name' => 'Aral', 'country_codes' => ['DE'], 'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    private function validPayload(int $brandId): array
    {
        return [
            'legal_name' => 'Welle Tankstellen', 'legal_form' => 'de_sole_proprietorship',
            'billing_street' => 'Petersberger Straße', 'billing_house_number' => '101',
            'billing_postal_code' => '36100', 'billing_city' => 'Petersberg',
            'billing_region' => 'Hessen', 'billing_country_code' => 'DE',
            'billing_email' => 'rechnung@example.test', 'vat_id' => 'DE123456789',
            'station_name' => 'Aral Petersberg', 'fuel_station_brand_id' => $brandId,
            'station_street' => 'Petersberger Straße', 'station_house_number' => '101',
            'station_postal_code' => '36100', 'station_city' => 'Petersberg',
            'station_region' => 'Hessen', 'station_country_code' => 'DE',
            'manager_salutation' => 'female', 'manager_first_name' => 'Alexandra',
            'manager_last_name' => 'Welle', 'manager_email' => 'leitung@example.test',
            'manager_phone' => '0661655', 'add_bank_account' => '1',
            'account_holder' => 'Welle Tankstellen', 'bank_code' => '37040044',
            'account_number' => '532013000', 'confirm_iban_result' => '1',
        ];
    }
}
