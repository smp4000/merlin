<?php

namespace Tests\Feature\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Models\User;
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
}
