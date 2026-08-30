<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
/**
 * Repräsentiert die globale Anmeldeidentität einer Person.
 *
 * Partnerbezogene Rollen und Daten liegen nicht direkt am Benutzer, sondern in getrennten
 * TenantMemberships. Dadurch kann dieselbe Identität mehreren unabhängigen Mandanten
 * angehören, ohne deren Daten oder Berechtigungen zu vermischen.
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Hält den kanonischen E-Mail-Schlüssel bei jeder expliziten Identitätsänderung synchron.
     *
     * Merlin verwendet bewusst nur Kleinschreibung und Randbereinigung. Anbieterabhängige
     * Plus-, Punkt- oder Aliasregeln werden nicht erraten.
     */
    protected static function booted(): void
    {
        self::saving(function (User $user): void {
            $user->email = mb_strtolower(trim((string) $user->email));
            $user->normalized_email = $user->email;
        });
    }

    /**
     * Erlaubt den vorläufigen Backoffice-Zugang nur für bestätigte Identitäten.
     *
     * Diese zentrale Schranke verhindert insbesondere, dass ein lediglich angelegtes,
     * aber noch nicht per E-Mail bestätigtes Konto das Filament-Panel betreten kann.
     * Mit der Trennung in Plattform- und Partner-Panel wird diese Prüfung zusätzlich
     * um den jeweiligen Tenant-Kontext und die passenden Systemrollen erweitert.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Prüft die reservierte Plattformrolle für mandantenübergreifende Metadatenaktionen.
     *
     * Diese Kennung gewährt ausdrücklich keinen Zugriff auf operative Mandantendaten.
     * Solche Zugriffe benötigen später zusätzlich einen zeitlich begrenzten Supportgrant.
     */
    public function isPlatformSuperAdmin(): bool
    {
        return $this->is_platform_super_admin === true;
    }

    /**
     * Liefert alle Memberships dieser Identität für den sicheren Tenant-Auswahlprozess.
     */
    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_platform_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
