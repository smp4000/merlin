<?php

namespace App\Http\Controllers;

use App\Foundation\Tenancy\AccessibleTenantMemberships;
use App\Foundation\Tenancy\TenantContextSession;
use App\Http\Requests\SelectTenantRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zeigt und verarbeitet die bewusste Betriebsauswahl einer globalen Identität.
 *
 * Die Seite listet ausschließlich bereits wirksame eigene Memberships. Auswahlfehler
 * bleiben absichtlich neutral und bestätigen nicht die Existenz eines fremden Mandanten.
 */
final class TenantSelectionController extends Controller
{
    /**
     * Zeigt alle aktuell erlaubten Betriebe, ohne Memberships anderer Identitäten offenzulegen.
     */
    public function show(
        Request $request,
        AccessibleTenantMemberships $memberships,
        TenantContextSession $tenantSession,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->email_verified_at !== null, 403);
        $availableMemberships = $memberships->getFor($user);
        abort_if($availableMemberships->isEmpty(), 403);

        return response()
            ->view('tenant-selection.show', [
                'memberships' => $availableMemberships,
                'selectedTenantPublicId' => $tenantSession->selectedPublicId($request),
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'same-origin');
    }

    /**
     * Bindet die ausdrücklich gewählte Membership und beginnt danach im Partner-Dashboard.
     */
    public function store(
        SelectTenantRequest $request,
        TenantContextSession $tenantSession,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->email_verified_at !== null, 403);

        try {
            $tenantSession->select($request, $user, $request->validated('tenant_public_id'));
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'tenant_public_id' => __('merlin.tenant_selection.invalid'),
            ]);
        }

        return redirect('/admin/dashboard');
    }
}
