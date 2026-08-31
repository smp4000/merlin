<?php

namespace App\Filament\Pages;

use App\Enums\TenantMembershipStatus;
use App\Foundation\Tenancy\TenantContextResolver;
use App\Models\LegalEntity;
use App\Models\Station;
use App\Models\TenantMembership;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stellt die eigenständige Merlin-Startseite des Backoffice bereit.
 *
 * Die Seite zeigt bewusst nur den aktuellen Aufbaufortschritt. Operative Kennzahlen werden
 * erst ergänzt, wenn Tenant- und StationContext sicher gebunden und autorisiert sind.
 */
final class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.pages.dashboard';

    /**
     * Liefert die übersetzte Bezeichnung in der Hauptnavigation.
     */
    public static function getNavigationLabel(): string
    {
        return __('merlin.dashboard.navigation_label');
    }

    /**
     * Liefert den übersetzten Seitentitel.
     */
    public function getTitle(): string|Htmlable
    {
        return __('merlin.dashboard.title');
    }

    /**
     * Ermittelt den echten Einrichtungsfortschritt ausschließlich aus einer wirksamen
     * Membership der angemeldeten Identität.
     *
     * Die Abfragen beginnen bewusst beim Benutzer und verwenden anschließend nur die so
     * bestätigte `tenant_id`. Plattformadministratoren erhalten keine operativen Zählwerte
     * fremder Mandanten. Noch nicht implementierte Schritte bleiben ausdrücklich offen.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        if ($user === null || $user->isPlatformSuperAdmin()) {
            return ['tenantProgress' => null];
        }

        $now = now();
        $memberships = TenantMembership::query()
            ->with('tenant')
            ->where('user_id', $user->getKey())
            ->where('status', TenantMembershipStatus::Active)
            ->where('valid_from', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>', $now);
            })
            ->get();

        $tenantPublicId = (string) request()->session()->get('active_tenant_public_id');

        // Bei genau einer wirksamen Zugehörigkeit ist die automatische Auswahl eindeutig.
        // Mehrere Zugehörigkeiten benötigen später die geplante bewusste Betriebsauswahl;
        // bis dahin zeigt Merlin keine zufällig ausgewählten Mandantendaten an.
        if ($tenantPublicId === '' && $memberships->count() === 1) {
            $tenantPublicId = (string) $memberships->first()->tenant->public_id;
            request()->session()->put('active_tenant_public_id', $tenantPublicId);
        }

        if ($tenantPublicId === '') {
            return ['tenantProgress' => null];
        }

        try {
            $context = app(TenantContextResolver::class)->resolve($user, $tenantPublicId);
        } catch (ModelNotFoundException) {
            return ['tenantProgress' => null];
        }

        $tenantId = $context->id();
        $completedSteps = [
            LegalEntity::query()->where('tenant_id', $tenantId)->exists(),
            Station::query()->where('tenant_id', $tenantId)->exists(),
            false,
            false,
        ];
        $completedCount = 0;

        foreach ($completedSteps as $isComplete) {
            if (! $isComplete) {
                break;
            }

            $completedCount++;
        }

        return ['tenantProgress' => [
            'tenant_name' => $context->tenant->display_name,
            'completed_steps' => $completedSteps,
            'completed_count' => $completedCount,
            'current_step' => min($completedCount + 1, count($completedSteps)),
            'total_steps' => count($completedSteps),
        ]];
    }
}
