# Stationsstammdaten

Der Such-, Übernahme- und Dublettenprozess für neue Standorte ist im
[Blueprint Tankstellenverwaltung mit Standortsuche](20-tankstellenverwaltung-suche-blueprint.md)
beschrieben. Eine externe Suche bleibt eine Eingabehilfe; bestätigte Stammdaten werden
nicht automatisch durch Anbieterdaten überschrieben.

Dieses Konzept übernimmt ausschließlich Feldstrukturen, keine realen Produktionswerte.
Nach bestätigter E-Mail startet der Trial automatisch; der Partner legt seine Station
selbst an. Die Station beginnt als Entwurf und wird nach Pflichtfeldprüfung aktiviert.

## Feldgruppen

### Allgemein

Pflichtfelder:

- systemseitige Stations-ID, Name und Kurzname
- Brand aus zentralem Katalog
- rechtlicher Betreiber aus dem aktiven Mandanten
- Status, Land, Bundesland/Region, Zeitzone und Standardsprache

Optionale bzw. brandabhängige Felder:

- partnerinterne Stationsnummer, Distrikt und Bezirk
- Eigentumsmodell, Vertriebskanal und Location-Typ
- Eröffnungs-, Übernahme- und Schließungsdatum
- Lkw-/Standortklassifikation und weitere zentral definierte Katalogwerte

Brand, Eigentumsmodell und Vertriebskanal sind getrennte Konzepte. Globale und
brandgebundene Kataloge werden zentral gepflegt; Partner wählen Werte aus und pflegen
nur ihre stationsbezogenen Angaben. Der versionierte Startbestand und seine
Seeder-Regeln sind im [DACH-Markenkatalog](17-dach-markenkatalog-seeder.md) festgelegt.

### Adresse und Kontakt

Strukturierte Straße, Hausnummer, Zusatz, PLZ, Ort, Ortsteil, Land/Bundesland,
Stations-Telefon, Funktions-E-Mail, Website sowie optional eine abweichende
Rechnungsadresse. Personenbezogene Ansprechpartner und direkte Telefonnummern sind
vertraulich; öffentliche Anzeige erfordert eine ausdrückliche Freigabe.

### Öffnungszeiten

Öffnungszeiten führen Wochentag, mehrere Zeitfenster, 24-Stunden-Kennzeichen,
Gültigkeitszeitraum, Sonderöffnung und temporäre Schließung. Shop und andere Bereiche
können eigene Zeiten besitzen. Überlappungen werden verhindert, Betrieb über Mitternacht
ist explizit möglich, vergangene Pläne bleiben versioniert.

### Shop und Angebote

- Shop vorhanden, Name, Fläche, Typ/Klasse und Sortimentsstufe
- Eröffnungs-/Schließungsdatum und eigener Zeitplan
- kontrollierte Leistungsmerkmale wie Backshop, Bistro oder Waschanlage
- brandgebundene Shop-, Preisregions- und Sortimentsklassifikationen

### Kartenakzeptanz

Zeitlich gültige Zuordnung zentraler Karten-/Zahlungsarten mit Einsatzbereich, optionalem
Acquirer und nicht geheimen Vertragsreferenzen. Terminalschlüssel, PINs und API-Secrets
sind ausgeschlossen.

### Typisierte Partner-/Abrechnungskennungen

Ein generisches Kennungsobjekt führt Typ, Wert, ausstellende Organisation, Gültigkeit,
Geltungsbereich, Vertraulichkeitsklasse und Prüfstatus. Typen umfassen insbesondere:

- Kraftstoff-/Shop-/Stationsnummern
- Partner-, Betreiber-, Kunden-, Debitoren- und Kreditorenreferenzen
- Bill-To-, TFS- und Mehrfachbetreiberreferenzen
- GLN und externe Systemreferenzen

GLN wird als 13-stellige Kennung mit Prüfziffer validiert. Eindeutigkeit wird je Typ
global, brandbezogen, ausstellerbezogen oder mandantenbezogen definiert.

### Steuer- und Behördenkennungen

USt-ID, Steuernummer/TIN, Finanzamt, Betriebs-, EO- und Facility-Kennungen werden nur
bei dokumentiertem Zweck erfasst. Steuerkennungen liegen möglichst an der Legal Entity
und nur bei echtem Stationsbezug zusätzlich an der Station. Formatprüfung bestätigt
nicht automatisch die fachliche Gültigkeit.

## Datenklassifikation

