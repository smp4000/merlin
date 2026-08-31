<?php

namespace Database\Seeders;

use App\Enums\LegalFormStatus;
use App\Models\LegalForm;
use Illuminate\Database\Seeder;

/**
 * Pflegt den zentralen DACH-Rechtsformkatalog idempotent und historienfreundlich.
 *
 * Der Seeder entfernt bewusst keine unbekannten oder bereits referenzierten Einträge.
 * Änderungen an gesetzlichen Formen erfolgen durch Aktualisieren oder Deaktivieren eines
 * stabilen Schlüssels, niemals durch stilles Umschreiben bestehender Gesellschaften.
 */
final class LegalFormSeeder extends Seeder
{
    /**
     * Ergänzt die für Deutschland, Österreich und die Schweiz relevanten Grundformen.
     */
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            LegalForm::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'labels' => ['de' => $definition['de'], 'en' => $definition['en']],
                    'country_codes' => [$definition['country']],
                    'status' => LegalFormStatus::Active,
                    'valid_from' => null,
                    'valid_until' => null,
                ],
            );
        }
    }

    /**
     * @return list<array{key: string, de: string, en: string, country: string}>
     */
    private function definitions(): array
    {
        return [
            $this->form('de_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'DE'),
            $this->form('de_registered_merchant', 'Eingetragener Kaufmann (e. K.)', 'Registered merchant', 'DE'),
            $this->form('de_gbr', 'Gesellschaft bürgerlichen Rechts (GbR)', 'Civil-law partnership', 'DE'),
            $this->form('de_ohg', 'Offene Handelsgesellschaft (OHG)', 'General partnership', 'DE'),
            $this->form('de_kg', 'Kommanditgesellschaft (KG)', 'Limited partnership', 'DE'),
            $this->form('de_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH)', 'Limited liability company', 'DE'),
            $this->form('de_ug_limited', 'Unternehmergesellschaft (haftungsbeschränkt)', 'Entrepreneurial company', 'DE'),
            $this->form('de_gmbh_co_kg', 'GmbH & Co. KG', 'GmbH & Co. KG', 'DE'),
            $this->form('de_ag', 'Aktiengesellschaft (AG)', 'Stock corporation', 'DE'),
            $this->form('de_kgaa', 'Kommanditgesellschaft auf Aktien (KGaA)', 'Partnership limited by shares', 'DE'),
            $this->form('de_cooperative', 'Eingetragene Genossenschaft (eG)', 'Registered cooperative', 'DE'),
            $this->form('de_partnership', 'Partnerschaftsgesellschaft (PartG)', 'Professional partnership', 'DE'),
            $this->form('de_partnership_limited', 'Partnerschaftsgesellschaft mbB', 'Professional partnership with limited liability', 'DE'),
            $this->form('de_association', 'Eingetragener Verein (e. V.)', 'Registered association', 'DE'),
            $this->form('de_foundation', 'Stiftung', 'Foundation', 'DE'),
            $this->form('de_se', 'Europäische Gesellschaft (SE)', 'Societas Europaea', 'DE'),

            $this->form('at_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'AT'),
            $this->form('at_registered_merchant', 'Eingetragenes Unternehmen (e.U.)', 'Registered sole trader', 'AT'),
            $this->form('at_gesbr', 'Gesellschaft bürgerlichen Rechts (GesbR)', 'Civil-law partnership', 'AT'),
            $this->form('at_og', 'Offene Gesellschaft (OG)', 'General partnership', 'AT'),
            $this->form('at_kg', 'Kommanditgesellschaft (KG)', 'Limited partnership', 'AT'),
            $this->form('at_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH)', 'Limited liability company', 'AT'),
            $this->form('at_ag', 'Aktiengesellschaft (AG)', 'Stock corporation', 'AT'),
            $this->form('at_cooperative', 'Genossenschaft (eGen)', 'Registered cooperative', 'AT'),
            $this->form('at_association', 'Verein', 'Association', 'AT'),
            $this->form('at_private_foundation', 'Privatstiftung', 'Private foundation', 'AT'),
            $this->form('at_se', 'Europäische Gesellschaft (SE)', 'Societas Europaea', 'AT'),

            $this->form('ch_sole_proprietorship', 'Einzelunternehmen', 'Sole proprietorship', 'CH'),
            $this->form('ch_general_partnership', 'Kollektivgesellschaft', 'General partnership', 'CH'),
            $this->form('ch_limited_partnership', 'Kommanditgesellschaft', 'Limited partnership', 'CH'),
            $this->form('ch_gmbh', 'Gesellschaft mit beschränkter Haftung (GmbH/Sàrl/Sagl)', 'Limited liability company', 'CH'),
            $this->form('ch_ag', 'Aktiengesellschaft (AG/SA)', 'Stock corporation', 'CH'),
            $this->form('ch_cooperative', 'Genossenschaft', 'Cooperative', 'CH'),
            $this->form('ch_association', 'Verein', 'Association', 'CH'),
            $this->form('ch_foundation', 'Stiftung', 'Foundation', 'CH'),
        ];
    }

    /** @return array{key: string, de: string, en: string, country: string} */
    private function form(string $key, string $de, string $en, string $country): array
    {
        return compact('key', 'de', 'en', 'country');
    }
}
