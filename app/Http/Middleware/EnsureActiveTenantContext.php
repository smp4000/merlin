<?php

namespace App\Http\Middleware;

use App\Foundation\Tenancy\AccessibleTenantMemberships;
use App\Foundation\Tenancy\TenantContext;
use App\Foundation\Tenancy\TenantContextSession;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bindet für jede geschützte Partneranfrage genau einen autorisierten TenantContext.
 *
 * Hatte die Sitzung bereits eine Auswahl, die inzwischen abgelaufen oder widerrufen ist,
 * wird niemals still ein anderer Mandant gewählt. Der Benutzer muss dann erneut bewusst
 * auswählen; so kann ein altes Formular nicht unter einem anderen Tenant gespeichert werden.
 */
final class EnsureActiveTenantContext
{
    /**
     * Verwendet dieselbe Membership- und Sitzungslogik für alle Partner-Endpunkte.
     */
    public function __construct(
        private readonly AccessibleTenantMemberships $memberships,
        private readonly TenantContextSession $tenantSession,
    ) {}

    /**
     * Auto-selektiert nur bei einer erstmaligen, eindeutig einzigen Membership.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User && $user->email_verified_at !== null, 403);

        $hadSelection = $this->tenantSession->selectedPublicId($request) !== null;
        $context = $this->tenantSession->current($request, $user);

        if ($context === null && ! $hadSelection) {
            $availableMemberships = $this->memberships->queryFor($user)->limit(2)->get();

            if ($availableMemberships->count() === 1) {
                $context = $this->tenantSession->select(
                    $request,
                    $user,
                    (string) $availableMemberships->first()->tenant->public_id,
                );
            }
        }

        if ($context === null) {
            if ($request->expectsJson()) {
                abort(409, __('merlin.tenant_selection.context_required'));
            }

            return redirect()->route('tenant-selection.show');
        }

        // Der unveränderliche Context wird nur für die Dauer dieses Requests gebunden.
        // Controller, Filament-Seiten und Policies erhalten dadurch dieselbe geprüfte Instanz.
        $request->attributes->set(TenantContext::class, $context);
        app()->instance(TenantContext::class, $context);

        return $next($request);
    }
}
