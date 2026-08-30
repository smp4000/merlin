<?php

namespace App\Console\Commands;

use App\Models\BankDirectorySource;
use App\Modules\Banking\Application\ImportBankDirectory as Importer;
use Illuminate\Console\Command;

/**
 * Startet den kontrollierten Bundesbank-Import für Betrieb und lokale Entwicklung.
 */
final class ImportBankDirectory extends Command
{
    protected $signature = 'bank-directory:import';

    protected $description = 'Importiert die aktive öffentliche Bundesbank-Bankleitzahlendatei.';

    public function handle(Importer $importer): int
    {
        $source = BankDirectorySource::query()->where('is_active', true)->first();

        if ($source === null) {
            $this->error('Es ist keine aktive Bankverzeichnisquelle vorhanden.');

            return self::FAILURE;
        }

        $version = $importer->handle($source);
        $this->info("Bankverzeichnis {$version->sha256} mit {$version->entry_count} Einträgen ist aktiv.");

        return self::SUCCESS;
    }
}
