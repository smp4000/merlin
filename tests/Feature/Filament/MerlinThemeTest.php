<?php

namespace Tests\Feature\Filament;

use App\Enums\TenantType;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function test_admin_panel_uses_merlin_ui_foundation(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame(Login::class, $panel->getLoginRouteAction());
        $this->assertContains(Dashboard::class, $panel->getPages());
        $this->assertSame('resources/css/filament/admin/theme.css', $panel->getViteTheme());
        $this->assertSame(InitialsAvatarProvider::class, $panel->getDefaultAvatarProvider());
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
     * Ohne bewusste Auswahl dürfen mehrere Memberships nicht zu einer zufälligen
     * Mandantenanzeige zusammenfallen.
     */
    public function test_dashboard_does_not_guess_a_tenant_when_user_has_multiple_memberships(): void
    {
        $user = User::factory()->create();
        app(CreateTenant::class)->handle($user, new CreateTenantData('Betrieb Nord', TenantType::SingleOperator));
        app(CreateTenant::class)->handle($user, new CreateTenantData('Betrieb Süd', TenantType::SingleOperator));

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Betrieb Nord ist eingerichtet.')
            ->assertDontSee('Betrieb Süd ist eingerichtet.')
            ->assertSee('Schritt 1 von 4');
    }
}
