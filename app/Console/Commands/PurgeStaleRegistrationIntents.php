<?php

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;
use App\Models\ConsentRecord;
use App\Models\RegistrationIntent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Löscht unbestätigte Registrierungsvorgänge nach der freigegebenen Sieben-Tage-Frist.
 *
 * Bestätigte Vorgänge und deren Zustimmungsnachweise werden nicht berührt. Das globale
 * minimierte Security-Audit bleibt für seine separat festzulegende Frist erhalten.
 */
final class PurgeStaleRegistrationIntents extends Command
{
    protected $signature = 'merlin:registrations:purge';

    protected $description = 'Löscht seit sieben Tagen unbestätigte Partnerregistrierungen.';

    /**
     * Entfernt nur terminal unbestätigte oder längst abgelaufene Vorgänge ohne Tenant.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('merlin.registration.pending_retention_days', 7));

        $candidateIds = RegistrationIntent::query()
            ->whereNull('tenant_id')
            ->whereIn('status', [
                RegistrationStatus::EmailPending,
                RegistrationStatus::Expired,
                RegistrationStatus::Revoked,
            ])
            ->where('last_confirmation_sent_at', '<=', $cutoff)
            ->pluck('id');

        $deleted = 0;

        foreach ($candidateIds as $candidateId) {
            $deleted += DB::transaction(function () use ($candidateId, $cutoff): int {
                // Dieselbe Parent-Zeile wird auch von der Bestätigung gesperrt. Nach
                // Erhalt des Locks werden alle Kriterien erneut ausgewertet, sodass
                // eine parallel bestätigte Registrierung niemals Evidenz verliert.
                $intent = RegistrationIntent::query()
                    ->whereKey($candidateId)
                    ->lockForUpdate()
                    ->first();

                if ($intent === null
                    || $intent->tenant_id !== null
                    || ! in_array($intent->status, [
                        RegistrationStatus::EmailPending,
                        RegistrationStatus::Expired,
                        RegistrationStatus::Revoked,
                    ], true)
                    || $intent->last_confirmation_sent_at->isAfter($cutoff)) {
                    return 0;
                }

                // Nachweise und Parent werden gemeinsam committed oder zurückgerollt.
                ConsentRecord::query()
                    ->where('registration_intent_id', $intent->getKey())
                    ->delete();

                return RegistrationIntent::query()->whereKey($intent->getKey())->delete();
            });
        }

        $this->info("{$deleted} unbestätigte Registrierung(en) gelöscht.");

        return self::SUCCESS;
    }
}
