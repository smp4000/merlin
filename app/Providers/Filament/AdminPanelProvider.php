<?php

namespace App\Providers\Filament;

use App\Enums\ThemePalette;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
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
 * Konfiguriert das derzeitige Merlin-Backoffice und bindet das eigene Produktdesign ein.
 *
 * Das Panel bleibt bis zur Einführung der getrennten Plattform- und Partnerkontexte unter
 * der bestehenden Admin-Route erreichbar. Dadurch werden Authentifizierung und Pilotzugang
 * nicht vorzeitig mit einer noch nicht implementierten Mandantenauflösung vermischt.
 */
final class AdminPanelProvider extends PanelProvider
{
    /**
     * Stellt Navigation, Authentifizierung und die zentrale Merlin-Designgrundlage bereit.
     *
     * Die primäre Farbe stammt aus dem kontrollierten Palettenkatalog. Eine spätere
     * mandantenbezogene Auswahl darf ausschließlich nach einem geprüften TenantContext
     * erfolgen und ersetzt dann nur die freigegebenen Akzent-Tokens.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ]);
    }
}
