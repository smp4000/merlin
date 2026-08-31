<?php

namespace App\Modules\Settings\Application;

use App\Enums\TenantMembershipRole;
use App\Enums\ThemePalette;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantWriteGuard;
use App\Models\TenantAppearanceSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Speichert eine geprüfte Akzentpalette im aktiven Mandanten und protokolliert den Wechsel.
 *
 * Der Dienst akzeptiert weder eine tenant_id noch freie Farben. Tenant, Membership und
 * Akteur stammen aus dem bereits geprüften Request-Kontext und werden erneut abgeglichen.
 */
final class SaveTenantAppearance
{
    public function __construct(
        private readonly TenantWriteGuard $writeGuard,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Ändert die Palette ausschließlich für aktive Administratoren des gebundenen Tenants.
     *
     * @throws AuthorizationException Wenn Akteur oder Rolle nicht zum Kontext passen.
     */
    public function handle(TenantContext $context, User $actor, ThemePalette $palette): TenantAppearanceSetting
    {
        if ((int) $context->membership->user_id !== (int) $actor->getKey()
            || $context->membership->role !== TenantMembershipRole::Administrator
            || ! $context->membership->isEffectiveAt(now())) {
            throw new AuthorizationException;
        }

        $this->writeGuard->ensureBusinessWritesAllowed($context);

        return DB::transaction(function () use ($context, $actor, $palette): TenantAppearanceSetting {
            $setting = TenantAppearanceSetting::query()
                ->where('tenant_id', $context->id())
                ->first() ?? new TenantAppearanceSetting;
            $previous = $setting->exists ? $setting->theme_key->value : ThemePalette::default()->value;
            $setting->tenant_id = $context->id();
            $setting->theme_key = $palette;
            $setting->updated_by_user_id = $actor->getKey();
            $setting->save();

            $this->audit->record(
                'tenant.appearance_changed',
                'tenant_appearance_setting',
                (string) $setting->getKey(),
                (string) Str::uuid(),
                ['previous_theme' => $previous, 'new_theme' => $palette->value],
                tenant: $context->tenant,
                actor: $actor,
            );

            return $setting;
        });
    }
}
