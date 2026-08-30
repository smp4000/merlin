<x-layouts.public title="Merlin | Betriebsplattform für Tankstellenpartner">
    <section class="merlin-hero">
        <div class="merlin-hero-copy">
            <span class="merlin-eyebrow">Für Tankstellenpartner und ihre Teams</span>
            <h1>Der Betrieb läuft.<br><em>Merlin hält ihn zusammen.</em></h1>
            <p>Eine mandantenfähige Plattform für Partner, Tankstellen und Mitarbeitende – vorbereitet für Personalplanung, MHD und Dokumentation.</p>
            <div class="merlin-hero-actions">
                <a href="{{ route('registration.create') }}" class="merlin-primary-button">14 Tage kostenlos starten <span aria-hidden="true">→</span></a>
                <a href="{{ route('filament.admin.auth.login') }}" class="merlin-secondary-button">Bereits registriert</a>
            </div>
            <p class="merlin-hero-note">Keine Zahlungsdaten. Nach 14 Tagen automatisch Nur-Lesen.</p>
        </div>

        <div class="merlin-hero-panel" aria-label="Vorschau der Merlin-Plattform">
            <div class="merlin-preview-topline"><span></span><span></span><span></span></div>
            <div class="merlin-preview-content">
                <div class="merlin-preview-sidebar"><strong>M</strong><i class="is-active"></i><i></i><i></i><i></i></div>
                <div class="merlin-preview-main">
                    <small>Guten Morgen</small><h2>Ihre Betriebe im Blick</h2>
                    <div class="merlin-preview-stats">
                        <article><span>2</span><small>Tankstellen</small></article>
                        <article><span>25</span><small>Mitarbeitende</small></article>
                        <article><span>4</span><small>Module</small></article>
                    </div>
                    <div class="merlin-preview-card"><span></span><span></span><span></span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="merlin-feature-strip" aria-label="Merlin Grundlagen">
        <article><span>01</span><h2>Sauber getrennt</h2><p>Jeder Partner arbeitet ausschließlich in seinem eigenen Mandanten.</p></article>
        <article><span>02</span><h2>Schrittweise modular</h2><p>Partner, Tankstellen und Mitarbeiter bilden die Basis für weitere Module.</p></article>
        <article><span>03</span><h2>Für den Alltag gebaut</h2><p>Backoffice, mobile Mitarbeiteransicht und Android-MDE greifen sicher ineinander.</p></article>
    </section>
</x-layouts.public>
