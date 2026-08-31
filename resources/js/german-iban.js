/**
 * Berechnet eine deutsche IBAN nach der freigegebenen Standardbildung.
 *
 * Die Funktion bestätigt weder Kontoexistenz noch Kontoinhaberschaft und bildet keine
 * institutsspezifischen Sonderregeln ab. Sie ist daher ausschließlich eine Eingabehilfe;
 * Laravel wiederholt die Berechnung beim Speichern auf einer unabhängigen Codebasis.
 *
 * @param {string} bankCode Achtstellige deutsche Bankleitzahl.
 * @param {string} accountNumber Ein- bis zehnstellige Kontonummer.
 * @returns {string} Unformatierte, mathematisch geprüfte deutsche IBAN.
 * @throws {TypeError} Wenn BLZ oder Kontonummer syntaktisch ungültig sind.
 * @throws {Error} Wenn die berechnete IBAN die Gegenprüfung nicht besteht.
 */
export function calculateGermanIban(bankCode, accountNumber) {
    if (!/^\d{8}$/.test(bankCode)) {
        throw new TypeError('invalid_bank_code');
    }

    if (!/^\d{1,10}$/.test(accountNumber)) {
        throw new TypeError('invalid_account_number');
    }

    const bban = bankCode + accountNumber.padStart(10, '0');
    const checkDigits = String(98 - mod97(`${bban}131400`)).padStart(2, '0');
    const iban = `DE${checkDigits}${bban}`;

    // Die Gegenprüfung verhindert, dass ein Implementierungsfehler als scheinbar
    // gültige Bankverbindung in das Formular übernommen wird.
    if (mod97(`${iban.slice(4)}1314${iban.slice(2, 4)}`) !== 1) {
        throw new Error('calculation_verification_failed');
    }

    return iban;
}

/** Formatiert eine kompakte IBAN ausschließlich für die lesbare Anzeige im Eingabefeld. */
export function formatIban(iban) {
    return iban.replace(/(.{4})/g, '$1 ').trim();
}

/**
 * Normalisiert und prüft eine vorhandene deutsche IBAN für die unmittelbare Formularhilfe.
 *
 * @param {string} iban Vom Benutzer eingegebene, gegebenenfalls gruppierte IBAN.
 * @returns {string} Normalisierte deutsche IBAN ohne Leerzeichen.
 * @throws {TypeError} Wenn Format oder Modulo-97-Prüfziffer ungültig sind.
 */
export function normalizeAndValidateGermanIban(iban) {
    const normalized = iban.toUpperCase().replace(/\s+/g, '');

    if (!/^DE\d{20}$/.test(normalized)
        || mod97(`${normalized.slice(4)}1314${normalized.slice(2, 4)}`) !== 1) {
        throw new TypeError('invalid_iban');
    }

    return normalized;
}

/** Berechnet den Rest großer Dezimalzahlen ziffernweise ohne JavaScript-Zahlüberlauf. */
function mod97(number) {
    let remainder = 0;

    for (const digit of number) {
        remainder = ((remainder * 10) + Number(digit)) % 97;
    }

    return remainder;
}
