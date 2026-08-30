<?php

namespace App\Modules\Tenants\Application;

use App\Enums\TenantMembershipRole;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\TrialStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Trial;
use App\Models\User;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Legt Mandant, genau eine Owner-Membership und den 14-Tage-Trial atomar an.
 *
 * Der Dienst wird später sowohl von der bestätigten Selbstregistrierung als auch von der
 * manuellen Plattform-Einladung verwendet. Schlägt ein Schritt fehl, bleibt kein
 * unvollständiger oder ungeschützter Mandant zurück.
 */
final class CreateTenant
{
    /**
     * Erstellt den vollständigen Mandantenkern in einer Datenbanktransaktion.
     */
    public function handle(User $owner, CreateTenantData $data): Tenant
    {
        if ($owner->email_verified_at === null) {
            throw ValidationException::withMessages([
                'owner' => 'Der Inhaber muss seine E-Mail-Adresse vor der Mandantenanlage bestätigen.',
            ]);
        }

        Validator::make([
            'display_name' => $data->displayName,
            'country_code' => $data->countryCode,
            'default_locale' => $data->defaultLocale,
            'timezone' => $data->timezone,
        ], [
            'display_name' => ['required', 'string', 'max:160'],
            'country_code' => ['required', 'string', 'size:2'],
            'default_locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:[-_][A-Z]{2})?$/'],
            'timezone' => ['required', 'timezone:all'],
        ])->validate();

        return DB::transaction(function () use ($owner, $data): Tenant {
            $trialStartedAt = now();

            $tenant = new Tenant;
            $tenant->owner_user_id = $owner->getKey();
            $tenant->display_name = $data->displayName;
            $tenant->type = $data->type;
            $tenant->status = TenantStatus::Onboarding;
            $tenant->country_code = mb_strtoupper($data->countryCode);
            $tenant->default_locale = mb_strtolower($data->defaultLocale);
            $tenant->timezone = $data->timezone;
            $tenant->save();

            $membership = new TenantMembership;
            $membership->user_id = $owner->getKey();
            $membership->role = TenantMembershipRole::Administrator;
            $membership->status = TenantMembershipStatus::Active;
            $membership->valid_from = $trialStartedAt;
            $tenant->memberships()->save($membership);

            $trial = new Trial;
            $trial->status = TrialStatus::Active;
            $trial->started_at = $trialStartedAt;
            $trial->ends_at = $trialStartedAt->copy()->addDays(14);
            $trial->extension_count = 0;
            $tenant->trial()->save($trial);

            return $tenant->load(['memberships', 'trial']);
        });
    }
}
