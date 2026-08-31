<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Foundation\Tenancy\AccessibleTenantMemberships;
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
     * Trennt Plattform- und Partnerzugang bereits an Filaments zentraler Panel-Schranke.
     *
     * Eine Plattformrolle gewährt allein keinen Zugriff auf das Partner-Panel. Umgekehrt
     * kann eine Partner-Membership niemals das Plattform-Panel öffnen. Identitäten mit
     * beiden ausdrücklich vergebenen Berechtigungen dürfen beide Kontexte getrennt nutzen.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->email_verified_at === null) {
            return false;
        }

        return match ($panel->getId()) {
            'platform' => $this->isPlatformSuperAdmin(),
            'admin' => app(AccessibleTenantMemberships::class)->existsFor($this),
            default => false,
        };
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
