<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bricht den Testlauf ab, bevor RefreshDatabase eine lokale MySQL-Datenbank erreichen kann.
 *
 * Dieser Test verwendet absichtlich kein RefreshDatabase und muss als erster Sicherheits-
 * Check separat ausführbar bleiben. Die erzwungene PHPUnit-Konfiguration ist zusätzlich
 * die technische Primärsicherung.
 */
final class TestingDatabaseIsolationTest extends TestCase
{
    public function test_phpunit_uses_only_ephemeral_sqlite_database(): void
    {
        self::assertSame('testing', app()->environment());
        self::assertSame('sqlite', DB::connection()->getDriverName());
        self::assertSame(':memory:', DB::connection()->getDatabaseName());
    }
}
