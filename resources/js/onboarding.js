import {
    calculateGermanIban,
    formatIban,
    normalizeAndValidateGermanIban,
} from './german-iban';

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
        this.form = root.querySelector('[data-onboarding-form]');
        this.validationSummary = root.querySelector('[data-live-validation-summary]');
        this.activePanel = 0;
        this.generatedIban = null;
        this.touchedFields = new Set();
        this.validatedPanels = new Set();

        this.bindNavigation();
        this.bindAddressCopy();
        this.bindBankAccount();
        this.bindLiveValidation();
        this.applyServerErrors();
    }

    /** Verknüpft Tabs und Vor-/Zurück-Aktionen, ohne Daten zwischen den Schritten zu verlieren. */
    bindNavigation() {
        this.tabButtons.forEach((button, index) => {
            button.addEventListener('click', () => this.showPanel(index));
        });
        this.previousButton.addEventListener('click', () => this.showPanel(this.activePanel - 1));
        this.nextButton.addEventListener('click', () => {
            if (this.validatePanel(this.activePanel, true)) {
                this.hideValidationSummary();
                this.showPanel(this.activePanel + 1);
                return;
            }

            this.showValidationSummary();
            this.focusFirstInvalidField(this.activePanel);
        });
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
                    this.root.querySelector(`[data-station="${key}"]`)
                        .dispatchEvent(new Event('input', { bubbles: true }));
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

    /**
     * Aktiviert eine progressive Live-Validierung ohne die serverseitige Prüfung zu ersetzen.
     * Vor der ersten Interaktion bleiben Felder ruhig; nach `blur` werden Fehler während
     * weiterer Eingaben sofort aktualisiert. Bankwerte verlassen dabei niemals den Browser.
     */
    bindLiveValidation() {
        const fields = [...this.form.querySelectorAll('input[name], select[name]')]
            .filter((field) => field.name !== '_token');

        fields.forEach((field) => {
            field.addEventListener('blur', () => {
                this.touchedFields.add(field);
                this.validateField(field, true);
                this.validateBankAlternative(true);
                this.updatePanelState(this.panelIndexFor(field));
            });
            field.addEventListener('input', () => this.revalidateTouchedField(field));
            field.addEventListener('change', () => this.revalidateTouchedField(field));
        });

        this.bankToggle.addEventListener('change', () => {
            if (!this.bankToggle.checked) {
                this.bankFields.querySelectorAll('input[name]').forEach((field) => this.setFieldError(field, ''));
            }
            this.updatePanelState(4);
        });

        this.form.addEventListener('submit', (event) => {
            let firstInvalidPanel = -1;

            this.panels.forEach((panel, index) => {
                if (!this.validatePanel(index, true) && firstInvalidPanel === -1) {
                    firstInvalidPanel = index;
                }
            });

            if (firstInvalidPanel === -1) {
                this.hideValidationSummary();
                return;
            }

            event.preventDefault();
            this.showPanel(firstInvalidPanel);
            this.showValidationSummary();
            this.focusFirstInvalidField(firstInvalidPanel);
        });
    }

    /**
     * Übernimmt serverseitige Laravel-Fehler nach einem Redirect in Feld und Tab.
     * Es werden ausschließlich Meldungen verarbeitet; eingegebene Werte oder Bankdaten
     * werden weder serialisiert noch in JavaScript-Protokolle geschrieben.
     */
    applyServerErrors() {
        const payload = this.root.querySelector('[data-onboarding-server-errors]');

        if (!payload) return;

        let errors;

        try {
            errors = JSON.parse(payload.textContent);
        } catch {
            return;
        }

        let firstInvalidPanel = -1;

        Object.entries(errors).forEach(([fieldName, messages]) => {
            const field = this.form.elements.namedItem(fieldName);

            if (!(field instanceof HTMLElement) || !Array.isArray(messages) || messages.length === 0) return;

            this.touchedFields.add(field);
            this.setFieldError(field, String(messages[0]));
            const panelIndex = this.panelIndexFor(field);
            this.updatePanelState(panelIndex);
            if (firstInvalidPanel === -1) firstInvalidPanel = panelIndex;
        });

        if (firstInvalidPanel >= 0) {
            this.showPanel(firstInvalidPanel);
            this.focusFirstInvalidField(firstInvalidPanel);
        }
    }

    /** Prüft alle wirksamen Felder eines Schritts und kennzeichnet den Tabzustand. */
    validatePanel(index, revealErrors) {
        const fields = [...this.panels[index].querySelectorAll('input[name], select[name]')];
        let isValid = true;

        if (revealErrors) this.validatedPanels.add(index);

        fields.forEach((field) => {
            if (revealErrors) this.touchedFields.add(field);
            if (!this.validateField(field, revealErrors)) isValid = false;
        });

        if (index === 4 && !this.validateBankAlternative(revealErrors)) isValid = false;

        this.updatePanelState(index, isValid);

        return isValid;
    }

    /** Prüft Pflichtwert, E-Mail, Maximallänge und die bankspezifischen Formate eines Feldes. */
    validateField(field, revealError) {
        if (!this.isFieldEffective(field)) {
            this.setFieldError(field, '');
            return true;
        }

        const value = field.type === 'checkbox' ? field.checked : field.value.trim();
        const label = this.fieldLabel(field);
        let message = '';

        const conditionallyRequired = this.bankToggle.checked
            && (field.matches('[data-bank-required]') || field.matches('[data-bank-confirm]'));

        if ((field.required || conditionallyRequired) && (value === '' || value === false)) {
            message = field.matches('[data-bank-confirm]')
                ? this.root.dataset.validationConfirm
                : this.validationMessage('required', label);
        } else if (value !== '' && field.type === 'email' && !field.validity.valid) {
            message = this.validationMessage('email', label);
        } else if (value !== '' && field.maxLength > 0 && String(value).length > field.maxLength) {
            message = this.validationMessage('max', label, field.maxLength);
        } else if (field.matches('[data-iban]') && value !== '') {
            try {
                normalizeAndValidateGermanIban(String(value));
            } catch {
                message = this.root.dataset.validationIban;
            }
        } else if (field.matches('[data-bank-code]') && value !== '' && !/^\d{8}$/.test(String(value))) {
            message = this.root.dataset.ibanBankCodeError;
        } else if (field.matches('[data-account-number]') && value !== '' && !/^\d{1,10}$/.test(String(value))) {
            message = this.root.dataset.ibanAccountNumberError;
        }

        if (revealError || this.touchedFields.has(field)) this.setFieldError(field, message);

        return message === '';
    }

    /** Erzwingt bei aktivierter Banksektion genau eine vollständige Eingabealternative. */
    validateBankAlternative(revealError) {
        if (!this.bankToggle.checked) {
            this.setFieldError(this.ibanInput, '');
            return true;
        }

        const hasIban = this.ibanInput.value.trim() !== '';
        const hasCalculationPair = this.bankCodeInput.value.trim() !== ''
            && this.accountNumberInput.value.trim() !== '';
        const isValid = hasIban || hasCalculationPair;

        if (revealError || this.touchedFields.has(this.ibanInput)) {
            const currentError = this.form.querySelector(`#onboarding-error-${CSS.escape(this.ibanInput.name)}`);
            const showsAlternativeError = currentError?.textContent === this.root.dataset.validationBankAlternative;

            if (!isValid && (this.ibanInput.getAttribute('aria-invalid') !== 'true' || showsAlternativeError)) {
                this.setFieldError(this.ibanInput, this.root.dataset.validationBankAlternative);
            } else if (isValid && showsAlternativeError) {
                this.setFieldError(this.ibanInput, '');
            }
        }

        return isValid;
    }

    /** Aktualisiert einen bereits berührten Wert sowie Fehler- und Vollständigkeitsstatus. */
    revalidateTouchedField(field) {
        if (!this.touchedFields.has(field)) return;

        this.validateField(field, true);
        this.validateBankAlternative(true);
        this.updatePanelState(this.panelIndexFor(field));
    }

    /** Setzt eine barrierefrei verknüpfte Feldmeldung, ohne eingegebene Werte zu kopieren. */
    setFieldError(field, message) {
        const errorId = `onboarding-error-${field.name}`;
        let error = this.form.querySelector(`#${CSS.escape(errorId)}`);

        if (!error) {
            error = document.createElement('small');
            error.id = errorId;
            error.className = 'merlin-live-field-error';
            error.setAttribute('role', 'alert');
            field.closest('label')?.append(error);
        }

        error.textContent = message;
        error.hidden = message === '';
        field.classList.toggle('is-invalid', message !== '');
        field.setAttribute('aria-invalid', message === '' ? 'false' : 'true');

        const describedBy = new Set((field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
        if (message === '') describedBy.delete(errorId);
        else describedBy.add(errorId);
        if (describedBy.size > 0) field.setAttribute('aria-describedby', [...describedBy].join(' '));
        else field.removeAttribute('aria-describedby');
    }

    /** Spiegelt Fehler oder abgeschlossene Schritte sichtbar und für Screenreader im Tab. */
    updatePanelState(index, knownValidity = null) {
        if (index < 0) return;

        const hasVisibleError = this.panels[index]
            .querySelector('.merlin-live-field-error:not([hidden])') !== null;
        const isComplete = knownValidity === true && this.validatedPanels.has(index);
        const button = this.tabButtons[index];

        button.classList.toggle('is-error', hasVisibleError);
        button.classList.toggle('is-complete', !hasVisibleError && isComplete);
    }

    /** Liefert den verständlichen sichtbaren Feldnamen ohne optionale Hilfetexte. */
    fieldLabel(field) {
        const label = field.closest('label');
        const directText = [...(label?.childNodes ?? [])]
            .find((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '')
            ?.textContent.trim();

        return directText ?? label?.querySelector('span')?.textContent.trim() ?? field.name;
    }

    /** Ersetzt ausschließlich bekannte Platzhalter in den deutsch gepflegten UI-Texten. */
    validationMessage(type, field, max = '') {
        return this.root.dataset[`validation${type.charAt(0).toUpperCase()}${type.slice(1)}`]
            .replace(':field', field)
            .replace(':max', String(max));
    }

    /** Bankfelder sind nur wirksam, wenn die optionale Bankverbindung aktiviert wurde. */
    isFieldEffective(field) {
        return !field.closest('[data-bank-fields]') || this.bankToggle.checked;
    }

    /** Ermittelt den zugehörigen Tab ohne Daten oder Mandanteninformationen auszuwerten. */
    panelIndexFor(field) {
        return this.panels.indexOf(field.closest('[data-tab-panel]'));
    }

    /** Zeigt die kompakte Live-Fehlerübersicht oberhalb des Formulars. */
    showValidationSummary() {
        this.validationSummary.hidden = false;
    }

    /** Entfernt die Live-Übersicht, sobald der aktuelle Schritt vollständig ist. */
    hideValidationSummary() {
        this.validationSummary.hidden = true;
    }

    /** Fokussiert den ersten sichtbaren Fehler im betroffenen Tab. */
    focusFirstInvalidField(index) {
        this.panels[index].querySelector('[aria-invalid="true"]')?.focus();
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
