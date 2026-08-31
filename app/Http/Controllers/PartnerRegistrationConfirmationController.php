<?php

namespace App\Http\Controllers;

use App\Foundation\Legal\LegalDocumentRepository;
use App\Foundation\Tenancy\TenantContextSession;
use App\Http\Requests\ConfirmPartnerRegistrationRequest;
use App\Modules\Registration\Application\ConfirmPartnerRegistration;
use App\Modules\Registration\Application\Data\ConfirmPartnerRegistrationData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trennt die scanner-sichere Linkanzeige vom zustandsändernden Bestätigungs-POST.
 */
final class PartnerRegistrationConfirmationController extends Controller
{
    /**
     * Prüft den Link nur lesend und zeigt anschließend die lokale Passwortseite.
     */
    public function show(string $intent, LegalDocumentRepository $documents): View|Response
    {
        return response()
            ->view('registration.confirm', [
                'intentPublicId' => $intent,
                'termsDocument' => $documents->get('terms'),
                'privacyDocument' => $documents->get('privacy'),
            ])
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Verbraucht den Token per CSRF-geschütztem POST und regeneriert danach die Sitzung.
     */
    public function store(
        ConfirmPartnerRegistrationRequest $request,
        string $intent,
        ConfirmPartnerRegistration $confirmation,
        TenantContextSession $tenantSession,
    ): JsonResponse|RedirectResponse|Response {
        try {
            $result = $confirmation->handle(new ConfirmPartnerRegistrationData(
                $intent,
                $request->validated('confirmation_token'),
                $request->validated('password'),
                (string) Str::uuid(),
                true,
                $request->validated('terms_version'),
                $request->validated('terms_digest'),
                true,
                $request->validated('privacy_version'),
                $request->validated('privacy_digest'),
            ));
        } catch (ModelNotFoundException) {
            return $this->invalidResponse($request->expectsJson());
        }

        Auth::login($result->user);
        $tenantSession->select($request, $result->user, $result->tenant->public_id);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_to' => route('onboarding.show'),
            ]);
        }

        return redirect()->route('onboarding.show')
            ->with('status', __('registration.confirm.success'));
    }

    /**
     * Liefert für alle ungültigen Linkzustände dieselbe tokenfreie Fehlerdarstellung.
     */
    private function invalidResponse(bool $asJson = false): JsonResponse|Response
    {
        if ($asJson) {
            return response()->json([
                'message' => __('registration.invalid.title'),
            ], 410);
        }

        return response()
            ->view('registration.invalid', status: 410)
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }
}
