<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Enums\TenantType;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Repräsentiert den isolierten Daten- und Vertragsraum eines Tankstellenpartners.
 *
 * Das Modell selbst trägt naturgemäß keine `tenant_id`, weil es die Wurzel des Scopes ist.
 * Operative Kindmodelle müssen den Mandanten später unveränderlich referenzieren.
 */
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Erzeugt vor der ersten Speicherung eine nicht erratbare öffentliche ULID.
     */
    protected static function booted(): void
    {
        self::creating(function (Tenant $tenant): void {
            $tenant->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * Liefert den genau einen fachlich verantwortlichen Inhaber des Mandanten.
     *
     * Ownership ist absichtlich keine Membership-Rolle. Die verpflichtende Fremdschlüsselspalte
     * bildet damit eine einzige, nicht widersprüchliche Owner-Wahrheit. Ein späterer Wechsel
     * darf ausschließlich über einen transaktionalen Ownership-Service erfolgen.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Liefert alle Memberships dieses Mandanten; der aufrufende Dienst muss autorisiert sein.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * Liefert die genau eine Testphase des Mandanten.
     */
    public function trial(): HasOne
    {
        return $this->hasOne(Trial::class);
    }

    /**
     * Liefert den datensparsam gehaltenen primären Geschäftskontakt des Mandanten.
     */
    public function businessContact(): HasOne
    {
        return $this->hasOne(TenantBusinessContact::class);
    }

    /**
     * Liefert alle rechtlichen Gesellschaften innerhalb dieses Mandanten.
     */
    public function legalEntities(): HasMany
    {
        return $this->hasMany(LegalEntity::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TenantType::class,
            'status' => TenantStatus::class,
            'onboarding_completed_at' => 'datetime',
        ];
    }
}
