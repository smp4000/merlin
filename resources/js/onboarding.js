import { calculateGermanIban, formatIban } from './german-iban';

/**
 * Steuert den geschützten Merlin-Einrichtungsassistenten einschließlich IBAN-Eingabehilfe.
 *
 * Die deutsche IBAN-Vorschau entsteht ausschließlich im Browser. BLZ und Kontonummer
 * werden dabei weder an Dritte übertragen noch lokal gespeichert. Beim finalen Absenden
 * berechnet beziehungsweise validiert Laravel das Ergebnis nochmals unabhängig.
 */
class MerlinOnboarding {
    /** @param {HTMLElement} root Wurzelelement des Einrichtungsassistenten. */
    constructor(root) {
        this.root = root;
        this.panels = [...root.querySelectorAll('[data-tab-panel]')];
        this.tabButtons = [...root.querySelectorAll('[data-tab-button]')];
        this.previousButton = root.querySelector('[data-previous]');
        this.nextButton = root.querySelector('[data-next]');
        this.submitButton = root.querySelector('[data-submit]');
        this.activePanel = 0;
        this.generatedIban = null;

        this.bindNavigation();
        this.bindAddressCopy();
        this.bindBankAccount();
    }

    /** Verknüpft Tabs und Vor-/Zurück-Aktionen, ohne Daten zwischen den Schritten zu verlieren. */
    bindNavigation() {
        this.tabButtons.forEach((button, index) => {
            button.addEventListener('click', () => this.showPanel(index));
        });
        this.previousButton.addEventListener('click', () => this.showPanel(this.activePanel - 1));
        this.nextButton.addEventListener('click', () => this.showPanel(this.activePanel + 1));
    }

    /** Zeigt genau einen Onboardingbereich und führt den Tastaturfokus in dessen erstes Feld. */
    showPanel(index) {
        this.activePanel = Math.max(0, Math.min(index, this.panels.length - 1));
        this.panels.forEach((panel, panelIndex) => {
            panel.classList.toggle('is-active', panelIndex === this.activePanel);
        });
        this.tabButtons.forEach((button, buttonIndex) => {
            button.classList.toggle('is-active', buttonIndex === this.activePanel);
            button.setAttribute('aria-selected', buttonIndex === this.activePanel ? 'true' : 'false');
        });
        this.previousButton.disabled = this.activePanel === 0;
        this.nextButton.hidden = this.activePanel === this.panels.length - 1;
        this.submitButton.hidden = this.activePanel !== this.panels.length - 1;
        this.panels[this.activePanel].querySelector('input, select, button')?.focus({ preventScroll: true });
        this.root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /** Übernimmt die Rechnungsanschrift bewusst erst nach einer ausdrücklichen Nutzeraktion. */
    bindAddressCopy() {
        this.root.querySelector('[data-copy-billing]').addEventListener('click', () => {
            ['street', 'house_number', 'address_addition', 'postal_code', 'city', 'region', 'country_code']
                .forEach((key) => {
                    this.root.querySelector(`[data-station="${key}"]`).value =
                        this.root.querySelector(`[data-billing="${key}"]`).value;
                });
        });
    }

    /**
     * Aktiviert die optionale Banksektion und die lokale IBAN-Eingabehilfe.
     * Eine manuell eingegebene IBAN wird niemals ohne eine Berechnungsaktion überschrieben.
     */
    bindBankAccount() {
        this.bankToggle = this.root.querySelector('[data-bank-toggle]');
        this.bankFields = this.root.querySelector('[data-bank-fields]');
        this.ibanInput = this.root.querySelector('[data-iban]');
        this.bankCodeInput = this.root.querySelector('[data-bank-code]');
        this.accountNumberInput = this.root.querySelector('[data-account-number]');
        this.calculateButton = this.root.querySelector('[data-calculate-iban]');
        this.ibanResult = this.root.querySelector('[data-iban-result]');

        const toggleBankFields = () => {
            this.bankFields.hidden = !this.bankToggle.checked;
        };

        this.bankToggle.addEventListener('change', toggleBankFields);
        this.calculateButton.addEventListener('click', () => this.calculateIban());
        [this.bankCodeInput, this.accountNumberInput].forEach((input) => {
            input.addEventListener('input', () => this.clearStaleGeneratedIban());
            input.addEventListener('blur', () => {
                if (this.hasCompleteCalculationInput()) this.calculateIban();
            });
        });
        this.ibanInput.addEventListener('input', () => {
            if (this.ibanInput.value !== this.generatedIban) {
                this.generatedIban = null;
                this.setIbanResult('');
            }
        });

        toggleBankFields();

        if (!this.ibanInput.value && this.hasCompleteCalculationInput()) this.calculateIban();
    }

    /** Berechnet die Standard-IBAN und schreibt das gruppierte Ergebnis in das sichtbare Feld. */
    calculateIban() {
        const bankCode = this.bankCodeInput.value.trim();
        const accountNumber = this.accountNumberInput.value.trim();

        if (!/^\d{8}$/.test(bankCode)) {
            this.setIbanResult(this.root.dataset.ibanBankCodeError, true);
            this.bankCodeInput.focus();
            return;
        }

        if (!/^\d{1,10}$/.test(accountNumber)) {
            this.setIbanResult(this.root.dataset.ibanAccountNumberError, true);
            this.accountNumberInput.focus();
            return;
        }

        let compactIban;

        try {
            compactIban = calculateGermanIban(bankCode, accountNumber);
        } catch {
            this.setIbanResult(this.root.dataset.ibanCalculationError, true);
            return;
        }

        this.generatedIban = formatIban(compactIban);
        this.ibanInput.value = this.generatedIban;
        this.setIbanResult(this.root.dataset.ibanSuccess);
    }

    /** Entfernt nur ein zuvor automatisch erzeugtes Ergebnis, niemals eine manuelle IBAN. */
    clearStaleGeneratedIban() {
        if (this.generatedIban !== null && this.ibanInput.value === this.generatedIban) {
            this.ibanInput.value = '';
        }

        this.generatedIban = null;
        this.setIbanResult('');
    }

    /** Prüft, ob beide Eingaben ohne stilles Bereinigen vollständig berechenbar sind. */
    hasCompleteCalculationInput() {
        return /^\d{8}$/.test(this.bankCodeInput.value.trim())
            && /^\d{1,10}$/.test(this.accountNumberInput.value.trim());
    }

    /** Meldet Erfolg oder Eingabefehler barrierefrei direkt unter der Berechnungsaktion. */
    setIbanResult(message, isError = false) {
        this.ibanResult.textContent = message;
        this.ibanResult.classList.toggle('is-error', isError);
        this.ibanResult.classList.toggle('is-success', message !== '' && !isError);
    }
}

document.querySelectorAll('[data-onboarding]').forEach((root) => new MerlinOnboarding(root));
