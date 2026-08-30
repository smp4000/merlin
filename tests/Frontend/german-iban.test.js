import assert from 'node:assert/strict';
import test from 'node:test';

import { calculateGermanIban, formatIban } from '../../resources/js/german-iban.js';

/** Beweist die browserseitige Parität zu den serverseitigen deutschen Testvektoren. */
test('berechnet und formatiert veröffentlichte deutsche Standardbeispiele', () => {
    assert.equal(calculateGermanIban('37040044', '532013000'), 'DE89370400440532013000');
    assert.equal(calculateGermanIban('53060180', '300250503'), 'DE84530601800300250503');
    assert.equal(formatIban('DE84530601800300250503'), 'DE84 5306 0180 0300 2505 03');
});

/** Ungültige Eingaben dürfen nicht stillschweigend bereinigt oder berechnet werden. */
test('weist unvollständige oder nichtnumerische Eingaben ab', () => {
    assert.throws(() => calculateGermanIban('5306018', '300250503'), /invalid_bank_code/);
    assert.throws(() => calculateGermanIban('53060180', '30025A503'), /invalid_account_number/);
    assert.throws(() => calculateGermanIban('53060180', '12345678901'), /invalid_account_number/);
});