| Klasse | Beispiele | Schutz |
|---|---|---|
| Öffentlich nach Freigabe | Stationsname, Anschrift, allgemeine Öffnungszeiten, Funktionskontakt | vor Veröffentlichung intern |
| Intern | Status, interne Bezeichnung, Betriebszeiten, Klassifikationen | berechtigte Partnerrollen |
| Vertraulich | Kontaktperson, GLN, Partner-/Kunden-/Bill-To-/TFS-Nummern | maskiert, zweckgebundene Rollen |
| Streng vertraulich | Steuer/TIN, mögliche Konto-/Finance-Daten | getrennte Finance-Rechte, Feldverschlüsselung |
| Secret | Passwörter, EO-Code falls Authentisierungsmerkmal, API-Schlüssel | niemals normales Stammdatenfeld |

Der EO-Code wird bis zur fachlichen Klärung als Secret behandelt. Muss ein Fremdsystem-
Credential gespeichert werden, liegt es ausschließlich in einem Secrets-Manager; der
Stammdatensatz enthält nur eine Referenz. Der Wert ist nach Eingabe nicht rücklesbar und
wird nicht exportiert oder geloggt.

## Brandgebundene Felder

Der zentrale Brand-Katalog kann versionierte Zusatzschemata bereitstellen. Für Aral
können dadurch beispielsweise Distrikt/Bezirk, Eigentums-/Vertriebskanal,
Shopklassifikation sowie Kraftstoff-/Shop-/Partnerreferenzen eingeblendet werden.

- Schema, Datentyp, Pflichtstatus und Katalog stammen von der Plattform.
- Partner pflegen nur ihre Werte.
- Brandwechsel bewahrt historische Werte, übernimmt sie aber nicht ungeprüft.
- Neue Pflichtfelder benötigen Gültigkeitsdatum und Migrationsregel.
- Partnerfelder dürfen nicht als offizielles Brandfeld erscheinen.

## Anlage und Versionierung

```text
Entwurf → optional zur Prüfung → aktiv → ersetzt / geschlossen
```

1. Trial startet einmalig nach E-Mail-Bestätigung.
2. Partner legt Legal Entity an und wählt ein zentrales Brand.
3. Allgemein, Adresse, Öffnungszeiten und Shop werden erfasst.
4. Brandabhängige Felder erscheinen dynamisch.
5. Kennungen und optionale Nachweise werden ergänzt.
6. Validierung und Duplikatwarnung prüfen Adresse, GLN und externe Nummern.
7. Partner bestätigt die Zusammenfassung und aktiviert die Station.

Duplikate werden nur vorgeschlagen, nie automatisch zusammengeführt. Änderungen führen
Gültigkeit, Quelle, Grund, Akteur und gegebenenfalls Freigeber. Kritische Änderungen an
Betreiber, Brand, GLN, Steuer-/Behördenkennungen, Eigentumsmodell oder Schließung können
einen Prüfworkflow erfordern.

## Anhänge

Optional sind Betreiber-/Pachtunterlagen, Erlaubnisse, Steuer-/Registrierungsnachweise,
Brandnachweise, Lagepläne oder Fotos. Dateien erhalten Kategorie, Gültigkeit,
Vertraulichkeit und Löschregel; Uploads werden auf Typ, Größe und Schadsoftware geprüft.
Keine Secrets, PIN-Listen oder Passwörter in Anhängen.

## Rechte und Audit

Granulare Rechte trennen allgemeines Lesen/Ändern, Kennungen, Steuerdaten, Anhänge,
Freigabe und Export. Partnerrollen können zentrale Grenzen nicht umgehen. Super-Admin
pflegt Brands und Kataloge, erhält aber keinen routinemäßigen Zugriff auf vertrauliche
Partnerwerte. JIT-Zugriff erfordert Step-up-MFA, Zweck, Zeitlimit und Audit.

Audit speichert Feldname und Änderungsvorgang, aber keine vollständigen Steuer-, Konto-
oder Secret-Werte. Standardexporte enthalten keine streng vertraulichen Kennungen.
Echte Werte erscheinen nie in Test-, Demo-, Entwicklungs- oder Supportsystemen.

## MVP-Akzeptanzkriterien

- bestätigter Partner kann eine Station speichern, fortsetzen und selbst aktivieren;
- Brand und zentrale Kataloge sind nur auswählbar, nicht partnerseitig veränderbar;
- Station, Legal Entity und alle Daten bleiben im aktiven Mandanten;
- 24/7, mehrere Tagesfenster, Sondertage und Mitternachtsbetrieb funktionieren;
- Kennungen sind typisiert, gültigkeitsbezogen, maskiert und korrekt eindeutig;
- Aral-Zusatzfelder erscheinen nur bei entsprechendem Brand;
- Secrets können nicht in normale Felder oder Standardexporte gelangen;
- kritische Änderungen und Downloads vertraulicher Dokumente sind auditiert;
- historische Daten werden deaktiviert/versioniert statt spurlos gelöscht.
