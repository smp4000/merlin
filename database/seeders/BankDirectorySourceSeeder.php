<?php

namespace Database\Seeders;

use App\Models\BankDirectorySource;
use Illuminate\Database\Seeder;

/**
 * Legt ausschließlich die vom Auftraggeber bestätigte öffentliche Bundesbank-Quelle an.
 */
final class BankDirectorySourceSeeder extends Seeder
{
    public function run(): void
    {
        BankDirectorySource::query()->updateOrCreate(
            ['name' => 'Deutsche Bundesbank – Bankleitzahlen'],
            [
                'provider' => 'Deutsche Bundesbank',
                'url' => config('merlin.bank_directory.source_url'),
                'allowed_host' => 'www.bundesbank.de',
                'format' => 'csv',
                'is_active' => true,
            ],
        );
    }
}
