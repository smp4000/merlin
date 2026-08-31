<?php

namespace Tests\Feature\Filament;

use App\Enums\TenantType;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Platform\Pages\Dashboard as PlatformDashboard;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Prüft, dass das Backoffice die Merlin-Seiten und nicht die Filament-Standardseiten nutzt.
 */
final class MerlinThemeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Das Panel muss Login, Dashboard und kompiliertes Merlin-Theme explizit registrieren.
     */
    public function test_partner_and_platform_panels_use_separate_merlin_foundations(): void
    {
        $partnerPanel = Filament::getPanel('admin');
        $platformPanel = Filament::getPanel('platform');

        $this->assertSame(Login::class, $partnerPanel->getLoginRouteAction());
        $this->assertContains(Dashboard::class, $partnerPanel->getPages());
        $this->assertNotContains(PlatformDashboard::class, $partnerPanel->getPages());
        $this->assertSame('resources/css/filament/admin/theme.css', $partnerPanel->getViteTheme());
        $this->assertSame(InitialsAvatarProvider::class, $partnerPanel->getDefaultAvatarProvider());

        $this->assertSame(Login::class, $platformPanel->getLoginRouteAction());
        $this->assertContains(PlatformDashboard::class, $platformPanel->getPages());
        $this->assertNotContains(Dashboard::class, $platformPanel->getPages());
        $this->assertSame('resources/css/filament/admin/theme.css', $platformPanel->getViteTheme());
        $this->assertFalse(Route::has('filament.admin.resources.partners.index'));
        $this->assertFalse(Route::has('filament.admin.resources.bank-directory-sources.index'));
        $this->assertTrue(Route::has('filament.platform.resources.partners.index'));
        $this->assertTrue(Route::has('filament.platform.resources.bank-directory-sources.index'));
    }

    /**
     * Die öffentliche Loginseite enthält das eigene übersetzte Merlin-Wording.
     */
    public function test_login_page_renders_merlin_branding(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Willkommen zurück')
            ->assertSee('Merlin Betriebsplattform');
    }

    /**
     * Angemeldete Benutzer erhalten das eigene Merlin-Dashboard statt der Framework-Widgets.
     */
    public function test_authenticated_user_sees_merlin_dashboard(): void
    {
        $user = User::factory()->create();
        app(CreateTenant::class)->handle($user, new CreateTenantData('ATS', TenantType::SingleOperator));

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Ihr Weg zur einsatzbereiten Plattform')
            ->assertSee('Partnerverwaltung')
            ->assertDontSee('ui-avatars.com');
    }

    /**
     * Bereits gespeicherte Gesellschaft und Tankstelle dürfen nicht erneut als offen erscheinen.
     */
    public function test_partner_dashboard_uses_the_real_tenant_setup_progress(): void
    {
        $user = User::factory()->create();
        $tenant = app(CreateTenant::class)->handle(
            $user,
            new CreateTenantData('ATS', TenantType::SingleOperator),
        );

        $legalEntity = LegalEntity::query()->forceCreate([
            'tenant_id' => $tenant->getKey(),
            'legal_name' => 'ATS',
            'legal_form' => 'sole_proprietorship',
            'is_primary' => true,
            'status' => 'active',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '36039',
            'city' => 'Fulda',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'billing_email' => 'rechnung@example.test',
        ]);
        Station::query()->forceCreate([
            'tenant_id' => $tenant->getKey(),
            'legal_entity_id' => $legalEntity->getKey(),
            'name' => 'ATS Fulda',
            'status' => 'active',
            'street' => 'Teststraße',
            'house_number' => '1',
            'postal_code' => '36039',
            'city' => 'Fulda',
            'region' => 'Hessen',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('ATS ist eingerichtet.')
            ->assertSee('Schritt 3 von 4')
            ->assertSee('Grunddaten erfasst');
    }

    /**
     * Die Plattformübersicht darf ohne Supportgrant keine operativen Tenantinhalte laden.
     */
    public function test_platform_dashboard_contains_no_tenant_content(): void
    {
        $owner = User::factory()->create();
        app(CreateTenant::class)->handle(
            $owner,
            new CreateTenantData('Vertraulicher Pilotbetrieb', TenantType::SingleOperator),
        );
        $platformAdmin = User::factory()->create(['is_platform_super_admin' => true]);

        $this->actingAs($platformAdmin)
            ->get('/platform/dashboard')
            ->assertOk()
            ->assertSee('Plattform und Partnerdaten bleiben getrennt')
            ->assertDontSee('Vertraulicher Pilotbetrieb');
    }
}
