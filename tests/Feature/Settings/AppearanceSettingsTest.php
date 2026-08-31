<?php

namespace Tests\Feature\Settings;

use App\Enums\TenantType;
use App\Enums\ThemePalette;
use App\Filament\Pages\AppearanceSettings;
use App\Foundation\Settings\TenantTheme;
use App\Foundation\Tenancy\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Tenants\Application\CreateTenant;
use App\Modules\Tenants\Application\Data\CreateTenantData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Prüft Speicherung, Mandantentrennung, Audit und Nur-Lesen-Schutz des Farbschemas. */
final class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_preview_and_save_palette_only_for_active_tenant(): void
    {
        [$user, $tenant] = $this->partner('Blauer Betrieb');
        [, $foreignTenant] = $this->partner('Fremder Betrieb');
        $this->bindContext($user, $tenant);

        Livewire::test(AppearanceSettings::class)
            ->assertSee('Ozeanblau')
            ->assertSee('Live-Vorschau')
            ->set('selectedTheme', ThemePalette::OceanBlue->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(AppearanceSettings::getUrl());

        $this->assertDatabaseHas('tenant_appearance_settings', [
            'tenant_id' => $tenant->getKey(),
            'theme_key' => ThemePalette::OceanBlue->value,
            'updated_by_user_id' => $user->getKey(),
        ]);
        $this->assertDatabaseMissing('tenant_appearance_settings', ['tenant_id' => $foreignTenant->getKey()]);
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->getKey(),
            'event_type' => 'tenant.appearance_changed',
        ]);
        $this->assertSame(ThemePalette::OceanBlue, app(TenantTheme::class)->current());
    }

    public function test_expired_tenant_cannot_change_palette(): void
    {
        [$user, $tenant] = $this->partner('Abgelaufener Betrieb');
        $tenant->trial()->update(['ends_at' => now()->subMinute()]);
        $this->bindContext($user, $tenant->fresh());

        Livewire::test(AppearanceSettings::class)
            ->set('selectedTheme', ThemePalette::Coral->value)
            ->call('save')
            ->assertHasErrors(['selectedTheme']);

        $this->assertDatabaseMissing('tenant_appearance_settings', ['tenant_id' => $tenant->getKey()]);
    }

    /** @return array{User, Tenant} */
    private function partner(string $name): array
    {
        $user = User::factory()->create();
        $tenant = app(CreateTenant::class)->handle($user, new CreateTenantData($name, TenantType::SingleOperator));

        return [$user, $tenant];
    }

    private function bindContext(User $user, Tenant $tenant): void
    {
        $tenant->load('trial', 'memberships');
        app()->instance(TenantContext::class, new TenantContext($tenant, $tenant->memberships->firstOrFail()));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }
}
