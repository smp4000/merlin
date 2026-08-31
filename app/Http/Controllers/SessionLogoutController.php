<?php

namespace App\Http\Controllers;

use App\Foundation\Tenancy\TenantContextSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Beendet eine Merlin-Sitzung unabhängig vom zuletzt verwendeten Panelkontext.
 *
 * Die Abmeldung liegt bewusst außerhalb der Filament-Auth-Middlewareketten. Dadurch kann
 * ein Benutzer die Sitzung auch dann sicher beenden, wenn seine Tenant-Membership während
 * der Sitzung abläuft oder der gespeicherte Mandantenkontext inzwischen ungültig ist.
 */
final class SessionLogoutController extends Controller
{
    /**
     * Meldet einen Plattformbenutzer ab und führt zur getrennten Plattformanmeldung.
     */
    public function platform(Request $request, TenantContextSession $tenantSession): RedirectResponse
    {
        return $this->logout($request, $tenantSession, 'filament.platform.auth.login');
    }

    /**
     * Meldet einen Partnerbenutzer ab und führt zur Partneranmeldung.
     */
    public function partner(Request $request, TenantContextSession $tenantSession): RedirectResponse
    {
        return $this->logout($request, $tenantSession, 'filament.admin.auth.login');
    }

    /**
     * Löscht fachliche Kontexte vor der Authentifizierung und invalidiert anschließend die
     * gesamte Sitzung samt CSRF-Token gegen Wiederverwendung alter Browserzustände.
     */
    private function logout(
        Request $request,
        TenantContextSession $tenantSession,
        string $loginRoute,
    ): RedirectResponse {
        $tenantSession->clear($request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
