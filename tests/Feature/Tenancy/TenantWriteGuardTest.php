<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TenantMembershipRole;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\TrialStatus;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Trial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prüft, dass der Tenant-Lifecycle fachliche Schreibvorgänge zentral sperrt.
 */
final class TenantWriteGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ein aktiver Mandant darf fachliche Änderungen ausführen.
     */
    public function test_active_tenant_allows_business_writes(): void
    {
        $context = $this->createContext(TenantStatus::Active, now()->addDay());

        app(TenantWriteGuard::class)->ensureBusinessWritesAllowed($context);

        $this->addToAssertionCount(1);
    }

    /**
     * Ein abgelaufener Trial bleibt lesbar, fachliche Änderungen werden jedoch blockiert.
     */
    public function test_read_only_tenant_rejects_business_writes(): void
    {
        $context = $this->createContext(TenantStatus::ReadOnly);

        $this->expectException(TenantReadOnlyException::class);

        app(TenantWriteGuard::class)->ensureBusinessWritesAllowed($context);
    }

    /**
     * Exakt am Trial-Ende greift die Sperre auch ohne bereits gelaufenen Scheduler.
     */
    public function test_trial_is_read_only_exactly_at_its_end(): void
    {
        $context = $this->createContext(TenantStatus::Active, now());

        $this->expectException(TenantReadOnlyException::class);

        app(TenantWriteGuard::class)->ensureBusinessWritesAllowed($context);
    }

    /**
     * Ein bereits abgelaufener Trial bleibt bei verspätetem Lifecycle-Job fail-closed.
     */
    public function test_expired_trial_is_read_only_even_if_tenant_status_is_still_active(): void
    {
        $context = $this->createContext(TenantStatus::Active, now()->subSecond());

        $this->expectException(TenantReadOnlyException::class);

        app(TenantWriteGuard::class)->ensureBusinessWritesAllowed($context);
    }

    /**
     * Ein fehlender Trial darf in der MVP-Phase nicht als bezahlter Zugang interpretiert werden.
     */
    public function test_active_tenant_without_trial_is_read_only(): void
    {
        $context = $this->createContext(TenantStatus::Active);

        $this->expectException(TenantReadOnlyException::class);

        app(TenantWriteGuard::class)->ensureBusinessWritesAllowed($context);
    }

    /**
     * Erzeugt einen konsistenten Context für Lifecycle-Tests.
     */
    private function createContext(TenantStatus $status, mixed $trialEndsAt = null): TenantContext
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['status' => $status]);

        $membership = new TenantMembership;
        $membership->user_id = $owner->getKey();
        $membership->role = TenantMembershipRole::Administrator;
        $membership->status = TenantMembershipStatus::Active;
        $membership->valid_from = now();
        $tenant->memberships()->save($membership);

        if ($trialEndsAt !== null) {
            $trial = new Trial;
            $trial->status = TrialStatus::Active;
            $trial->started_at = now()->subDays(14);
            $trial->ends_at = $trialEndsAt;
            $trial->extension_count = 0;
            $tenant->trial()->save($trial);
        }

        return new TenantContext($tenant, $membership);
    }
}
