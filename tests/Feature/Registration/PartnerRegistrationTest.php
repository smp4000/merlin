<?php

namespace Tests\Feature\Registration;

use App\Enums\RegistrationStatus;
use App\Enums\TenantType;
use App\Foundation\Legal\LegalDocumentRepository;
use App\Models\AuditEvent;
use App\Models\ConsentRecord;
use App\Models\RegistrationIntent;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Registration\Application\Data\StartPartnerRegistrationData;
use App\Modules\Registration\Application\StartPartnerRegistration;
use App\Notifications\PartnerRegistrationConfirmation;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Prüft den vollständigen öffentlichen Registrierungspfad samt Missbrauchsgrenzen.
 */
final class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Das öffentliche Formular zeigt den freigegebenen Drei-Schritt-Ablauf.
     */
    public function test_registration_form_uses_the_merlin_step_design(): void
    {
        $this->get('/registrieren')
            ->assertOk()
            ->assertSee('Partnerkonto vorbereiten')
            ->assertSee('Partnerdaten')
            ->assertSee('E-Mail')
            ->assertSee('Zugang')
            ->assertSee('Einzelunternehmen');
    }

    /**
     * Das Absenden erzeugt nur Intent und Nachweise, aber noch keinen Benutzer oder Tenant.
     */
    public function test_starting_registration_is_data_minimal_and_sends_confirmation(): void
    {
        Notification::fake();

        $response = $this->post('/registrieren', $this->validFormData());

        $response->assertRedirect(route('registration.pending'));
        $this->assertDatabaseCount('registration_intents', 1);
        $this->assertDatabaseCount('consent_records', 2);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);

        $intent = RegistrationIntent::query()->sole();
        $this->assertSame(RegistrationStatus::EmailPending, $intent->status);
        $this->assertSame('owner@example.test', $intent->normalized_email);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $intent->confirmation_token_hash);

        Notification::assertSentOnDemand(
            PartnerRegistrationConfirmation::class,
            function (PartnerRegistrationConfirmation $notification, array $channels, AnonymousNotifiable $recipient) use ($intent): bool {
                $actionUrl = $notification->toMail($recipient)->actionUrl;

                $this->assertStringContainsString('#token=', $actionUrl);
                $this->assertStringNotContainsString($notification->confirmationToken, Str::before($actionUrl, '#'));

                return $channels === ['mail']
                    && $recipient->routes['mail'] === 'owner@example.test'
                    && $notification->confirmationToken !== $intent->confirmation_token_hash;
            },
        );
    }

    /**
     * Queue und Failed-Job-Payload dürfen weder Bearer-Token noch Empfänger offenlegen.
     */
    public function test_queued_confirmation_payload_is_encrypted(): void
    {
        config(['queue.default' => 'database']);
        $token = str_repeat('A', 43);
        $notification = new PartnerRegistrationConfirmation(
            (string) Str::ulid(),
            $token,
            60,
        );

        $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);

        Notification::route('mail', 'secret-recipient@example.test')
            ->notify($notification);

        $payload = (string) DB::table('jobs')->value('payload');
        $this->assertNotSame('', $payload);
        $this->assertStringNotContainsString($token, $payload);
        $this->assertStringNotContainsString('secret-recipient@example.test', $payload);
    }

    /**
     * HTML- und Textfassung verwenden das eigene Merlin-Design ohne externe Tracker.
     */
    public function test_confirmation_email_uses_the_branded_merlin_templates(): void
    {
        $notification = new PartnerRegistrationConfirmation(
            (string) Str::ulid(),
            str_repeat('A', 43),
            60,
        );
        $message = $notification->toMail(new AnonymousNotifiable);
        $this->assertIsArray($message->view);

        $html = view($message->view['html'], $message->viewData)->render();
        $text = view($message->view['text'], $message->viewData)->render();

        $this->assertStringContainsString('Willkommen bei Merlin.', $html);
        $this->assertStringContainsString('Ihr Merlin-Team', $html);
        $this->assertStringContainsString('background:#122326', $html);
        $this->assertStringContainsString('#token=', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('Regards,', $html);
        $this->assertStringNotContainsString('<script', $html);

        $this->assertStringContainsString('Registrierung bestätigen', $text);
        $this->assertStringContainsString('#token=', $text);

        app()->setLocale('en');
        $englishMessage = $notification->toMail(new AnonymousNotifiable);
        $englishHtml = view($englishMessage->view['html'], $englishMessage->viewData)->render();
        $this->assertStringContainsString('Welcome to Merlin.', $englishHtml);
        $this->assertStringContainsString('Your Merlin team', $englishHtml);
    }

    /**
     * Ohne beide getrennten Bestätigungen wird kein Registrierungsvorgang gespeichert.
     */
    public function test_terms_and_privacy_evidence_are_both_required(): void
    {
        $payload = $this->validFormData();
        unset($payload['terms_accepted'], $payload['privacy_acknowledged']);

        $this->post('/registrieren', $payload)
            ->assertSessionHasErrors(['terms_accepted', 'privacy_acknowledged']);

        $this->assertDatabaseCount('registration_intents', 0);
        $this->assertDatabaseCount('consent_records', 0);
    }

    /**
     * Manipulierte Dokumentversionen oder Digests dürfen keinen Nachweis erzeugen.
     */
    public function test_consent_evidence_must_match_the_displayed_canonical_documents(): void
    {
        $payload = $this->validFormData();
        $payload['terms_digest'] = str_repeat('0', 64);

        $this->post('/registrieren', $payload)->assertSessionHasErrors('terms_digest');

        $this->assertDatabaseCount('registration_intents', 0);
        $this->assertDatabaseCount('consent_records', 0);
    }

    /**
     * Ein GET durch Browser oder Mail-Scanner darf den Tenant niemals provisionieren.
     */
    public function test_confirmation_get_is_read_only(): void
    {
        [$intent, $token] = $this->startRegistration();

        $this->get(route('registration.confirm.show', [$intent->public_id]).'#token='.$token)
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('Jetzt Ihren Zugang schützen')
            ->assertSee('data-confirmation-form', false);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertSame(RegistrationStatus::EmailPending, $intent->fresh()->status);
    }

    /**
     * Der bewusste POST erzeugt alle Kernobjekte atomar und meldet den Owner an.
     */
    public function test_confirmation_post_creates_user_tenant_membership_trial_and_audit(): void
    {
        [$intent, $token] = $this->startRegistration();

        $response = $this->post(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData($token),
        );

        $response->assertRedirect(route('onboarding.show'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseCount('trials', 1);

        $confirmed = $intent->fresh();
        $tenant = Tenant::query()->sole();
        $this->assertSame(RegistrationStatus::Confirmed, $confirmed->status);
        $this->assertSame($tenant->getKey(), $confirmed->tenant_id);
        $this->assertSame($tenant->owner_user_id, $confirmed->confirmed_user_id);
        $this->assertNull($confirmed->confirmation_token_hash);
        $this->assertNull($confirmed->active_email_hash);
        $this->assertNull($confirmed->email);
        $this->assertNull($confirmed->first_name);
        $this->assertNull($confirmed->last_name);
        $this->assertSame($tenant->public_id, session('active_tenant_public_id'));

        $audit = AuditEvent::query()->where('event_type', 'registration.confirmed')->sole();
        $serializedAudit = json_encode($audit->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($token, $serializedAudit);
        $this->assertStringNotContainsString('owner@example.test', $serializedAudit);
    }

    /**
     * Ein zu kurzes Passwort darf weder Identität noch Mandant teilweise anlegen.
     */
    public function test_weak_password_is_rejected_before_provisioning(): void
    {
        [$intent, $token] = $this->startRegistration();

        $this->postJson(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData($token, 'kurz'),
        )->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertFalse(session()->has('_old_input.confirmation_token'));

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertSame(RegistrationStatus::EmailPending, $intent->fresh()->status);
    }

    /**
     * Der Nachweis nennt die Sprache des Rechtstexts, nicht eine andere UI-Präferenz.
     */
    public function test_consent_records_store_the_actual_legal_document_locale(): void
    {
        Notification::fake();
        $payload = $this->validFormData();
        $payload['locale'] = 'en';

        $this->post('/registrieren', $payload)->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('consent_records', 2);
        $this->assertSame(
            ['de'],
            ConsentRecord::query()->distinct()->pluck('locale')->all(),
        );
    }

    /**
     * Erneutes Absenden ersetzt den alten Token, ohne Registrierungsdaten zu duplizieren.
     */
    public function test_resubmission_rotates_token_and_invalidates_previous_link(): void
    {
        [$intent, $firstToken] = $this->startRegistration();
        Notification::fake();
        $secondToken = '';
        $terms = app(LegalDocumentRepository::class)->get('terms');
        $privacy = app(LegalDocumentRepository::class)->get('privacy');

        app(StartPartnerRegistration::class)->handle(new StartPartnerRegistrationData(
            'Manipulierter',
            'Name',
            'owner@example.test',
            'Manipulierter Partner',
            TenantType::CompanyGroup,
            'AT',
            'en',
            (string) Str::uuid(),
            true,
            $terms->version,
            $terms->digest,
            true,
            $privacy->version,
            $privacy->digest,
        ));

        Notification::assertSentOnDemand(
            PartnerRegistrationConfirmation::class,
            function (PartnerRegistrationConfirmation $notification) use (&$secondToken): bool {
                $secondToken = $notification->confirmationToken;

                return true;
            },
        );

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertDatabaseCount('registration_intents', 1);
        $this->assertSame('Pilot Partner', $intent->fresh()->partner_display_name);
        $this->post(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData($firstToken),
        )->assertStatus(410);
        $this->post(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData($secondToken),
        )->assertRedirect(route('onboarding.show'));
    }

    /**
     * Ein verbrauchter Link erzeugt auch nach Abmeldung keinen zweiten Mandanten.
     */
    public function test_replayed_confirmation_never_creates_a_duplicate_tenant(): void
    {
        [$intent, $token] = $this->startRegistration();
        $payload = $this->validConfirmationData($token);

        $this->post(route('registration.confirm.store', [$intent->public_id]), $payload)
            ->assertRedirect(route('onboarding.show'));
        auth()->logout();

        $this->post(route('registration.confirm.store', [$intent->public_id]), $payload)
            ->assertStatus(410);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('trials', 1);
    }

    /**
     * Falsche und abgelaufene Token erhalten dieselbe neutrale Fehlerseite ohne Seiteneffekt.
     */
    public function test_invalid_and_expired_tokens_are_rejected_without_side_effects(): void
    {
        [$intent, $token] = $this->startRegistration();

        $this->post(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData(str_repeat('x', 43)),
        )
            ->assertStatus(410)
            ->assertSee('Dieser Link kann nicht mehr verwendet werden');

        $intent->token_expires_at = now()->subSecond();
        $intent->save();

        $this->post(
            route('registration.confirm.store', [$intent->public_id]),
            $this->validConfirmationData($token),
        )
            ->assertStatus(410)
            ->assertSee('Dieser Link kann nicht mehr verwendet werden');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);
    }

    /**
     * Bestehende Identitäten erhalten dieselbe HTTP-Antwort und werden niemals überschrieben.
     */
    public function test_existing_email_gets_generic_response_without_account_changes(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'owner@example.test',
            'normalized_email' => 'owner@example.test',
        ]);
        $originalPassword = $user->password;

        $response = $this->post('/registrieren', $this->validFormData());

        $response->assertRedirect(route('registration.pending'));
        $this->assertDatabaseCount('registration_intents', 0);
        $this->assertSame($originalPassword, $user->fresh()->password);
        Notification::assertNothingSent();
    }

    /**
     * Nach sieben Tagen entfernt der geplante Lauf nur unbestätigte Vorgänge.
     */
    public function test_stale_unconfirmed_registration_is_purged_after_seven_days(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        [$intent] = $this->startRegistration();
        $intent->created_at = now()->subDays(8);
        $intent->last_confirmation_sent_at = now()->subDays(8);
        $intent->save();

        $this->artisan('merlin:registrations:purge')->assertSuccessful();

        $this->assertDatabaseMissing('registration_intents', ['id' => $intent->getKey()]);
        $this->assertDatabaseCount('consent_records', 0);
    }

    /**
     * Ein frisch rotierter Link bleibt unabhängig vom ursprünglichen Erstellungsdatum erhalten.
     */
    public function test_fresh_resend_of_old_intent_is_not_purged(): void
    {
        Carbon::setTestNow('2026-08-30 10:00:00 Europe/Berlin');
        [$intent] = $this->startRegistration();
        $intent->created_at = now()->subDays(8);
        $intent->last_confirmation_sent_at = now()->subDays(8);
        $intent->save();

        $documents = app(LegalDocumentRepository::class);
        app(StartPartnerRegistration::class)->handle(new StartPartnerRegistrationData(
            'Christian',
            'Welle',
            'owner@example.test',
            'Pilot Partner',
            TenantType::SingleOperator,
            'DE',
            'de',
            (string) Str::uuid(),
            true,
            $documents->get('terms')->version,
            $documents->get('terms')->digest,
            true,
            $documents->get('privacy')->version,
            $documents->get('privacy')->digest,
        ));

        $this->artisan('merlin:registrations:purge')->assertSuccessful();

        $this->assertDatabaseHas('registration_intents', ['id' => $intent->getKey()]);
        $this->assertDatabaseCount('consent_records', 2);
    }

    /**
     * Startet direkt über die Application Action und liefert den nur im Mailobjekt sichtbaren Token.
     *
     * @return array{RegistrationIntent, string}
     */
    private function startRegistration(): array
    {
        Notification::fake();
        $token = '';

        app(StartPartnerRegistration::class)->handle(new StartPartnerRegistrationData(
            'Christian',
            'Welle',
            'owner@example.test',
            'Pilot Partner',
            TenantType::SingleOperator,
            'DE',
            'de',
            (string) Str::uuid(),
            true,
            app(LegalDocumentRepository::class)->get('terms')->version,
            app(LegalDocumentRepository::class)->get('terms')->digest,
            true,
            app(LegalDocumentRepository::class)->get('privacy')->version,
            app(LegalDocumentRepository::class)->get('privacy')->digest,
        ));

        Notification::assertSentOnDemand(
            PartnerRegistrationConfirmation::class,
            function (PartnerRegistrationConfirmation $notification) use (&$token): bool {
                $token = $notification->confirmationToken;

                return true;
            },
        );

        return [RegistrationIntent::query()->sole(), $token];
    }

    /** @return array<string, string> */
    private function validFormData(): array
    {
        $terms = app(LegalDocumentRepository::class)->get('terms');
        $privacy = app(LegalDocumentRepository::class)->get('privacy');

        return [
            'first_name' => 'Christian',
            'last_name' => 'Welle',
            'email' => 'Owner@Example.Test',
            'partner_display_name' => 'Pilot Partner',
            'tenant_type' => 'single_operator',
            'country_code' => 'DE',
            'locale' => 'de',
            'terms_accepted' => '1',
            'terms_version' => $terms->version,
            'terms_digest' => $terms->digest,
            'privacy_acknowledged' => '1',
            'privacy_version' => $privacy->version,
            'privacy_digest' => $privacy->digest,
        ];
    }

    /** @return array<string, string> */
    private function validConfirmationData(
        string $token,
        string $password = 'Eine lange Passphrase 2026',
    ): array {
        $terms = app(LegalDocumentRepository::class)->get('terms');
        $privacy = app(LegalDocumentRepository::class)->get('privacy');

        return [
            'confirmation_token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
            'terms_accepted' => '1',
            'terms_version' => $terms->version,
            'terms_digest' => $terms->digest,
            'privacy_acknowledged' => '1',
            'privacy_version' => $privacy->version,
            'privacy_digest' => $privacy->digest,
        ];
    }
}
