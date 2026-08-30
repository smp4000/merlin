# Blueprint: Geschütztes Onboarding, Bankverzeichnis und IBAN-Assistent

Status: `Entwurf – öffentliche Bundesbank-CSV bestätigt, Gesamtfreigabe ausstehend`

## 1. Problem und Ziel

Nach der datenarmen öffentlichen Registrierung soll der bestätigte Partner im geschützten
Onboarding die rechtliche Gesellschaft, Rechnungsanschrift, erste Tankstelle,
Ansprechpartner beziehungsweise Stationsleitung und optional eine Bankverbindung erfassen.

Der IBAN-Assistent soll für deutsche Konten:

- eine vorhandene IBAN normalisieren und mathematisch prüfen;
- Bankleitzahl, Bankname und BIC aus einem versionierten Bankverzeichnis ermitteln;
- aus Bankleitzahl und Kontonummer eine deutsche IBAN nach der Standardformel berechnen;
- niemals behaupten, dass ein mathematisch gültiges Konto tatsächlich existiert oder dem
  angegebenen Kontoinhaber gehört.

## 2. Abgrenzung

- Das öffentliche Registrierungsformular bleibt datenarm.
- Das Onboarding beginnt erst nach bestätigter E-Mail und Anmeldung.
- Merlin führt keine Überweisung, Lastschrift, Kontoinhaberprüfung oder Zahlung aus.
- Eine Bankverbindung ist kein Stationsstammdatum, sondern gehört zur rechtlichen
  Gesellschaft und kann später zweckbezogen einer Station zugeordnet werden.
- Aus der öffentlichen Bundesbank-Datei wird keine uneingeschränkt verbindliche
  Zahlungsverkehrsauskunft abgeleitet.

## 3. Nutzer, Rollen und Reichweite

### Partner-Panel

- `Tenant Owner`: Onboarding abschließen sowie Bankverbindungen anlegen und verwalten;
- `Tenant Administrator`: nur mit ausdrücklich zugewiesenen Finance-Rechten;
- `Stationsleitung`: im Onboarding als Kontakt beziehungsweise spätere Einladung, aber
  standardmäßig ohne Zugriff auf Bankverbindungen;
- eigene Partnerrollen: nur innerhalb des Tenants und ohne reservierte Plattformrechte.

Permissions:

- `legal_entity.bank_account.read_masked`
- `legal_entity.bank_account.create`
- `legal_entity.bank_account.update`
- `legal_entity.bank_account.deactivate`
- `legal_entity.bank_account.read_full`
- `legal_entity.bank_account.verify`

### Plattform-Panel

- `Platform Bank Directory Admin`: Quellen und Importe verwalten;
- `Platform Security Auditor`: Import- und Sicherheitsereignisse lesen;
- `Platform Super Admin`: besitzt die Berechtigung, erhält dadurch aber keinen
  routinemäßigen Zugriff auf Bankverbindungen eines Partners.

Permissions:

- `platform.bank_directory.read`
- `platform.bank_directory.source.manage`
- `platform.bank_directory.import.preview`
- `platform.bank_directory.import.activate`
- `platform.bank_directory.rollback`

## 4. Onboarding-Oberfläche

Das Partner-Onboarding verwendet auf Desktop Tabs und mobil einen Stepper:

1. `Partner`: Anzeigename, Partnerart, Sprache, Land und Zeitzone;
2. `Unternehmen`: Firmierung, Rechtsform und Hauptgesellschaft;
3. `Anschrift`: Geschäfts- und abweichende Rechnungsanschrift mit Kopierfunktion;
4. `Erste Tankstelle`: Stationsname, Brand, Anschrift, Region und Funktionskontakt;
5. `Stationsleitung`: Anrede, Name, geschäftliche Kontaktdaten und Auswahl
   `registrierende Person` oder `andere Person einladen`;
