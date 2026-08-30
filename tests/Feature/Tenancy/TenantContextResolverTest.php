<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TenantMembershipRole;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Foundation\Tenancy\TenantContextResolver;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Prüft die fail-closed Auflösung des TenantContext einschließlich Fremdmandantenfällen.
 */
final class TenantContextResolverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eine aktive, zeitlich wirksame Membership darf ausschließlich ihren Tenant binden.
     */
    public function test_active_membership_resolves_its_tenant(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $membership = $this->createMembership($tenant, $user);

        $context = app(TenantContextResolver::class)->resolve($user, $tenant->public_id);

        $this->assertTrue($context->tenant->is($tenant));
        $this->assertTrue($context->membership->is($membership));
    }

    /**
     * Ein fremder Benutzer erhält weder Context noch Existenzbestätigung des Mandanten.
     */
    public function test_user_cannot_resolve_a_foreign_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $stranger = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(TenantContextResolver::class)->resolve($stranger, $tenant->public_id);
    }

    /**
     * Eine suspendierte Membership bleibt unabhängig von ihrem Zeitraum unwirksam.
     */
    public function test_suspended_membership_is_rejected(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->createMembership($tenant, $user, [
            'status' => TenantMembershipStatus::Suspended,
            'valid_from' => now()->subDay(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(TenantContextResolver::class)->resolve($user, $tenant->public_id);
    }

    /**
     * Eine erst zukünftig beginnende Vertretung darf den Mandanten noch nicht öffnen.
     */
    public function test_future_membership_is_rejected(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->createMembership($tenant, $user, [
            'valid_from' => now()->addMinute(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(TenantContextResolver::class)->resolve($user, $tenant->public_id);
    }

    /**
     * Nach dem Ende einer zeitlich begrenzten Zuweisung endet auch der Mandantenzugriff.
     */
    public function test_expired_membership_is_rejected(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->createMembership($tenant, $user, [
            'valid_from' => now()->subDay(),
            'valid_until' => now()->subMinute(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(TenantContextResolver::class)->resolve($user, $tenant->public_id);
    }

    /**
     * Ein sicherheitsgesperrter Mandant kann trotz aktiver Membership nicht geöffnet werden.
     */
    public function test_suspended_tenant_is_rejected(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Suspended]);
        $this->createMembership($tenant, $user);

        $this->expectException(ModelNotFoundException::class);

        app(TenantContextResolver::class)->resolve($user, $tenant->public_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createMembership(Tenant $tenant, User $user, array $overrides = []): TenantMembership
    {
        $membership = new TenantMembership;
        $membership->user_id = $user->getKey();
        $membership->role = $overrides['role'] ?? TenantMembershipRole::Administrator;
        $membership->status = $overrides['status'] ?? TenantMembershipStatus::Active;
        $membership->valid_from = $overrides['valid_from'] ?? now()->subMinute();
        $membership->valid_until = $overrides['valid_until'] ?? null;
        $membership->suspended_at = $overrides['suspended_at'] ?? null;
        $membership->ended_at = $overrides['ended_at'] ?? null;
        $tenant->memberships()->save($membership);

        return $membership;
    }
}
