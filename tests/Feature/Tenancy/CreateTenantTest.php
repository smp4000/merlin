<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TenantMembershipRole;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Enums\TrialStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Prüft die atomare Anlage des Partner-Mandantenkerns.
 */
final class CreateTenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eine Anlage erzeugt exakt einen Owner und einen 14-Tage-Trial.
     */
    public function test_tenant_owner_membership_and_trial_are_created_together(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        $owner = User::factory()->create();

        $tenant = app(CreateTenant::class)->handle(
            $owner,
            new CreateTenantData('Pilot Partner', TenantType::SingleOperator),
        );

        $this->assertSame('Pilot Partner', $tenant->display_name);
        $this->assertSame(TenantStatus::Onboarding, $tenant->status);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $tenant->public_id);
        $this->assertCount(1, $tenant->memberships);
        $this->assertSame(TenantMembershipRole::Administrator, $tenant->memberships->sole()->role);
        $this->assertSame($owner->getKey(), $tenant->owner->getKey());
        $this->assertSame(TenantMembershipStatus::Active, $tenant->memberships->sole()->status);
        $this->assertSame(TrialStatus::Active, $tenant->trial->status);
        $this->assertSame(0, $tenant->trial->extension_count);
        $this->assertTrue($tenant->trial->ends_at->equalTo(now()->addDays(14)));
    }

    /**
     * Die Datenbank verhindert einen Mandanten ohne genau benannten Inhaber.
     */
    public function test_tenant_cannot_exist_without_an_owner(): void
    {
        $tenant = new Tenant;
        $tenant->display_name = 'Pilot ohne Inhaber';
        $tenant->type = TenantType::SingleOperator;
        $tenant->status = TenantStatus::Onboarding;
        $tenant->country_code = 'DE';
        $tenant->default_locale = 'de';
        $tenant->timezone = 'Europe/Berlin';

        $this->expectException(QueryException::class);

        $tenant->save();
    }

    /**
     * Ein unbestätigtes Konto darf noch keinen Partner-Mandanten erhalten.
     */
    public function test_unverified_owner_cannot_create_a_tenant(): void
    {
        $owner = User::factory()->unverified()->create();

        $this->expectException(ValidationException::class);

        app(CreateTenant::class)->handle(
            $owner,
            new CreateTenantData('Pilot Partner', TenantType::SingleOperator),
        );
    }
}
