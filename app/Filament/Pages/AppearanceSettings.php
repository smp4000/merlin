<?php

namespace App\Filament\Pages;

use App\Enums\TenantMembershipRole;
use App\Enums\ThemePalette;
use App\Foundation\Tenancy\Exceptions\TenantReadOnlyException;
use App\Foundation\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\Settings\Application\SaveTenantAppearance;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;

/**
 * Ermöglicht Partneradministratoren die Auswahl eines geprüften Mandanten-Farbschemas.
 *
 * Die Seite bietet nur feste ThemePalette-Schlüssel an. Freies CSS oder freie HEX-Werte
 * gelangen weder in Livewire noch in die Datenbank oder die gerenderte Oberfläche.
 */
final class AppearanceSettings extends Page
{
    protected static ?string $slug = 'einstellungen/erscheinungsbild';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected string $view = 'filament.pages.appearance-settings';

    public string $selectedTheme = ThemePalette::MerlinPetrol->value;

    public static function getNavigationLabel(): string
    {
        return __('appearance.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('appearance.navigation.group');
    }

    /** Zeigt die Einstellung nur im wirksamen Administrator-Kontext des Mandanten. */
    public static function canAccess(): bool
    {
        return app()->bound(TenantContext::class)
            && app(TenantContext::class)->membership->role === TenantMembershipRole::Administrator;
    }

    /** Lädt die gespeicherte Palette des aktiven Mandanten oder den Merlin-Standard. */
    public function mount(): void
    {
        $setting = app(TenantContext::class)->tenant->appearanceSetting()->first();
        $this->selectedTheme = ($setting?->theme_key ?? ThemePalette::default())->value;
    }

    public function getTitle(): string|Htmlable
    {
        return __('appearance.title');
    }

    /** Speichert die geprüfte Auswahl über die tenantgebundene Anwendungsgrenze. */
    public function save(SaveTenantAppearance $service): mixed
    {
        $validated = $this->validate([
            'selectedTheme' => ['required', Rule::enum(ThemePalette::class)],
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $service->handle(
                app(TenantContext::class),
                $actor,
                ThemePalette::from($validated['selectedTheme']),
            );
        } catch (TenantReadOnlyException) {
            $this->addError('selectedTheme', __('appearance.read_only'));

            return null;
        }

        Notification::make()->title(__('appearance.saved'))->success()->send();

        return $this->redirect(self::getUrl(), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'palettes' => collect(ThemePalette::cases())->mapWithKeys(
                fn (ThemePalette $palette): array => [$palette->value => [
                    'label' => __($palette->labelKey()),
                    'variables' => $palette->cssVariables(),
                ]],
            ),
        ];
    }
}
