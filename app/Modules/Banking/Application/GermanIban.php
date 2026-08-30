<?php

namespace App\Modules\Banking\Application;

use InvalidArgumentException;

/**
 * Prüft und berechnet deutsche IBANs nach der öffentlich dokumentierten Standardbildung.
 *
 * Der Dienst bestätigt weder Kontoexistenz noch Kontoinhaberschaft. Institutsspezifische
 * Sonderregeln sind gemäß Produktentscheidung nicht Bestandteil dieser Eingabehilfe.
 */
final class GermanIban
{
    /**
     * Berechnet aus achtstelliger BLZ und maximal zehnstelliger Kontonummer eine IBAN.
     *
     * @throws InvalidArgumentException Bei syntaktisch ungültigen Eingaben.
     */
    public function calculate(string $bankCode, string $accountNumber): string
    {
        $bankCode = preg_replace('/\D+/', '', $bankCode) ?? '';
        $accountNumber = preg_replace('/\D+/', '', $accountNumber) ?? '';

        if (preg_match('/^\d{8}$/', $bankCode) !== 1
            || preg_match('/^\d{1,10}$/', $accountNumber) !== 1) {
            throw new InvalidArgumentException('Bankleitzahl oder Kontonummer ist ungültig.');
        }

        $bban = $bankCode.str_pad($accountNumber, 10, '0', STR_PAD_LEFT);
        $checkDigits = str_pad((string) (98 - $this->mod97($bban.'131400')), 2, '0', STR_PAD_LEFT);
        $iban = 'DE'.$checkDigits.$bban;

        if (! $this->isValid($iban)) {
            throw new InvalidArgumentException('Die berechnete IBAN konnte nicht verifiziert werden.');
        }

        return $iban;
    }

    /**
     * Normalisiert eine deutsche IBAN und prüft Länge sowie Modulo-97-Prüfziffer.
     */
    public function normalizeAndValidate(string $iban): string
    {
        $normalized = mb_strtoupper(preg_replace('/\s+/', '', trim($iban)) ?? '');

        if (! $this->isValid($normalized)) {
            throw new InvalidArgumentException('Die IBAN ist formal ungültig.');
        }

        return $normalized;
    }

    /**
     * Liefert die in einer deutschen IBAN enthaltene Bankleitzahl.
     */
    public function bankCode(string $iban): string
    {
        return substr($this->normalizeAndValidate($iban), 4, 8);
    }

    private function isValid(string $iban): bool
    {
        return preg_match('/^DE\d{20}$/', $iban) === 1
            && $this->mod97(substr($iban, 4).'1314'.substr($iban, 2, 2)) === 1;
    }

    /**
     * Berechnet den Rest großer Dezimalzahlen ohne plattformabhängige Integer-Überläufe.
     */
    private function mod97(string $number): int
    {
        $remainder = 0;

        foreach (str_split($number) as $digit) {
            $remainder = (($remainder * 10) + (int) $digit) % 97;
        }

        return $remainder;
    }
}
