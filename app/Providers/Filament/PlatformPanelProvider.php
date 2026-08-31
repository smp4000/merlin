<?php

namespace App\Providers\Filament;

use App\Enums\ThemePalette;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Platform\Pages\Dashboard;
use App\Filament\Resources\BankDirectorySources\BankDirectorySourceResource;
use App\Filament\Resources\Partners\PartnerResource;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Konfiguriert die von operativen Mandantendaten getrennte Plattformverwaltung.
 *
 * Das Panel enthält nur ausdrücklich registrierte globale Ressourcen. Eine spätere
 * Mandanteneinsicht darf hier ausschließlich über einen gesonderten, zeitlich begrenzten
 * Supportgrant ergänzt werden und entsteht niemals aus der Super-Admin-Rolle allein.
 */
final class PlatformPanelProvider extends PanelProvider
{
    /**
     * Registriert Plattform-Metadaten und globale Kataloge unter dem eigenen Pfad.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('platform')
            ->path('platform')
            ->login(Login::class)
            ->brandName(__('merlin.brand.name'))
            ->brandLogo(fn () => view('filament.components.brand'))
            ->darkModeBrandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('2.75rem')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->font('Segoe UI Variable', provider: LocalFontProvider::class)
            ->darkMode(false)
            ->sidebarWidth('18rem')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => ThemePalette::default()->colors(),
            ])
            ->resources([
                PartnerResource::class,
                BankDirectorySourceResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ], isPersistent: true);
    }
}
