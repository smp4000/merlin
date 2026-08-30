<?php

namespace App\Modules\Registration\Application;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Legal\LegalDocumentRepository;
use App\Models\ConsentRecord;
use App\Models\RegistrationIntent;
use App\Models\User;
use App\Modules\Registration\Application\Data\StartPartnerRegistrationData;
use App\Notifications\PartnerRegistrationConfirmation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Startet oder erneuert eine datenarme Partnerregistrierung ohne Existenzoffenlegung.
 *
 * Bestehende Benutzerkonten werden niemals verändert. Ein noch offener Vorgang erhält
 * nur einen neuen Token; die ursprünglichen Registrierungs- und Zustimmungsdaten bleiben
 * unverändert. Nach außen verwendet der Controller in allen Fällen dieselbe Antwort.
 */
final class StartPartnerRegistration
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
        private readonly LegalDocumentRepository $legalDocuments,
    ) {}

    /**
     * Speichert den Vorgang atomar und versendet den Token erst nach dem Commit.
     */
    public function handle(StartPartnerRegistrationData $data): void
    {
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

        $this->validateConsentEvidence($data);

        if (User::query()->where('normalized_email', $normalizedEmail)->exists()) {
            return;
        }

        $token = $this->newToken();
        $emailHash = hash_hmac('sha256', $normalizedEmail, (string) config('app.key'));

        try {
            $intent = DB::transaction(function () use ($data, $normalizedEmail, $emailHash, $token): RegistrationIntent {
                $existingIntent = RegistrationIntent::query()
                    ->where('active_email_hash', $emailHash)
                    ->lockForUpdate()
                    ->first();

                if ($existingIntent !== null) {
                    $existingIntent->confirmation_token_hash = hash('sha256', $token);
                    $existingIntent->token_expires_at = now()->addMinutes($this->tokenLifetime());
                    $existingIntent->last_confirmation_sent_at = now();
                    $existingIntent->save();

                    $this->auditRecorder->record(
                        'registration.confirmation_resent',
                        'registration_intent',
                        $existingIntent->public_id,
                        $data->correlationId,
                        ['locale' => $existingIntent->locale],
                    );

                    return $existingIntent;
                }

                $intent = new RegistrationIntent;
                $intent->status = RegistrationStatus::EmailPending;
                $intent->source = RegistrationSource::SelfService;
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

                $this->recordConsents($intent, $data);

                $this->auditRecorder->record(
                    'registration.requested',
                    'registration_intent',
                    $intent->public_id,
                    $data->correlationId,
                    [
                        'country_code' => $intent->country_code,
                        'locale' => $intent->locale,
                        'tenant_type' => $intent->tenant_type->value,
                    ],
                );

                return $intent;
            });
        } catch (QueryException $exception) {
            if ($this->isExpectedDuplicateKey($exception)
                && (User::query()->where('normalized_email', $normalizedEmail)->exists()
                || RegistrationIntent::query()->where('active_email_hash', $emailHash)->exists())) {
                return;
            }

            throw $exception;
        }

        Notification::route('mail', $intent->email)
            ->notify((new PartnerRegistrationConfirmation(
                $intent->public_id,
                $token,
                $this->tokenLifetime(),
            ))->locale($intent->locale)->afterCommit());
    }

    /**
     * Speichert Vertragsbestätigung und Datenschutzkenntnisnahme als getrennte Nachweise.
     */
    private function recordConsents(RegistrationIntent $intent, StartPartnerRegistrationData $data): void
    {
        $documents = [
            'terms' => [
                'purpose' => 'contract',
                'acceptance_type' => 'acceptance',
                'version' => $data->termsVersion,
                'digest' => $data->termsDigest,
            ],
            'privacy' => [
                'purpose' => 'privacy_information',
                'acceptance_type' => 'acknowledgement',
                'version' => $data->privacyVersion,
                'digest' => $data->privacyDigest,
            ],
        ];

        foreach ($documents as $key => $classification) {
            $record = new ConsentRecord;
            $record->template_key = $key;
            $record->template_version = $classification['version'];
            $record->document_digest = $classification['digest'];
            $record->purpose = $classification['purpose'];
            $record->acceptance_type = $classification['acceptance_type'];
            // Die Evidenz nennt die Sprache des tatsächlich angezeigten Dokuments,
            // nicht die unabhängig davon gewählte künftige Oberflächensprache.
            $record->locale = $this->legalDocuments->get($key)->locale;
            $record->accepted_at = now();
            $intent->consentRecords()->save($record);
        }
    }

    /**
     * Bindet jede behauptete Nutzerhandlung an Version und Digest der wirklich ausgelieferten Datei.
     */
    private function validateConsentEvidence(StartPartnerRegistrationData $data): void
    {
        $terms = $this->legalDocuments->get('terms');
        $privacy = $this->legalDocuments->get('privacy');

        Validator::make([
            'terms_accepted' => $data->termsAccepted,
            'terms_version' => $data->termsVersion,
            'terms_digest' => $data->termsDigest,
            'privacy_acknowledged' => $data->privacyAcknowledged,
            'privacy_version' => $data->privacyVersion,
            'privacy_digest' => $data->privacyDigest,
        ], [
            'terms_accepted' => ['accepted'],
            'terms_version' => ['required', Rule::in([$terms->version])],
            'terms_digest' => ['required', Rule::in([$terms->digest])],
            'privacy_acknowledged' => ['accepted'],
            'privacy_version' => ['required', Rule::in([$privacy->version])],
            'privacy_digest' => ['required', Rule::in([$privacy->digest])],
        ])->validate();
    }

    /**
     * Erzeugt 256 Bit kryptografischen Zufall in URL-sicherer Schreibweise.
     */
    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Liefert die freigegebene kurze Gültigkeitsdauer des Bestätigungslinks.
     */
    private function tokenLifetime(): int
    {
        return (int) config('merlin.registration.token_lifetime_minutes', 60);
    }

    /**
     * Unterscheidet erwartete Race-Condition-Dubletten von echten Datenbankfehlern.
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