6. `Steuer & Register`: USt-ID, nationale Steuernummer und optionale Registerdaten;
7. `Bankverbindung`: Kontoinhaber, IBAN oder alternativ BLZ und Kontonummer;
8. `Prüfen`: vollständige, maskierte Zusammenfassung und bewusste Aktivierung.

Entwürfe bleiben fortsetzbar. Tabtitel zeigen Fehler, Warnungen und Vollständigkeit.
AGB-Vertragsannahme und Kenntnisnahme der Datenschutzhinweise bleiben getrennte,
versionierte Zustimmungen und werden nicht in einer Sammel-Checkbox vermischt.

## 5. IBAN-Assistent

### Eingabe einer vorhandenen IBAN

1. Leerzeichen und zulässige Schreibformatierung entfernen;
2. Land und erwartete Länge prüfen;
3. ISO-13616-Mod-97-Prüfung ausführen;
4. bei deutscher IBAN die enthaltene BLZ gegen die zum Stichtag aktive
   Bankverzeichnisversion auflösen;
5. Bankname und BIC als Hinweis anzeigen;
6. Nutzer bestätigt die Bankverbindung ausdrücklich vor dem Speichern.

Eine erfolgreiche Prüfung bedeutet nur `format_and_checksum_valid`, nicht
`account_exists` oder `owner_verified`.

### Berechnung aus BLZ und Kontonummer

- BLZ muss achtstellig und zum Stichtag aktiv sein;
- die Kontonummer wird links mit Nullen auf zehn Stellen ergänzt;
- aus Länderkennung, BLZ und Kontonummer wird die Prüfziffer nach Modulo 97 berechnet;
- Ergebnis zeigt die verwendete Bankverzeichnisversion und den ausdrücklichen Hinweis,
  dass institutsspezifische Sonderregeln nicht ausgewertet werden;
- der Partner muss das Ergebnis vor Verwendung anhand eines Kontoauszugs, einer Bankkarte
  oder einer Bestätigung seiner Bank kontrollieren;
- die rohe Kontonummer wird nach Abschluss nicht separat benötigt und daher nicht
  dauerhaft gespeichert, sofern kein später freigegebener Fachzweck dies verlangt.

## 6. Bundesbank-Quelle und Super-Admin-Seite

Initiale öffentliche CSV-Quelle:

`https://www.bundesbank.de/resource/blob/926192/b27b518a016ea7ca7af321eb7289fcf4/472B63F073F071307366337C94F8C870/blz-aktuell-csv-data.csv`

Die öffentliche Datei enthält Bankleitzahl, Merkmal, Bezeichnung, PLZ, Ort,
Kurzbezeichnung, PAN, BIC, Prüfzifferberechnungsmethode, Datensatznummer,
Änderungskennzeichen, Löschhinweis und Nachfolge-BLZ. Genau diese öffentlichen Bankdaten
werden importiert. Die nicht enthaltenen institutsspezifischen IBAN-Regeln sind bewusst
nicht Bestandteil von Merlin.

Unter `Super-Admin → Systemkataloge → Bankverzeichnis` entstehen:

### Tab `Quelle`

- Anzeigename und Herausgeber;
- Download-URL;
- Quellentyp `public_csv`;
- erlaubter Host und erwartetes Format;
- Aktivkennzeichen und geplanter Prüfzeitpunkt;
- nur berechtigte Plattformrolle darf ändern;
- jede Änderung benötigt Grund, Step-up-MFA und Audit.

### Tab `Versionen`

- Version, Quell-URL, SHA-256, Abruf- und Importzeitpunkt;
- Gültig-von und Gültig-bis;
- Anzahl Datensätze sowie neue, geänderte und gelöschte Einträge;
- Importstatus `downloaded`, `validated`, `previewed`, `scheduled`, `active`, `rejected`
  oder `rolled_back`;
- Vorschau des Deltas ohne personenbezogene Daten.

### Tab `Import`

