<?php

namespace Database\Seeders;

use App\Models\FuelStationBrand;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Importiert den versionierten DACH-Markenkatalog idempotent in die globale Auswahlliste.
 */
final class FuelStationBrandSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/data/fuel-station-brands-dach.json');
        $catalog = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! isset($catalog['brands']) || ! is_array($catalog['brands'])) {
            throw new RuntimeException('Der DACH-Markenkatalog besitzt keine gültige Markenliste.');
        }

        foreach ($catalog['brands'] as $brand) {
            FuelStationBrand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'country_codes' => $brand['countries'],
                    'status' => $brand['status'],
                ],
            );
        }
    }
}
