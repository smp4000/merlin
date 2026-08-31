<?php

namespace App\Providers\Filament;

use App\Enums\ThemePalette;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Foundation\Tenancy\AccessibleTenantMemberships;
use App\Foundation\Tenancy\TenantContext;
use App\Http\Middleware\EnsureActiveTenantContext;
use App\Models\User;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Konfiguriert das ausschließlich mandantenbezogene Merlin-Partner-Panel.
 *
 * Der bestehende Pfad `/admin` bleibt für Partner stabil. Globale Plattformressourcen
 * werden hier weder entdeckt noch registriert und können deshalb nicht versehentlich in
 * Navigation, Suche oder Livewire-Aktionen des Partnerkontexts gelangen.
 */
final class PartnerPanelProvider extends PanelProvider
{
    /**
     * Bindet Authentifizierung, TenantContext und die eigenständige Merlin-Oberfläche.
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
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                function (): View {
                    $context = app(TenantContext::class);
                    /** @var User $user */
                    $user = auth()->user();
                    $membershipCount = app(AccessibleTenantMemberships::class)
                        ->queryFor($user)
                        ->limit(2)
                        ->get()
                        ->count();

                    return view('filament.components.tenant-switcher', compact('context', 'membershipCount'));
                },
            )
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
                EnsureActiveTenantContext::class,
            ], isPersistent: true);
    }
}
