<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TenantType;
use App\Foundation\Tenancy\TenantContextSession;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prüft Paneltrennung, bewusste Betriebsauswahl und das Fail-closed-Verhalten alter Kontexte.
 */
final class TenantSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eine einzige wirksame Membership wird beim ersten Partneraufruf eindeutig gebunden.
     */
    public function test_single_accessible_tenant_is_selected_automatically(): void
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant($user, 'Betrieb Nord');

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Betrieb Nord')
            ->assertSee('Aktiver Betrieb')
            ->assertSessionHas(TenantContextSession::SESSION_KEY, $tenant->public_id);
    }

    /**
     * Mehrere Betriebe erzwingen vor dem Dashboard eine sichtbare, bewusste Auswahl.
     */
    public function test_multiple_tenants_require_explicit_selection(): void
    {
        $user = User::factory()->create();
        $north = $this->createTenant($user, 'Betrieb Nord');
        $south = $this->createTenant($user, 'Betrieb Süd');

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('tenant-selection.show'));

        $this->get(route('tenant-selection.show'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Betrieb Nord')
            ->assertSee('Betrieb Süd');

        $this->post(route('tenant-selection.store'), [
            'tenant_public_id' => $south->public_id,
        ])->assertRedirect('/admin/dashboard')
            ->assertSessionHas(TenantContextSession::SESSION_KEY, $south->public_id);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Betrieb Süd ist eingerichtet.')
            ->assertDontSee('Betrieb Nord ist eingerichtet.');

        $this->assertNotSame($north->public_id, session(TenantContextSession::SESSION_KEY));
    }

    /**
     * Eine fremde ULID wird neutral abgewiesen und verändert den bestehenden Kontext nicht.
     */
    public function test_foreign_tenant_cannot_be_selected(): void
    {
        $user = User::factory()->create();
        $ownTenant = $this->createTenant($user, 'Eigener Betrieb');
        $foreignTenant = $this->createTenant(User::factory()->create(), 'Fremder Betrieb');

        $this->actingAs($user)
            ->withSession([TenantContextSession::SESSION_KEY => $ownTenant->public_id])
            ->from(route('tenant-selection.show'))
            ->post(route('tenant-selection.store'), [
                'tenant_public_id' => $foreignTenant->public_id,
            ])
            ->assertRedirect(route('tenant-selection.show'))
            ->assertSessionHasErrors('tenant_public_id')
            ->assertSessionHas(TenantContextSession::SESSION_KEY, $ownTenant->public_id);
    }

    /**
     * Ein abgelaufener alter Kontext darf nicht still auf den letzten übrigen Betrieb wechseln.
     */
    public function test_expired_selected_membership_never_silently_switches_tenant(): void
    {
        $user = User::factory()->create();
        $expiredTenant = $this->createTenant($user, 'Abgelaufener Betrieb');
        $remainingTenant = $this->createTenant($user, 'Verbleibender Betrieb');

        $expiredTenant->memberships()->where('user_id', $user->getKey())->update([
            'valid_until' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->withSession([TenantContextSession::SESSION_KEY => $expiredTenant->public_id])
            ->get('/admin/dashboard')
            ->assertRedirect(route('tenant-selection.show'))
            ->assertSessionMissing(TenantContextSession::SESSION_KEY);

        $this->get(route('tenant-selection.show'))
            ->assertOk()
            ->assertSee('Verbleibender Betrieb')
            ->assertDontSee('Abgelaufener Betrieb');

        $this->assertNotSame($remainingTenant->public_id, session(TenantContextSession::SESSION_KEY));
    }

    /**
     * Plattformrolle und Partner-Membership gewähren niemals automatisch das jeweils andere Panel.
     */
    public function test_platform_and_partner_panels_are_role_separated(): void
    {
        $partner = User::factory()->create();
        $this->createTenant($partner, 'Partnerbetrieb');

        $this->actingAs($partner)->get('/platform/dashboard')->assertForbidden();

        $platformAdmin = User::factory()->create(['is_platform_super_admin' => true]);
        $this->actingAs($platformAdmin)->get('/platform/dashboard')->assertOk();
        $this->actingAs($platformAdmin)->get('/admin/dashboard')->assertForbidden();
    }

    /**
     * Die Auswahlseite offenbart einem Benutzer ohne Membership keinerlei Mandantendaten.
     */
    public function test_user_without_membership_cannot_open_tenant_selection(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tenant-selection.show'))
            ->assertForbidden();
    }

    /**
     * Eine später widerrufene E-Mail-Bestätigung sperrt auch die externen Partnerseiten.
     */
    public function test_unverified_identity_cannot_use_selection_or_onboarding(): void
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant($user, 'Gesperrter Betrieb');
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->withSession([TenantContextSession::SESSION_KEY => $tenant->public_id])
            ->get(route('tenant-selection.show'))
            ->assertForbidden();

        $this->get(route('onboarding.show'))->assertForbidden();
    }

    private function createTenant(User $owner, string $name): Tenant
    {
        return app(CreateTenant::class)->handle(
            $owner,
            new CreateTenantData($name, TenantType::SingleOperator),
        );
    }
}
