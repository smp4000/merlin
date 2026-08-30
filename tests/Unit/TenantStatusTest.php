<?php

namespace Tests\Unit;

use App\Enums\TenantStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prüft die zentralen Zugriffs- und Schreibregeln des Tenant-Lifecycles.
 */
final class TenantStatusTest extends TestCase
{
    /**
     * @param  bool  $allowsAccess  Erwarteter lesender Zugriff.
     * @param  bool  $allowsWrites  Erwartete fachliche Schreibfähigkeit.
     */
    #[DataProvider('statusProvider')]
    public function test_status_capabilities(TenantStatus $status, bool $allowsAccess, bool $allowsWrites): void
    {
        $this->assertSame($allowsAccess, $status->allowsAccess());
        $this->assertSame($allowsWrites, $status->allowsBusinessWrites());
    }

    /**
     * @return iterable<string, array{TenantStatus, bool, bool}>
     */
    public static function statusProvider(): iterable
    {
        yield 'onboarding' => [TenantStatus::Onboarding, true, true];
        yield 'active' => [TenantStatus::Active, true, true];
        yield 'read only' => [TenantStatus::ReadOnly, true, false];
        yield 'closure requested' => [TenantStatus::ClosureRequested, true, false];
        yield 'suspended' => [TenantStatus::Suspended, false, false];
        yield 'closed' => [TenantStatus::Closed, false, false];
    }
}