- `Jetzt prüfen` lädt nur herunter und validiert;
- `Änderungen ansehen` zeigt Zugänge, Änderungen, Löschungen und Nachfolge-BLZ;
- `Aktivieren` setzt eine validierte Version sofort oder zum Gültigkeitsdatum aktiv;
- fehlerhafte Versionen ersetzen niemals die letzte gültige Version;
- manueller Datei-Upload ist ein auditiertes Fallback, nicht der Normalweg.

### Tab `Überwachung`

- letzter erfolgreicher Abruf und Import;
- nächste planmäßige Prüfung;
- Warnungen bei veralteter Quelle, Formatänderung, unerwartetem Größenunterschied,
  ungültiger Signatur beziehungsweise Prüfsumme oder abgelaufenem Gültigkeitszeitraum;
- Benachrichtigung an Plattformadministration bei Fehlern.

## 7. Aktualisierungsstrategie

- Merlin prüft die Quelle regelmäßig; empfohlen ist wöchentlich und zusätzlich vor den
  quartalsweisen Bundesbank-Gültigkeitsterminen.
- Ein Abruf aktiviert Daten niemals ungeprüft.
- Parser- und Schemafehler, leere Dateien, unerwartete Massendifferenzen oder ungültige
  Datensätze stoppen den Import.
- Neue Versionen werden vollständig und unveränderlich gespeichert; aktive Datensätze
  werden über den Gültigkeitszeitraum ausgewählt.
- Ein künftiger Datenbestand darf vorab importiert, aber erst ab `valid_from` verwendet
  werden.
- Rollback aktiviert eine frühere validierte Version und wird begründet auditiert.

Die URL ist im Super-Admin änderbar, aber nicht beliebig: nur HTTPS, ausschließlich
freigegebene Bundesbank-Hosts und erwartete Pfade, begrenzte Dateigröße, kurze Timeouts,
Redirect-Prüfung und blockierte private beziehungsweise lokale IP-Ziele verhindern SSRF.

## 8. Datenmodell

| Entität | Zentrale Felder |
|---|---|
| `BankDirectorySource` | `name`, `provider`, `source_type`, `url`, `allowed_host`, `format`, `is_active` |
| `BankDirectoryVersion` | Quelle, Version, SHA-256, Abrufzeit, Gültigkeit, Status, Statistiken, Aktivierungsdaten |
| `BankDirectoryEntry` | Version, BLZ, Merkmal, Namen, Ort, PAN, BIC, Kontoprüfmethode, Änderungskennzeichen, Löschhinweis und Nachfolge-BLZ |
| `BankDirectoryImport` | Quelle, Start/Ende, Status, Fehlercode, sichere Metriken, Akteur/Job |
| `LegalEntityBankAccount` | unveränderliche `tenant_id`, Gesellschaft, Kontoinhaber, verschlüsselte IBAN, maskierte Anzeige, Fingerprint, Status, Prüfstatus und Verzeichnisversion |

Bankverzeichnisdaten sind globale Plattformkataloge und tragen keine `tenant_id`.
Bankverbindungen sind streng mandantenbezogen und tragen immer eine unveränderliche
`tenant_id`.

## 9. Sicherheit, Datenschutz und Audit

- IBAN wird verschlüsselt gespeichert und standardmäßig maskiert angezeigt;
- Suche, Logs, Exceptions, Queue-Payloads und Audit enthalten keine vollständige IBAN,
  Kontonummer oder Zugangsdaten;
- ein nicht umkehrbarer, kontextgebundener Fingerprint unterstützt Dublettenprüfungen
  ausschließlich innerhalb desselben Tenants;
- Vollanzeige benötigt Hochrisikorecht, Step-up-MFA, Zweck und Audit;
- Plattformadministratoren sehen Bankverzeichnisdaten, aber keine Partnerkonten;
- die Bankverbindung wird nur für dokumentierte Zwecke und nach einer noch juristisch zu
  bestätigenden Aufbewahrungsregel gespeichert;
