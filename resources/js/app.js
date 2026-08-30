import './onboarding';

/**
 * Steuert die einwilligungsabhängigen Datenschutz-Einstellungen der öffentlichen Seiten.
 *
 * Die Entscheidung wird ausschließlich im Browser gespeichert. Sie aktiviert selbst
 * keine Drittanbieter: Künftige Integrationen müssen das Ereignis
 * `merlin:consent-changed` auswerten und dürfen erst danach externe Inhalte laden.
 */
class MerlinPrivacyConsent {
    /**
     * Verbindet das Dialogfenster mit der lokalen Speicherung und der Tastatursteuerung.
     *
     * @param {HTMLElement} root Wurzelelement des Datenschutzdialogs.
     */
    constructor(root) {
        this.root = root;
        this.dialog = root.querySelector('[role="dialog"]');
        this.analytics = root.querySelector('[data-privacy-analytics]');
        this.externalMedia = root.querySelector('[data-privacy-external-media]');
        this.closeButton = root.querySelector('[data-privacy-close]');
        this.status = root.querySelector('[data-privacy-status]');
        this.storageKey = `merlin_privacy_consent_v${root.dataset.consentVersion}`;
        this.previousFocus = null;
        this.inertElements = [];
        this.hasDecision = false;

        this.bindEvents();
        this.restore();
    }

    /** Registriert bewusst nur lokale Ereignisse; externe Dienste werden hier nie geladen. */
    bindEvents() {
        document.querySelectorAll('[data-privacy-open]').forEach((button) => {
            button.addEventListener('click', () => this.open(true));
        });

        this.root.querySelector('[data-privacy-necessary]').addEventListener('click', () => {
            this.analytics.checked = false;
            this.externalMedia.checked = false;
            this.save();
        });
        this.root.querySelector('[data-privacy-save]').addEventListener('click', () => this.save());
        this.root.querySelector('[data-privacy-accept-all]').addEventListener('click', () => {
            this.analytics.checked = true;
            this.externalMedia.checked = true;
            this.save();
        });
        this.closeButton.addEventListener('click', () => this.close());
        this.root.addEventListener('keydown', (event) => this.handleKeyboard(event));
        window.addEventListener('storage', (event) => {
            if (event.key === this.storageKey) this.restore(false);
        });
    }

    /**
     * Stellt eine valide vorhandene Entscheidung wieder her oder fordert die Erstauswahl an.
     * Fehlerhafte lokale Werte werden verworfen, ohne die Seite zu blockieren.
     */
    restore(openWhenMissing = true) {
        let consent = null;

        try {
            consent = JSON.parse(window.localStorage.getItem(this.storageKey));
        } catch {
            window.localStorage.removeItem(this.storageKey);
        }

        if (consent?.version === Number(this.root.dataset.consentVersion)) {
            this.hasDecision = true;
            this.analytics.checked = consent.analytics === true;
            this.externalMedia.checked = consent.externalMedia === true;
            this.closeButton.hidden = false;
            this.publish(consent);
            return;
        }

        if (openWhenMissing) this.open(false);
    }

    /** Öffnet den Dialog; bei der ersten Auswahl ist ein folgenloses Schließen ausgeschlossen. */
    open(isReopen) {
        this.previousFocus = document.activeElement;
        this.closeButton.hidden = !(isReopen || this.hasDecision);
        this.root.hidden = false;
        document.body.classList.add('has-privacy-dialog');
        this.setBackgroundInert(true);
        window.requestAnimationFrame(() => this.dialog.focus());
    }

    /** Schließt einen erneut geöffneten Dialog und stellt den vorherigen Fokus wieder her. */
    close() {
        if (!this.hasDecision) return;

        this.root.hidden = true;
        document.body.classList.remove('has-privacy-dialog');
        this.setBackgroundInert(false);
        this.previousFocus?.focus?.();
    }

    /** Speichert nur Zweckkategorien, Version und Zeitpunkt – niemals personenbezogene Angaben. */
    save() {
        const consent = {
            version: Number(this.root.dataset.consentVersion),
            necessary: true,
            analytics: this.analytics.checked,
            externalMedia: this.externalMedia.checked,
            decidedAt: new Date().toISOString(),
        };

        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(consent));
        } catch {
            // Bei gesperrtem Browserspeicher gilt die Auswahl nur für die aktuelle Seite.
            // Merlin lädt auch in diesem Fall keine optionalen Anbieter ohne das lokale Ereignis.
        }
        this.hasDecision = true;
        this.status.textContent = this.root.dataset.savedMessage;
        this.publish(consent);
        this.closeButton.hidden = false;
        this.close();
    }

    /** Informiert ausschließlich lokale, später hinzukommende Integrationen über die Auswahl. */
    publish(consent) {
        window.dispatchEvent(new CustomEvent('merlin:consent-changed', { detail: consent }));
    }

    /**
     * Nimmt den Seiteninhalt während des modalen Zustands aus der Tastatur- und
     * Screenreader-Navigation. Bereits anderweitig inaktive Elemente bleiben unangetastet.
     */
    setBackgroundInert(isInert) {
        if (isInert) {
            this.inertElements = [...document.body.children].filter(
                (element) => element !== this.root && !element.inert,
            );
            this.inertElements.forEach((element) => { element.inert = true; });
            return;
        }

        this.inertElements.forEach((element) => { element.inert = false; });
        this.inertElements = [];
    }

    /** Hält den Tastaturfokus im modalen Fenster und erlaubt Escape erst nach einer Entscheidung. */
    handleKeyboard(event) {
        if (event.key === 'Escape' && this.hasDecision) {
            this.close();
            return;
        }

        if (event.key !== 'Tab') return;

        const focusable = [...this.root.querySelectorAll('a[href], button:not([hidden]), input:not([disabled])')];
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
}

document.querySelectorAll('[data-privacy-consent]').forEach((root) => new MerlinPrivacyConsent(root));
