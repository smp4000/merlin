<?php

namespace Tests\Feature\Database;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\LocalAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Prüft die sichere und wiederholbare Anlage des lokalen Bootstrap-Administrators.
 */
final class LocalAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Der Seeder legt ein bestätigtes Konto an und erzeugt bei Wiederholung kein Duplikat.
     */
    public function test_local_admin_is_created_idempotently_with_a_hashed_password(): void
    {
        config()->set('merlin.bootstrap_admin', [
            'name' => 'Lokaler Administrator',
            'email' => 'admin@example.test',
            'password' => 'A-secure-local-password-123!',
        ]);

        $this->seed(LocalAdminSeeder::class);
        $this->seed(LocalAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@example.test')->sole();

        $this->assertSame('Lokaler Administrator', $admin->name);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->isPlatformSuperAdmin());
        $this->assertTrue(Hash::check('A-secure-local-password-123!', $admin->password));
        $this->assertSame(1, User::query()->where('email', 'admin@example.test')->count());
    }

    /**
     * Der durch den Seeder erzeugte Benutzer kann den echten Filament-Login durchlaufen.
     */
    public function test_local_admin_can_authenticate_in_the_filament_panel(): void
    {
        config()->set('merlin.bootstrap_admin', [
            'name' => 'Lokaler Administrator',
            'email' => 'admin@example.test',
            'password' => 'A-secure-local-password-123!',
        ]);

        $this->seed(LocalAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@example.test')->sole();

        Filament::setCurrentPanel(Filament::getPanel('platform'));

        Livewire::test(Login::class)
            ->set('data.email', 'admin@example.test')
            ->set('data.password', 'A-secure-local-password-123!')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin);
    }

    /**
     * Ein zu kurzes lokales Passwort wird abgewiesen, bevor ein Konto entsteht.
     */
    public function test_local_admin_rejects_an_insecure_password(): void
    {
        config()->set('merlin.bootstrap_admin', [
            'name' => 'Lokaler Administrator',
            'email' => 'admin@example.test',
            'password' => 'too-short',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->seed(LocalAdminSeeder::class);
    }

    /**
     * Der lokale Zugang darf nicht als unbeabsichtigter Produktions-Seeder dienen.
     */
    public function test_local_admin_cannot_be_seeded_in_production(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        config()->set('merlin.bootstrap_admin', [
            'name' => 'Lokaler Administrator',
            'email' => 'admin@example.test',
            'password' => 'A-secure-local-password-123!',
        ]);

        $this->expectException(RuntimeException::class);

        app(LocalAdminSeeder::class)->run();
    }
}
