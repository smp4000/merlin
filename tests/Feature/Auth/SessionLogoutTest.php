<?php

namespace Tests\Feature\Auth;

use App\Enums\TenantType;
use App\Foundation\Tenancy\TenantContextSession;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Beweist die panelunabhängige Abmeldung einschließlich ungültiger Tenantkontexte.
 */
final class SessionLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_is_logged_out_and_redirected_to_platform_login(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
        ]);

        $this->actingAs($admin)
            ->withSession([
                TenantContextSession::SESSION_KEY => 'stale-tenant',
                'active_station_public_id' => 'stale-station',
                'tenant_permission_cache' => ['stale'],
            ])
            ->post(route('session.logout.platform'))
            ->assertRedirect(route('filament.platform.auth.login'));

        $this->assertGuest();
        self::assertNull(session()->get(TenantContextSession::SESSION_KEY));
        self::assertNull(session()->get('active_station_public_id'));
        self::assertNull(session()->get('tenant_permission_cache'));
    }

    public function test_partner_can_logout_even_after_membership_has_expired(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = app(CreateTenant::class)->handle(
            $user,
            new CreateTenantData('Abgelaufener Betrieb', TenantType::SingleOperator),
        );
        $membership = $tenant->memberships->firstOrFail();
        $membership->valid_until = now()->subMinute();
        $membership->save();

        $this->actingAs($user)
            ->withSession([TenantContextSession::SESSION_KEY => $tenant->public_id])
            ->post(route('session.logout.partner'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
        self::assertNull(session()->get(TenantContextSession::SESSION_KEY));
    }

    public function test_logout_endpoints_reject_get_requests(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/platform/abmelden')->assertMethodNotAllowed();
        $this->actingAs($user)->get('/admin/abmelden')->assertMethodNotAllowed();
    }

    public function test_expired_guest_session_still_reaches_the_correct_panel_login(): void
    {
        $this->post(route('session.logout.platform'))
            ->assertRedirect(route('filament.platform.auth.login'));
        $this->post(route('session.logout.partner'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    public function test_both_user_menus_post_to_the_central_logout_endpoints(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
        ]);
        $this->actingAs($user);

        $platformPanel = Filament::getPanel('platform');
        Filament::setCurrentPanel($platformPanel);
        $platformLogout = $platformPanel->getUserMenuItems()['logout'];
        self::assertSame(route('session.logout.platform'), $platformLogout->getUrl());
        self::assertTrue($platformLogout->shouldPostToUrl());

        $partnerPanel = Filament::getPanel('admin');
        Filament::setCurrentPanel($partnerPanel);
        $partnerLogout = $partnerPanel->getUserMenuItems()['logout'];
        self::assertSame(route('session.logout.partner'), $partnerLogout->getUrl());
        self::assertTrue($partnerLogout->shouldPostToUrl());
    }
}
