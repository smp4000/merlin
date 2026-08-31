<x-layouts.onboarding title="Merlin – Partner einrichten">
    <div
        class="merlin-onboarding-page"
        data-onboarding
        data-iban-success="IBAN wurde berechnet. Bitte vergleichen Sie das Ergebnis mit Ihren Bankunterlagen."
        data-iban-bank-code-error="Bitte geben Sie eine achtstellige Bankleitzahl ein."
        data-iban-account-number-error="Bitte geben Sie eine Kontonummer mit höchstens zehn Ziffern ein."
        data-iban-calculation-error="Die IBAN konnte nicht sicher berechnet werden. Bitte prüfen Sie Ihre Eingaben."
        data-validation-required=":field ist erforderlich."
        data-validation-email="Bitte geben Sie für :field eine gültige E-Mail-Adresse ein."
        data-validation-max=":field darf höchstens :max Zeichen enthalten."
        data-validation-iban="Bitte geben Sie eine gültige deutsche IBAN ein."
        data-validation-bank-alternative="Bitte geben Sie eine IBAN oder Bankleitzahl und Kontonummer ein."
        data-validation-confirm="Bitte bestätigen Sie die Prüfung anhand Ihrer Bankunterlagen."
    >
        <header class="merlin-onboarding-heading">
            <span class="merlin-eyebrow">Sicherer Einrichtungsassistent</span>
            <h1>Richten Sie Ihren Betrieb ein.</h1>
            <p>Die Angaben werden geschützt in Ihrem Mandanten gespeichert. Sie können vor dem Abschluss zwischen den Bereichen wechseln.</p>
        </header>

        @if ($errors->any())
            <div class="merlin-error-summary" role="alert">
                <strong>Bitte prüfen Sie Ihre Angaben.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
            <script type="application/json" data-onboarding-server-errors>@json($errors->toArray())</script>
        @endif

        <div class="merlin-error-summary" role="alert" tabindex="-1" data-live-validation-summary hidden>
            <strong>Bitte vervollständigen Sie den markierten Bereich.</strong>
            <p>Die Eingaben bleiben erhalten. Der erste Fehler ist bereits fokussiert.</p>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" class="merlin-onboarding-card" data-onboarding-form novalidate>
            @csrf
            <nav class="merlin-onboarding-tabs" aria-label="Bereiche des Onboardings">
                @foreach (['Unternehmen', 'Rechnungsanschrift', 'Tankstelle', 'Stationsleitung', 'Bankverbindung', 'Prüfen'] as $index => $label)
                    <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-tab-button="{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        <span>{{ $index + 1 }}</span>{{ $label }}
                    </button>
                @endforeach
            </nav>

            <section class="merlin-onboarding-panel is-active" data-tab-panel="0">
                <div class="merlin-panel-heading"><span>Unternehmen</span><h2>Rechtlicher Betreiber</h2></div>
                <div class="merlin-field-grid">
                    <label>Firma / vollständige Firmierung<input name="legal_name" value="{{ old('legal_name', $context->tenant->display_name) }}" required maxlength="200"></label>
                    <label>Rechtsform<select name="legal_form" required>
                        @foreach ($legalForms as $legalForm)
                            <option value="{{ $legalForm->key }}" @selected(old('legal_form', $legalForms->first()?->key) === $legalForm->key)>{{ $legalForm->label($context->tenant->default_locale) }}</option>
                        @endforeach
                    </select></label>
                    <label>E-Mail für Rechnungen<input type="email" name="billing_email" value="{{ old('billing_email', auth()->user()->email) }}" required maxlength="254"></label>
                    <label>USt-IdNr. <small>Optional</small><input name="vat_id" value="{{ old('vat_id') }}" autocomplete="off" maxlength="40"></label>
                </div>
            </section>

            <section class="merlin-onboarding-panel" data-tab-panel="1">
                <div class="merlin-panel-heading"><span>Rechnungsanschrift</span><h2>Anschrift des Unternehmens</h2></div>
                <div class="merlin-field-grid">
                    <label>Straße<input name="billing_street" value="{{ old('billing_street') }}" required maxlength="160" data-billing="street"></label>
                    <label>Hausnummer<input name="billing_house_number" value="{{ old('billing_house_number') }}" required maxlength="30" data-billing="house_number"></label>
                    <label>Adresszusatz <small>Optional</small><input name="billing_address_addition" value="{{ old('billing_address_addition') }}" maxlength="120" data-billing="address_addition"></label>
                    <label>PLZ<input name="billing_postal_code" value="{{ old('billing_postal_code') }}" required maxlength="20" data-billing="postal_code"></label>
                    <label>Ort<input name="billing_city" value="{{ old('billing_city') }}" required maxlength="120" data-billing="city"></label>
                    <label>Bundesland / Region<input name="billing_region" value="{{ old('billing_region', 'Hessen') }}" required maxlength="120" data-billing="region"></label>
                    <label>Land<select name="billing_country_code" data-billing="country_code"><option value="DE">Deutschland</option><option value="AT">Österreich</option><option value="CH">Schweiz</option></select></label>
                </div>
            </section>

            <section class="merlin-onboarding-panel" data-tab-panel="2">
                <div class="merlin-panel-heading"><span>Erste Tankstelle</span><h2>Stationsdaten</h2><button type="button" class="merlin-copy-action" data-copy-billing>Rechnungsanschrift übernehmen</button></div>
                <div class="merlin-field-grid">
                    <label>Stationsname<input name="station_name" value="{{ old('station_name') }}" required maxlength="160"></label>
                    <label>Marke<select name="fuel_station_brand_id"><option value="">Bitte wählen</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}" @selected((string) old('fuel_station_brand_id') === (string) $brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
                    <label>Straße<input name="station_street" value="{{ old('station_street') }}" required maxlength="160" data-station="street"></label>
                    <label>Hausnummer<input name="station_house_number" value="{{ old('station_house_number') }}" required maxlength="30" data-station="house_number"></label>
                    <label>Adresszusatz <small>Optional</small><input name="station_address_addition" value="{{ old('station_address_addition') }}" maxlength="120" data-station="address_addition"></label>
                    <label>PLZ<input name="station_postal_code" value="{{ old('station_postal_code') }}" required maxlength="20" data-station="postal_code"></label>
                    <label>Ort<input name="station_city" value="{{ old('station_city') }}" required maxlength="120" data-station="city"></label>
                    <label>Bundesland / Region<input name="station_region" value="{{ old('station_region', 'Hessen') }}" required maxlength="120" data-station="region"></label>
                    <label>Land<select name="station_country_code" data-station="country_code"><option value="DE">Deutschland</option><option value="AT">Österreich</option><option value="CH">Schweiz</option></select></label>
                </div>
            </section>

            <section class="merlin-onboarding-panel" data-tab-panel="3">
                <div class="merlin-panel-heading"><span>Ansprechpartner</span><h2>Stationsleitung</h2></div>
                <div class="merlin-field-grid">
                    <label>Anrede<select name="manager_salutation"><option value="female">Frau</option><option value="male">Herr</option><option value="diverse">Divers</option><option value="none">Keine Angabe</option></select></label>
                    <label>Vorname<input name="manager_first_name" value="{{ old('manager_first_name') }}" required maxlength="80"></label>
                    <label>Nachname<input name="manager_last_name" value="{{ old('manager_last_name') }}" required maxlength="80"></label>
                    <label>E-Mail<input type="email" name="manager_email" value="{{ old('manager_email', auth()->user()->email) }}" required maxlength="254"></label>
                    <label>Telefon<input type="tel" name="manager_phone" value="{{ old('manager_phone') }}" required maxlength="40"></label>
                    <label>Fax <small>Optional</small><input type="tel" name="manager_fax" value="{{ old('manager_fax') }}" maxlength="40"></label>
                </div>
            </section>

            <section class="merlin-onboarding-panel" data-tab-panel="4">
                <div class="merlin-panel-heading"><span>Optional</span><h2>Bankverbindung</h2><p>Die IBAN wird verschlüsselt gespeichert und später nur maskiert angezeigt.</p></div>
                <label class="merlin-checkbox"><input type="checkbox" name="add_bank_account" value="1" @checked(old('add_bank_account')) data-bank-toggle><span>Bankverbindung jetzt hinterlegen</span></label>
                <div class="merlin-bank-fields" data-bank-fields hidden>
                    <label>Kontoinhaber<input name="account_holder" value="{{ old('account_holder') }}" autocomplete="organization" maxlength="200" data-bank-required></label>
                    <div class="merlin-bank-choice"><strong>Vorhandene oder berechnete IBAN</strong><label>IBAN<input name="iban" value="{{ old('iban') }}" placeholder="DE00 0000 0000 0000 0000 00" autocomplete="off" data-iban aria-describedby="iban-calculation-result"></label></div>
                    <div class="merlin-bank-divider"><span>oder berechnen</span></div>
                    <div class="merlin-field-grid">
                        <label>Bankleitzahl<input name="bank_code" value="{{ old('bank_code') }}" inputmode="numeric" maxlength="8" data-bank-code></label>
                        <label>Kontonummer<input name="account_number" value="{{ old('account_number') }}" inputmode="numeric" maxlength="10" autocomplete="off" data-account-number></label>
                    </div>
                    <div class="merlin-iban-calculation">
                        <button type="button" class="merlin-secondary-button" data-calculate-iban>IBAN berechnen</button>
                        <p id="iban-calculation-result" class="merlin-iban-result" role="status" aria-live="polite" data-iban-result></p>
                    </div>
                    <p class="merlin-bank-warning">Der Rechner ist eine Eingabehilfe. Das Ergebnis bestätigt weder Kontoexistenz noch Kontoinhaberschaft und muss mit Ihren Bankunterlagen verglichen werden.</p>
                    <label class="merlin-checkbox"><input type="checkbox" name="confirm_iban_result" value="1" @checked(old('confirm_iban_result')) data-bank-confirm><span>Ich werde die IBAN anhand meiner Bankunterlagen prüfen.</span></label>
                </div>
            </section>

            <section class="merlin-onboarding-panel" data-tab-panel="5">
                <div class="merlin-panel-heading"><span>Abschluss</span><h2>Einrichtung prüfen und speichern</h2><p>Nach dem Speichern wird Ihre erste Station aktiviert. Änderungen bleiben später in den jeweiligen Stammdaten möglich und werden nachvollziehbar protokolliert.</p></div>
                <div class="merlin-review-note"><strong>Mandant:</strong> {{ $context->tenant->display_name }}<br><strong>Testphase:</strong> läuft bis {{ $context->tenant->trial?->ends_at?->format('d.m.Y H:i') ?? '–' }}</div>
            </section>

            <footer class="merlin-onboarding-actions">
                <button type="button" class="merlin-secondary-button" data-previous disabled>Zurück</button>
                <button type="button" class="merlin-primary-button" data-next>Weiter</button>
                <button type="submit" class="merlin-primary-button" data-submit hidden>Einrichtung abschließen</button>
            </footer>
        </form>
    </div>

</x-layouts.onboarding>
