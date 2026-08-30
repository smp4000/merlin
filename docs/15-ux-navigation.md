# UX-, Navigation- und Formularplan

## Öffentliche Oberfläche

- Landingpage
- Registrierung
- E-Mail-Bestätigung
- Trial-/Onboarding-Einstieg

## Platform Panel

- Dashboard
- Mandanten und Trialstatus
- Entitlements/Module
- Brands, Rechtsformen und zentrale Kataloge
- Supportzugriffe
- Security-/Supportaudit

Das Panel zeigt standardmäßig keine operativen Mandantendaten. Ein aktiver Supportmodus
ist optisch eindeutig und zeitlich sichtbar.

## Partner Panel

Hauptnavigation:

- Dashboard
- Unternehmen
- Tankstellen
- Mitarbeiter
- Rollen
- Geräte
- Zeiterfassung
- Exporte
- Audit
- Einstellungen

Das Dashboard zeigt Onboardingfortschritt, offene Einladungen/Freigaben,
Gerätesynchronisation, Zeitkonflikte und Trialstatus.

Nach der Anmeldung erscheint bei mehreren erlaubten Stationen zunächst eine klare
Stationsauswahl mit Name, Ort, Stationsnummer und optionaler Rolle. Erst danach werden
operative Navigation, Dashboardwerte und stationsbezogene Aktionen geladen. Der aktive
Stationsname bleibt im Kopfbereich sichtbar und kann über einen zentralen Schalter
gewechselt werden. Bei genau einer erlaubten Station wird sie automatisch gewählt.

Mandantenweite Einstellungen und ausdrücklich erlaubte Gesamtauswertungen bleiben über
einen getrennten Bereich erreichbar. `Alle Stationen` ist nur ein lesender Filter und
kann niemals zum Anlegen oder Ändern eines stationsbezogenen Datensatzes verwendet werden.

## Partner-Settings

Keine monolithische Sammelseite. Der Menüpunkt führt zu klaren Unterseiten:

- Allgemein und Standardwerte
- Legal Entities
- Rollen und Delegationsgrenzen
- Anmeldung, MFA/Passkeys und Recovery
- Module/Entitlements
- Mitarbeiter-Onboarding
- Zeit-, Pausen- und Korrekturregeln
- Geräte-/Offline-Richtlinie
- Benachrichtigungen
- Sprache und regionale Darstellung
- Erscheinungsbild und Farbschema
- Payroll-Profile und bestätigte Empfänger
- Datenschutz, Aufbewahrung und Audit
- Integrationen und Secret-Referenzen

Jede Unterseite ist nur mit passender Permission sichtbar.

Die persönliche Sprachauswahl liegt im Benutzerprofil und übersteuert den
Mandantenstandard nur für diesen Benutzer. Der Partner legt unter `Einstellungen` die
Standardsprache sowie erlaubte Sprachen fest. Ein Sprachwechsel darf keine Eingaben,
Filter, aktive Station oder den Tenant-Kontext verlieren.

Unter `Erscheinungsbild und Farbschema` wählt ein berechtigter Partner aus geprüften
Merlin-Farbschemata. Eine Live-Vorschau zeigt Navigation, Aktionsschaltflächen,
Formularfelder und Fokuszustände. Die Auswahl gilt nach dem Speichern mandantenweit,
wird zeitlich auditiert und kann auf den Merlin-Standard zurückgesetzt werden. Ein
Schemawechsel darf weder Sprache noch aktive Station, Filter oder ungespeicherte
Formulareingaben verändern. Systemfarben für Erfolg, Warnung, Fehler, Information und
Offlinezustand bleiben unverändert.

Die Anmeldeseite zeigt MFA-Einrichtung und Wiederherstellung nur, wenn sie für die Rolle
verfügbar oder verpflichtend ist. Die Partner-Einstellung bietet `deaktiviert`, `optional`
und `verpflichtend`; unveränderliche Step-up-Vorgaben für Support, Break-glass und
sensible Exporte werden transparent angezeigt, können dort aber nicht abgeschaltet werden.

## Tab-Formulare

### Station

`Allgemein` · `Adresse` · `Öffnungszeiten` · `Shop` · `Karten` · `Kennungen` · `Dokumente`

### Mitarbeiter

`Person` · `Beschäftigung` · `Schutzprofil` · `Stationen` · `Rollen` · `Zugang` · `Freigabe`

### Regeln/Settings

Unterseiten verwenden eigene fachliche Tabs, nicht ein einziges riesiges Formular.

Tab-Regeln:

- Fehler-, Warnungs- und Vollständigkeitsstatus im Tabtitel
- Entwurf jederzeit speichern
- Fehlerübersicht verlinkt direkt in den betroffenen Tab
- kritische Änderungen zeigen Vorher/Nachher und benötigen Bestätigung
- Zustand bleibt beim Tabwechsel erhalten
- mobil werden Tabs zu Stepper oder Akkordeon

## Mitarbeiter-PWA

- bewusste Betriebsauswahl, wenn mehrere aktive TenantMemberships bestehen
- anschließende bewusste Stationsauswahl bei mehreren aktuell freigegebenen Zuordnungen
- Onboarding-/Einladungsstatus
- eigenes Profil und eigene Stationen
- eigene Zeiten und Korrekturanträge
- Benachrichtigungen

Die Betriebsauswahl zeigt nur Namen der Betriebe, denen die angemeldete Identität bereits
angehört. Nach einem Wechsel werden Navigation, Daten und Berechtigungen vollständig neu
geladen; Informationen verschiedener Betriebe werden nie in einer gemeinsamen Ansicht
vermischt.

Die Stationsauswahl zeigt nur aktuell wirksame, freigegebene Zuordnungen. Gewählte
Station und Rolle sind während der gesamten Sitzung sichtbar. Bei einem Stationswechsel
werden Daten, Berechtigungen und Offlinezustand neu geladen. Eine laufende Zeitbuchung
kann nicht durch einen einfachen Kontextwechsel einer anderen Station zugeordnet werden.

## MDE-PWA

- fest sichtbare, durch die Geräteregistrierung vorgegebene Station ohne freie Auswahl
- großer Scan-/Loginbereich für Personalnummer, QR und NFC plus PIN
- erwartete nächste Zeitaktion
- persönliches, erst nach Anmeldung sichtbares Postfach für Pflichtnachrichten und
  Korrekturentscheidungen
- eindeutige Online-/Offline-/Noch-nicht-synchronisiert-Anzeige
- große, handschuhgeeignete Bedienelemente
- automatische kurze Abmeldung
- keine Restdaten des vorherigen Mitarbeiters
- beaufsichtigte Recovery per kurzlebigem Einmalcode ohne Kenntnis der endgültigen PIN
- offline keine Korrekturen, Exporte oder Administration

## Merlin-Design

Eigenes Filament-Theme mit Design-Tokens für Farbe, Typografie, Abstände, Radien,
Schatten, Icons und Status. Keine kritische Abhängigkeit von einem Theme-Plugin.
Barrierefreiheit, Tastaturbedienung, Kontrast, Lade-/Fehler-/Empty States und responsive
Darstellung sind Teil der Abnahme, nicht spätere Politur.

Die mandantenbezogene Partnerfarbe überschreibt ausschließlich dafür freigegebene
Akzent-Tokens. Struktur, Typografie, Abstände, Statussemantik und Mindestkontraste bleiben
Teil des zentral gepflegten Merlin-Designsystems.
