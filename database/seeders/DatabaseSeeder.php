<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Bündelt die freigegebenen Basisdaten für die aktuelle Entwicklungsumgebung.
 *
 * Fachliche Katalog- und Mandantendaten werden später durch eigene, geprüfte Seeder
 * ergänzt. Der lokale Administrator ist ausschließlich eine Zugangshilfe für das
 * technische Grundgerüst.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Führt die aktuell registrierten Basis-Seeder in definierter Reihenfolge aus.
     */
    public function run(): void
    {
        $this->call([
            LocalAdminSeeder::class,
            FuelStationBrandSeeder::class,
            BankDirectorySourceSeeder::class,
        ]);
    }
}
