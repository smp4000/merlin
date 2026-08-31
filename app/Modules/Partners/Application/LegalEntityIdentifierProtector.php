<?php

namespace App\Modules\Partners\Application;

use App\Enums\LegalEntityIdentifierType;

/**
 * Normalisiert, maskiert und fingerprintet vertrauliche Gesellschaftskennungen.
 *
 * Der Fingerprint ist durch Tenant, Typ und Anwendungsschlüssel kontextgebunden. Damit
 * sind nur tenantinterne Dublettenprüfungen möglich; ein globaler Abgleich wird verhindert.
 */
final class LegalEntityIdentifierProtector
{
    /**
     * Entfernt ausschließlich Darstellungszeichen und erhält fachlich relevante Zeichen.
     */
    public function normalize(LegalEntityIdentifierType $type, string $value): string
    {
        $value = mb_strtoupper(trim($value));

        return match ($type) {
            LegalEntityIdentifierType::VatId,
            LegalEntityIdentifierType::EconomicId,
            LegalEntityIdentifierType::EmployerNumber => (string) preg_replace('/[\s\-\.]+/u', '', $value),
            LegalEntityIdentifierType::NationalTaxNumber,
            LegalEntityIdentifierType::CommercialRegister => (string) preg_replace('/\s+/u', ' ', $value),
        };
    }

    /**
     * Zeigt nur die letzten vier Zeichen; die übrigen Zeichen bleiben unkenntlich.
     */
    public function mask(string $normalizedValue): string
    {
        return str_repeat('•', max(0, mb_strlen($normalizedValue) - 4)).mb_substr($normalizedValue, -4);
    }

    /**
     * Erzeugt einen nicht umkehrbaren, tenantgebundenen Dubletten-Fingerprint.
     */
    public function fingerprint(
        int $tenantId,
        LegalEntityIdentifierType $type,
        string $countryCode,
        string $normalizedValue,
    ): string {
        $context = implode('|', [$tenantId, $type->value, mb_strtoupper($countryCode), $normalizedValue]);

        return hash_hmac('sha256', $context, (string) config('app.key'));
    }
}
