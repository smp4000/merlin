<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use RuntimeException;

/**
 * Legt den ersten lokalen Zugang zum Filament-Administrationsbereich an.
 *
 * Der Seeder ist bewusst auf Entwicklungs- und Testumgebungen begrenzt. Er ersetzt
 * weder das geplante Plattform-Rollenmodell noch einen sicheren produktiven
 * Provisionierungsprozess. Zugangsdaten stammen ausschließlich aus der lokalen
 * Umgebungskonfiguration und werden nicht im Quellcode hinterlegt.
 */
final class LocalAdminSeeder extends Seeder
{
    /**
     * Erstellt oder aktualisiert den lokalen Bootstrap-Administrator.
     *
     * Bei wiederholter Ausführung wird derselbe Benutzer anhand seiner E-Mail-Adresse
     * aktualisiert, sodass kein zweites Konto entsteht. Das Passwort wird dabei bewusst
     * auf den aktuellen lokalen ENV-Wert gesetzt und durch den Model-Cast gehasht.
     *
     * @throws InvalidArgumentException Bei ungültiger oder unvollständiger Konfiguration.
     * @throws RuntimeException Wenn der Seeder außerhalb einer erlaubten Umgebung läuft.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Der lokale Bootstrap-Administrator darf nur in local oder testing angelegt werden.',
            );
        }

        $name = trim((string) config('merlin.bootstrap_admin.name'));
        $email = trim((string) config('merlin.bootstrap_admin.email'));
        $password = (string) config('merlin.bootstrap_admin.password');

        if ($name === '') {
            throw new InvalidArgumentException('MERLIN_ADMIN_NAME darf nicht leer sein.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('MERLIN_ADMIN_EMAIL muss eine gültige E-Mail-Adresse sein.');
        }

        if (mb_strlen($password) < 16) {
            throw new InvalidArgumentException('MERLIN_ADMIN_PASSWORD muss mindestens 16 Zeichen lang sein.');
        }

        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->name = $name;
        $user->email = $email;
        $user->normalized_email = mb_strtolower($email);
        $user->email_verified_at = now();
        $user->is_platform_super_admin = true;
        $user->password = $password;
        $user->save();
    }
}
