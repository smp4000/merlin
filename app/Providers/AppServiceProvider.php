<?php

namespace App\Providers;

use App\Modules\Stations\Contracts\StationSearchProvider;
use App\Modules\Stations\Infrastructure\BenzinpreisAktuellStationSearchProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Registriert anwendungsweite Schutzmechanismen außerhalb einzelner Fachmodule.
 */
class AppServiceProvider extends ServiceProvider
{
    /** Bindet austauschbare Fachverträge an die freigegebenen Pilotadapter. */
    public function register(): void
    {
        $this->app->bind(StationSearchProvider::class, BenzinpreisAktuellStationSearchProvider::class);
    }

    /**
     * Begrenzt Registrierungs- und Tokenversuche unabhängig nach IP und pseudonymem Schlüssel.
     *
     * Der HMAC verhindert, dass rohe E-Mail-Adressen in Cache-Schlüsseln erscheinen. In einer
     * Mehrinstanzumgebung muss der konfigurierte Cache dafür zentral über Redis betrieben werden.
     */
    public function boot(): void
    {
        RateLimiter::for('partner-registration', function (Request $request): array {
            $normalizedEmail = mb_strtolower(trim((string) $request->input('email')));
            $emailDigest = hash_hmac('sha256', $normalizedEmail, (string) config('app.key'));

            return [
                Limit::perMinutes(15, 5)->by('registration-ip:'.$request->ip()),
                Limit::perHour(3)->by('registration-email:'.$emailDigest),
            ];
        });

        RateLimiter::for('registration-confirmation-view', function (Request $request): Limit {
            return Limit::perMinutes(15, 30)->by('confirmation-view-ip:'.$request->ip());
        });

        RateLimiter::for('registration-confirmation-submit', function (Request $request): array {
            return [
                Limit::perMinutes(15, 10)->by('confirmation-submit-ip:'.$request->ip()),
                Limit::perMinutes(15, 5)->by('confirmation-submit-intent:'.hash('sha256', (string) $request->route('intent'))),
            ];
        });
    }
}
