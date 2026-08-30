<?php

namespace Tests\Feature\Platform;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Enums\TenantType;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Foundation\Legal\LegalDocumentRepository;
use App\Models\AuditEvent;
use App\Models\RegistrationIntent;
use App\Models\User;
use App\Modules\Registration\Application\Data\InvitePartnerData;
use App\Modules\Registration\Application\InvitePartner;
use App\Notifications\PartnerRegistrationConfirmation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prüft die manuelle Partneranlage und ihre reservierte Plattformberechtigung.
 */
final class PartnerAdministrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nur ein Super-Admin darf die globale Partnerverwaltung öffnen.
     */
    public function test_partner_resource_is_restricted_to_platform_super_admins(): void
    {
        $regularUser = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($regularUser)->get('/admin/partners')->assertForbidden();

        $superAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
        ]);

        $this->actingAs($superAdmin)->get('/admin/partners')->assertOk();
    }

    /**
     * Das Tab-Modal erzeugt ausschließlich eine Einladung und noch keinen Mandanten.
     */
    public function test_super_admin_can_invite_a_partner_from_filament(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
        ]);
        $this->actingAs($superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListPartners::class)
            ->callAction('invitePartner', data: [
                'partner_display_name' => 'Tankstellen Welle',
                'tenant_type' => TenantType::SingleOperator->value,
                'country_code' => 'DE',
                'locale' => 'de',
                'first_name' => 'Christian',
                'last_name' => 'Welle',
                'owner_email' => 'owner@example.test',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $intent = RegistrationIntent::query()->sole();
        $this->assertSame(RegistrationSource::PlatformInvitation, $intent->source);
        $this->assertSame(RegistrationStatus::EmailPending, $intent->status);
        $this->assertSame($superAdmin->getKey(), $intent->invited_by_user_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('trials', 0);
        $this->assertDatabaseCount('consent_records', 0);

        $audit = AuditEvent::query()->where('event_type', 'platform.partner_invited')->sole();
        $this->assertSame($superAdmin->getKey(), $audit->actor_user_id);

        Notification::assertSentOnDemand(
            PartnerRegistrationConfirmation::class,
            fn (PartnerRegistrationConfirmation $notification, array $channels, AnonymousNotifiable $recipient): bool => $channels === ['mail'] && $recipient->routes['mail'] === 'owner@example.test',
        );
    }

    /**
     * Direkte Action-Aufrufe ohne Plattformrolle werden serverseitig abgewiesen.
     */
    public function test_regular_user_cannot_invoke_partner_invitation_service(): void
    {
        $regularUser = User::factory()->create(['email_verified_at' => now()]);

        $this->expectException(ValidationException::class);

        app(InvitePartner::class)->handle($regularUser, $this->invitationData());
    }

    /**
     * Erst die bewusste Owner-Bestätigung erzeugt User, Tenant, Trial und Nachweise.
     */
    public function test_invited_owner_confirmation_provisions_the_partner_atomically(): void
    {
        Notification::fake();
        $token = '';
        $superAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_super_admin' => true,
        ]);

        $intent = app(InvitePartner::class)->handle($superAdmin, $this->invitationData());

        Notification::assertSentOnDemand(
            PartnerRegistrationConfirmation::class,
            function (PartnerRegistrationConfirmation $notification) use (&$token): bool {
                $token = $notification->confirmationToken;

                return true;
            },
        );

        $terms = app(LegalDocumentRepository::class)->get('terms');
        $privacy = app(LegalDocumentRepository::class)->get('privacy');

        $this->post(route('registration.confirm.store', [$intent->public_id]), [
            'confirmation_token' => $token,
            'password' => 'Eine lange Passphrase 2026',
            'password_confirmation' => 'Eine lange Passphrase 2026',
            'terms_accepted' => '1',
            'terms_version' => $terms->version,
            'terms_digest' => $terms->digest,
            'privacy_acknowledged' => '1',
            'privacy_version' => $privacy->version,
            'privacy_digest' => $privacy->digest,
        ])->assertRedirect(route('onboarding.show'));

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseCount('trials', 1);
        $this->assertDatabaseCount('consent_records', 2);
        $this->assertSame(RegistrationStatus::Confirmed, $intent->fresh()->status);
    }

    /**
     * Liefert konsistente, gültige Partnerdaten für den Plattformdienst.
     */
    private function invitationData(): InvitePartnerData
    {
        return new InvitePartnerData(
            'Christian',
            'Welle',
            'owner@example.test',
            'Tankstellen Welle',
            TenantType::SingleOperator,
            'DE',
            'de',
            (string) Str::uuid(),
        );
    }
}
