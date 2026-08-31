<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validiert ausschließlich fachliche Onboarding-Eingaben; der Mandant wird niemals aus
 * dem Request übernommen, sondern serverseitig aus der aktiven Membership aufgelöst.
 */
final class StorePartnerOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:200'],
            'legal_form' => ['required', Rule::in(['sole_proprietorship', 'gbr', 'ug', 'gmbh', 'ag', 'kg', 'gmbh_co_kg', 'other'])],
            'billing_street' => ['required', 'string', 'max:160'],
            'billing_house_number' => ['required', 'string', 'max:30'],
            'billing_address_addition' => ['nullable', 'string', 'max:120'],
            'billing_postal_code' => ['required', 'string', 'max:20'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_region' => ['required', 'string', 'max:120'],
            'billing_country_code' => ['required', Rule::in(['DE', 'AT', 'CH'])],
            'billing_email' => ['required', 'email:rfc', 'max:254'],
            'vat_id' => ['nullable', 'string', 'max:40'],
            'station_name' => ['required', 'string', 'max:160'],
            'fuel_station_brand_id' => ['nullable', 'integer', 'exists:fuel_station_brands,id'],
            'station_street' => ['required', 'string', 'max:160'],
            'station_house_number' => ['required', 'string', 'max:30'],
            'station_address_addition' => ['nullable', 'string', 'max:120'],
            'station_postal_code' => ['required', 'string', 'max:20'],
            'station_city' => ['required', 'string', 'max:120'],
            'station_region' => ['required', 'string', 'max:120'],
            'station_country_code' => ['required', Rule::in(['DE', 'AT', 'CH'])],
            'manager_salutation' => ['required', Rule::in(['female', 'male', 'diverse', 'none'])],
            'manager_first_name' => ['required', 'string', 'max:80'],
            'manager_last_name' => ['required', 'string', 'max:80'],
            'manager_email' => ['required', 'email:rfc', 'max:254'],
            'manager_phone' => ['required', 'string', 'max:40'],
            'manager_fax' => ['nullable', 'string', 'max:40'],
            'add_bank_account' => ['nullable', 'boolean'],
            'account_holder' => ['nullable', 'required_if:add_bank_account,1', 'string', 'max:200'],
            'iban' => ['nullable', 'string', 'max:34'],
            'bank_code' => ['nullable', 'digits:8'],
            'account_number' => ['nullable', 'digits_between:1,10'],
            'confirm_iban_result' => ['nullable', 'required_if:add_bank_account,1', 'accepted'],
        ];
    }

    /**
     * Übersetzt technische Feldnamen für verständliche serverseitige Fehlermeldungen.
     * Die Zuordnung enthält bewusst keine Werte und verhindert damit, dass Bankdaten in
     * einer Validierungsübersicht oder einem Log erscheinen.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'legal_name' => 'Firma',
            'legal_form' => 'Rechtsform',
            'billing_street' => 'Straße der Rechnungsanschrift',
            'billing_house_number' => 'Hausnummer der Rechnungsanschrift',
            'billing_address_addition' => 'Adresszusatz der Rechnungsanschrift',
            'billing_postal_code' => 'PLZ der Rechnungsanschrift',
            'billing_city' => 'Ort der Rechnungsanschrift',
            'billing_region' => 'Bundesland oder Region der Rechnungsanschrift',
            'billing_country_code' => 'Land der Rechnungsanschrift',
            'billing_email' => 'E-Mail für Rechnungen',
            'vat_id' => 'USt-IdNr.',
            'station_name' => 'Stationsname',
            'fuel_station_brand_id' => 'Tankstellenmarke',
            'station_street' => 'Straße der Tankstelle',
            'station_house_number' => 'Hausnummer der Tankstelle',
            'station_address_addition' => 'Adresszusatz der Tankstelle',
            'station_postal_code' => 'PLZ der Tankstelle',
            'station_city' => 'Ort der Tankstelle',
            'station_region' => 'Bundesland oder Region der Tankstelle',
            'station_country_code' => 'Land der Tankstelle',
            'manager_salutation' => 'Anrede der Stationsleitung',
            'manager_first_name' => 'Vorname der Stationsleitung',
            'manager_last_name' => 'Nachname der Stationsleitung',
            'manager_email' => 'E-Mail der Stationsleitung',
            'manager_phone' => 'Telefon der Stationsleitung',
            'manager_fax' => 'Fax der Stationsleitung',
            'add_bank_account' => 'Auswahl zur Bankverbindung',
            'account_holder' => 'Kontoinhaber',
            'iban' => 'IBAN',
            'bank_code' => 'Bankleitzahl',
            'account_number' => 'Kontonummer',
            'confirm_iban_result' => 'Prüfung der Bankverbindung',
        ];
    }

    /**
     * Verlangt bei aktivierter Bankverbindung entweder eine IBAN oder das vollständige
     * Paar aus BLZ und Kontonummer, ohne Bankdaten für das Onboarding generell zu erzwingen.
     */
    public function after(): array
    {
        return [function ($validator): void {
            if (! $this->boolean('add_bank_account')) {
                return;
            }

            if (! filled($this->input('iban'))
                && (! filled($this->input('bank_code')) || ! filled($this->input('account_number')))) {
                $validator->errors()->add('iban', 'Bitte IBAN oder Bankleitzahl und Kontonummer angeben.');
            }
        }];
    }
}
