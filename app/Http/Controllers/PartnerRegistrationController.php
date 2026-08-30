<?php

namespace App\Http\Controllers;

use App\Enums\TenantType;
use App\Foundation\Legal\LegalDocumentRepository;
use App\Http\Requests\StorePartnerRegistrationRequest;
use App\Modules\Registration\Application\Data\StartPartnerRegistrationData;
use App\Modules\Registration\Application\StartPartnerRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Stellt die datenarme öffentliche Partnerregistrierung bereit.
 *
 * Die Antwort nach dem Absenden ist bewusst für neue, bestehende und bereits offene
 * E-Mail-Adressen identisch und verhindert dadurch eine direkte Kontoauskunft.
 */
final class PartnerRegistrationController extends Controller
{
    /**
     * Zeigt das responsive Registrierungsformular ohne vertraulichen Tenant-Kontext.
     */
    public function create(LegalDocumentRepository $documents): View
    {
        return view('registration.create', [
            'tenantTypes' => TenantType::cases(),
            'countries' => config('merlin.registration.supported_countries'),
            'locales' => config('merlin.registration.supported_locales'),
            'termsDocument' => $documents->get('terms'),
            'privacyDocument' => $documents->get('privacy'),
        ]);
    }

    /**
     * Startet die Registrierung und leitet immer auf dieselbe neutrale Folgeseite weiter.
     */
    public function store(
        StorePartnerRegistrationRequest $request,
        StartPartnerRegistration $startRegistration,
    ): RedirectResponse {
        $validated = $request->validated();

        $startRegistration->handle(new StartPartnerRegistrationData(
            $validated['first_name'],
            $validated['last_name'],
            $validated['email'],
            $validated['partner_display_name'],
            TenantType::from($validated['tenant_type']),
            $validated['country_code'],
            $validated['locale'],
            (string) Str::uuid(),
            true,
            $validated['terms_version'],
            $validated['terms_digest'],
            true,
            $validated['privacy_version'],
            $validated['privacy_digest'],
        ));

        return redirect()->route('registration.pending');
    }

    /**
     * Zeigt die neutrale Versandbestätigung ohne Wiederholung der eingegebenen E-Mail.
     */
    public function pending(): View
    {
        return view('registration.pending');
    }
}
