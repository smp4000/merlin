<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wendet ausschließlich freigegebene Oberflächensprachen auf Webanfragen an.
 *
 * Die Sprachwahl ist eine Komforteinstellung und kein Autorisierungsnachweis. Unbekannte
 * Werte werden ignoriert; Deutsch bleibt der definierte Fallback.
 */
final class ApplyLocale
{
    /**
     * Speichert eine erlaubte explizite Auswahl in der Sitzung und aktiviert sie je Request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('merlin.registration.supported_locales', ['de']);
        $requestedLocale = mb_strtolower((string) $request->query('lang'));

        if (in_array($requestedLocale, $supportedLocales, true)) {
            $request->session()->put('locale', $requestedLocale);
        }

        $locale = (string) $request->session()->get('locale', config('app.locale', 'de'));
        App::setLocale(in_array($locale, $supportedLocales, true) ? $locale : 'de');

        return $next($request);
    }
}