- Löschung beziehungsweise Sperrung berücksichtigt Buchungs- und Nachweispflichten;
- DSFA- und Rechtsgrundlagenprüfung werden vor Go-live aktualisiert.

## 10. Fehler- und Missbrauchsfälle

- manipulierte Quelle, DNS-/Redirect-Manipulation oder Zugriff auf interne Ziele;
- CSV-Formelwerte, unerwartete Kodierung, Spalten- oder Formatänderung;
- gelöschte BLZ wird ohne Nachfolgehinweis verwendet;
- zukünftige Version wird zu früh aktiviert;
- Nutzer verlässt sich trotz des sichtbaren Hinweises ungeprüft auf die Standardberechnung;
- Nutzer interpretiert Prüfsummengültigkeit als Bestätigung der Kontoinhaberschaft;
- fremde Tenant-ID, Gesellschaft oder Bankverbindung wird angesprochen;
- vollständige IBAN erscheint in Audit, Export oder Benachrichtigung;
- parallele Importe aktivieren widersprüchliche Versionen.

Diese Fälle müssen ohne unkontrollierte Seiteneffekte enden und ein sicheres technisches
Ereignis erzeugen.

## 11. Tests und Akzeptanzkriterien

- gültige und ungültige IBAN-Prüfsummen werden korrekt erkannt;
- Formatierung mit Leerzeichen wird normalisiert, unzulässige Zeichen werden abgewiesen;
- Standardregel liefert für veröffentlichte Testvektoren die erwartete IBAN;
- jede berechnete IBAN wird zusätzlich mit Modulo 97 geprüft und mit dem sichtbaren
  Hinweis zur möglichen institutsindividuellen Abweichung ausgegeben;
- gelöschte und zukünftige BLZ werden stichtagsbezogen korrekt behandelt;
- Import erkennt neue, geänderte und gelöschte Datensätze sowie Nachfolge-BLZ;
- fehlerhafter Import lässt die aktive Version unverändert;
- zwei parallele Importe können nicht gleichzeitig aktivieren;
- nur berechtigte Plattformrollen ändern Quelle oder aktivieren Versionen;
- Cross-Tenant-Tests verhindern Lesen, Suchen, Dublettenhinweise und Änderungen fremder
  Bankverbindungen;
- vollständige IBAN erscheint weder in Logs noch im Audit;
- Tabs und Fehler sind auf Desktop, Mobilgerät, Tastatur und Screenreader bedienbar;
- Onboarding kann als Entwurf fortgesetzt werden und aktiviert keine unvollständige
  Gesellschaft oder Station.

## 12. Rollout und Monitoring

1. öffentliche Bundesbank-CSV importieren und Bank-/BLZ-Suche aktivieren;
2. IBAN-Prüfung für bereits eingegebene IBAN bereitstellen;
3. Standardberechnung aus BLZ und Kontonummer mit dauerhaft sichtbarem Hinweis aktivieren;
4. Pilot mit eigenen Konten ausschließlich gegen bekannte Bankunterlagen prüfen;
5. Fehlerquote, Importalter und Rollbacks überwachen.

## 13. Bestätigte Produktentscheidung und Einschränkung

Merlin importiert ausschließlich die öffentliche Bundesbank-CSV mit den Bankdaten. Ein
NExt-Zugang, die erweiterte Bankleitzahlendatei und institutsspezifische IBAN-Regeln sind
nicht vorgesehen.

Der IBAN-Rechner verwendet deshalb ausschließlich die deutsche Standardbildung aus BLZ,
zehnstelliger Kontonummer und Modulo-97-Prüfziffer. Er ist eine Eingabehilfe. Er bestätigt
weder die Existenz des Kontos noch die Kontoinhaberschaft und berücksichtigt keine
institutsindividuellen Umrechnungsregeln. Dieser Hinweis erscheint unmittelbar am Ergebnis
und in der Bestätigung vor dem Speichern.
