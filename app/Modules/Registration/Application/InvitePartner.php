<?php

namespace App\Modules\Registration\Application;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Foundation\Audit\AuditRecorder;
use App\Models\RegistrationIntent;
use App\Models\User;
use App\Modules\Registration\Application\Data\InvitePartnerData;
use App\Notifications\PartnerRegistrationConfirmation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Erstellt eine sichere Partner-Einladung aus dem Plattform-Panel.
 *
 * Vor der Bestätigung entstehen bewusst weder Benutzer, Mandant noch Trial. Der
 * vorgesehene Owner akzeptiert die rechtlichen Hinweise selbst und setzt sein Passwort
 * erst über den einmaligen Link. So kann der Super-Admin nicht stellvertretend zustimmen.
 */
final class InvitePartner
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    /**
     * Speichert die Einladung atomar und stellt die verschlüsselte Mail nach Commit zu.
     *
     * @throws ValidationException Wenn Rolle, Daten oder E-Mail-Zustand die Einladung verbieten.
     */
    public function handle(User $actor, InvitePartnerData $data): RegistrationIntent
    {
        if (! $actor->isPlatformSuperAdmin()) {
            throw ValidationException::withMessages([
                'authorization' => __('partners.validation.platform_admin_required'),
            ]);
        }

        $normalizedEmail = mb_strtolower(trim($data->email));

        Validator::make([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $normalizedEmail,
            'partner_display_name' => $data->partnerDisplayName,
            'country_code' => mb_strtoupper($data->countryCode),
            'locale' => mb_strtolower($data->locale),
        ], [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'partner_display_name' => ['required', 'string', 'max:160'],
            'country_code' => ['required', Rule::in(config('merlin.registration.supported_countries'))],
            'locale' => ['required', Rule::in(config('merlin.registration.supported_locales'))],
        ])->validate();

        $emailHash = hash_hmac('sha256', $normalizedEmail, (string) config('app.key'));

        if (User::query()->where('normalized_email', $normalizedEmail)->exists()
            || RegistrationIntent::query()->where('active_email_hash', $emailHash)->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => __('partners.validation.invitation_not_available'),
            ]);
        }

        $token = $this->newToken();

        try {
            $intent = DB::transaction(function () use ($actor, $data, $normalizedEmail, $emailHash, $token): RegistrationIntent {
                $intent = new RegistrationIntent;
                $intent->status = RegistrationStatus::EmailPending;
                $intent->source = RegistrationSource::PlatformInvitation;
                $intent->invited_by_user_id = $actor->getKey();
                $intent->email = $normalizedEmail;
                $intent->normalized_email = $normalizedEmail;
                $intent->active_email_hash = $emailHash;
                $intent->first_name = trim($data->firstName);
                $intent->last_name = trim($data->lastName);
                $intent->partner_display_name = trim($data->partnerDisplayName);
                $intent->tenant_type = $data->tenantType;
                $intent->country_code = mb_strtoupper($data->countryCode);
                $intent->locale = mb_strtolower($data->locale);
                $intent->confirmation_token_hash = hash('sha256', $token);
                $intent->token_expires_at = now()->addMinutes($this->tokenLifetime());
                $intent->last_confirmation_sent_at = now();
                $intent->save();

                $this->auditRecorder->record(
                    'platform.partner_invited',
                    'registration_intent',
                    $intent->public_id,
                    $data->correlationId,
                    [
                        'country_code' => $intent->country_code,
                        'locale' => $intent->locale,
                        'tenant_type' => $intent->tenant_type->value,
                    ],
                    actor: $actor,
                    channel: 'filament',
                );

                return $intent;
            });
        } catch (QueryException $exception) {
            if ($this->isExpectedDuplicateKey($exception)) {
                throw ValidationException::withMessages([
                    'owner_email' => __('partners.validation.invitation_not_available'),
                ]);
            }

            throw $exception;
        }

        Notification::route('mail', $intent->email)
            ->notify((new PartnerRegistrationConfirmation(
                $intent->public_id,
                $token,
                $this->tokenLifetime(),
            ))->locale($intent->locale)->afterCommit());

        return $intent;
    }

    /**
     * Erzeugt einen nicht erratbaren 256-Bit-Schlüssel für genau eine Einladung.
     */
    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Liefert die zentral festgelegte kurze Einladungsdauer.
     */
    private function tokenLifetime(): int
    {
        return (int) config('merlin.registration.token_lifetime_minutes', 60);
    }

    /**
     * Behandelt nur echte Unique-Races als neutrale fachliche Kollision.
     */
    private function isExpectedDuplicateKey(QueryException $exception): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => (int) ($exception->errorInfo[1] ?? 0) === 1062,
            'sqlite' => str_contains($exception->getMessage(), 'UNIQUE constraint failed'),
            default => false,
        };
    }
}
