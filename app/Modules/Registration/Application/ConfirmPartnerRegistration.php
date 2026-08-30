<?php

namespace App\Modules\Registration\Application;

use App\Enums\RegistrationStatus;
use App\Foundation\Audit\AuditRecorder;
use App\Foundation\Legal\LegalDocumentRepository;
use App\Models\ConsentRecord;
use App\Models\RegistrationIntent;
use App\Models\User;
use App\Modules\Registration\Application\Data\ConfirmedPartnerRegistration;
use App\Modules\Registration\Application\Data\ConfirmPartnerRegistrationData;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Bestätigt einen Registrierungsvorgang und provisioniert den Partner atomar.
 *
 * Der Vorgang wird innerhalb der Transaktion gesperrt. Doppelklicks und parallele
 * Bestätigungen erhalten deshalb dasselbe Ergebnis, ohne einen zweiten Mandanten zu
 * erzeugen. Bereits bekannte Konten werden nicht still zusammengeführt oder verändert.
 */
final class ConfirmPartnerRegistration
{
    public function __construct(
        private readonly CreateTenant $createTenant,
        private readonly AuditRecorder $auditRecorder,
        private readonly LegalDocumentRepository $legalDocuments,
    ) {}

    /**
     * Erstellt Benutzer, Owner-Bezug, Administrator-Membership, Trial und Audit in einem Commit.
     *
     * @throws ModelNotFoundException Bei einem nicht mehr verwendbaren Vorgang.
     */
    public function handle(ConfirmPartnerRegistrationData $data): ConfirmedPartnerRegistration
    {
        Validator::make(['password' => $data->password], [
            'password' => ['required', 'string', 'max:128', Password::min(12)->letters()->numbers()],
        ])->validate();

        return DB::transaction(function () use ($data): ConfirmedPartnerRegistration {
            $intent = RegistrationIntent::query()
                ->where('public_id', $data->publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($intent->status === RegistrationStatus::Confirmed) {
                throw (new ModelNotFoundException)->setModel(RegistrationIntent::class);
            }

            if ($intent->status !== RegistrationStatus::EmailPending
                || ! $intent->token_expires_at->isAfter(now())) {
                throw (new ModelNotFoundException)->setModel(RegistrationIntent::class);
            }

            $this->ensureTokenMatches($intent, $data->token);
            $this->recordOrValidateConsentEvidence($intent, $data);

            if (User::query()->where('normalized_email', $intent->normalized_email)->exists()) {
                throw (new ModelNotFoundException)->setModel(RegistrationIntent::class);
            }

            $user = new User;
            $user->name = trim($intent->first_name.' '.$intent->last_name);
            $user->email = $intent->normalized_email;
            $user->normalized_email = $intent->normalized_email;
            $user->email_verified_at = now();
            $user->password = $data->password;
            $user->save();

            $tenant = $this->createTenant->handle(
                $user,
                new CreateTenantData(
                    $intent->partner_display_name,
                    $intent->tenant_type,
                    $intent->country_code,
                    $intent->locale,
                    config("merlin.registration.country_timezones.{$intent->country_code}", 'Europe/Berlin'),
                ),
            );

            $intent->confirmed_user_id = $user->getKey();
            $intent->tenant_id = $tenant->getKey();
            $intent->status = RegistrationStatus::Confirmed;
            $intent->confirmed_at = now();
            $intent->active_email_hash = null;
            $intent->confirmation_token_hash = null;
            $intent->email = null;
            $intent->normalized_email = null;
            $intent->first_name = null;
            $intent->last_name = null;
            $intent->partner_display_name = null;
            $intent->save();

            $consents = $intent->consentRecords()->get()->keyBy('template_key');
            $this->auditRecorder->record(
                'registration.confirmed',
                'registration_intent',
                $intent->public_id,
                $data->correlationId,
                [
                    'country_code' => $intent->country_code,
                    'locale' => $intent->locale,
                    'terms_version' => $consents->get('terms')->template_version,
                    'privacy_version' => $consents->get('privacy')->template_version,
                ],
                $tenant,
                $user,
            );

            return new ConfirmedPartnerRegistration($user, $tenant);
        });
    }

    /**
     * Vergleicht ausschließlich Hashwerte und verwendet einen konstantzeitlichen Vergleich.
     */
    private function ensureTokenMatches(RegistrationIntent $intent, string $token): void
    {
        $storedHash = (string) $intent->confirmation_token_hash;
        $providedHash = hash('sha256', $token);

        if (strlen($token) !== 43
            || preg_match('/^[A-Za-z0-9_-]{43}$/', $token) !== 1
            || $storedHash === ''
            || ! hash_equals($storedHash, $providedHash)) {
            throw (new ModelNotFoundException)->setModel(RegistrationIntent::class);
        }
    }

    /**
     * Verhindert Provisionierung bei fehlenden oder technisch beschädigten Nachweisen.
     */
    private function recordOrValidateConsentEvidence(
        RegistrationIntent $intent,
        ConfirmPartnerRegistrationData $data,
    ): void {
        $documents = [
            'terms' => $this->legalDocuments->get('terms'),
            'privacy' => $this->legalDocuments->get('privacy'),
        ];

        Validator::make([
            'terms_accepted' => $data->termsAccepted,
            'terms_version' => $data->termsVersion,
            'terms_digest' => $data->termsDigest,
            'privacy_acknowledged' => $data->privacyAcknowledged,
            'privacy_version' => $data->privacyVersion,
            'privacy_digest' => $data->privacyDigest,
        ], [
            'terms_accepted' => ['accepted'],
            'terms_version' => ['required', Rule::in([$documents['terms']->version])],
            'terms_digest' => ['required', Rule::in([$documents['terms']->digest])],
            'privacy_acknowledged' => ['accepted'],
            'privacy_version' => ['required', Rule::in([$documents['privacy']->version])],
            'privacy_digest' => ['required', Rule::in([$documents['privacy']->digest])],
        ])->validate();

        $records = $intent->consentRecords()->get()->keyBy('template_key');

        foreach ($documents as $key => $document) {
            $record = $records->get($key);

            if ($record === null) {
                $record = new ConsentRecord;
                $record->template_key = $key;
                $record->template_version = $document->version;
                $record->document_digest = $document->digest;
                $record->purpose = $key === 'terms' ? 'contract' : 'privacy_information';
                $record->acceptance_type = $key === 'terms' ? 'acceptance' : 'acknowledgement';
                $record->locale = $document->locale;
                $record->accepted_at = now();
                $intent->consentRecords()->save($record);

                continue;
            }

            if (! hash_equals($document->digest, $record->document_digest)
                || $record->template_version !== $document->version
                || $record->locale !== $document->locale) {
                throw (new ModelNotFoundException)->setModel(RegistrationIntent::class);
            }
        }
    }
}
