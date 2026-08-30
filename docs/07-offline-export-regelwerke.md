# Architekturentscheidung: Offline-MDE, Regelwerke und Payroll-Exporte

## Status

`Restrisiko fachlich akzeptiert – technische Kontrollen vor Pilot nachzuweisen`

## Offline-Anmeldung

Eine Offline-Anmeldung ist nur für Mitarbeitende möglich, deren Berechtigung vorher auf
genau dieses registrierte Stationsgerät synchronisiert wurde. Erstes Onboarding, neue
Zuordnungen und sofortige zentrale Widerrufe sind ohne Netz technisch nicht möglich.

Das MDE erhält regelmäßig ein signiertes, versioniertes und verschlüsseltes Paket mit:

- pseudonymer Mitarbeiter-ID;
- Mandant, Station und Gerätebindung;
- minimalem Offline-Recht `time.clock`;
- erlaubten Identifikatoren und lokalem PIN-Verifier;
- Credential-/Widerrufsversion, Regelversion und Ablaufzeit.

QR/NFC enthalten nur zufällige, widerrufbare Kennungen. PINs werden nicht gespeichert;
ein speicherharter Verifier wird mit gerätegebundenem Schlüssel im Android Keystore,
möglichst StrongBox, geschützt. Offline sind ausschließlich Stempeln und Pausen erlaubt,
keine Korrekturen, Personaldaten oder Exporte.

Im Pilot ist Personalnummer+PIN das Standardverfahren und zugleich der Fallback bei
Scanner- oder NFC-Ausfall. QR+PIN und NFC+PIN können je registriertem Gerät optional
aktiviert werden. Alle drei Verfahren verwenden denselben Berechtigungs- und
Sperrmechanismus. QR oder NFC ohne persönliche PIN sind ausgeschlossen. Ein verlorener
QR-/NFC-Identifikator kann unabhängig vom Mitarbeiterkonto widerrufen werden.

Die Pilot-Freigabedauer beträgt 48 Stunden und wird bei jeder Verbindung erneuert.
Danach ist keine neue Offline-Anmeldung möglich; ein manueller Notprozess greift. Der
Auftraggeber akzeptiert ausdrücklich das Restrisiko, dass ein bereits freigegebener
Mitarbeiter bis zum Ablauf offline stempeln kann, obwohl er zentral inzwischen gesperrt
wurde. Diese Akzeptanz ersetzt nicht den technischen Sicherheitsnachweis vor dem Pilot.

Beim späteren Sync werden Credential-/Widerrufsversion, lokale Ereigniszeit, monotone
Gerätezeit und Serverempfangszeit abgeglichen. Ereignisse, die nach einer inzwischen
bekannten Sperre entstanden sein können, werden nicht verworfen oder automatisch
freigegeben. Sie erhalten einen Konfliktstatus, erscheinen bei Partner und zuständiger
Stationsleitung und benötigen eine dokumentierte Entscheidung. Erkennung, Anzeige,
Entscheidung und Ergebnis werden auditiert.

Jedes Offline-Ereignis enthält Ereignis-ID, Gerätesquenz, vorherigen Hash,
Credential-Version, lokale Zeit, UTC-Offset, monotone Gerätezeit, Boot-Zähler und
Gerätesignatur. Replay, Uhrsprung, Hashlücke oder zwischenzeitlicher Widerruf erzeugen
einen Prüfhinweis; Rohereignisse werden niemals still gelöscht.

Pflichttests: 72 Stunden offline, Neustart, verstellte Uhr, doppelte Ereignisse,
verlorener Badge, wiederholte falsche PIN, Rollenwiderruf, Geräteverlust und späterer Sync.

## Organisation und Regelwerke

`Tenant` ist der Daten-/Vertragsraum. Darunter können mehrere `LegalEntity`-Datensätze
mit zentral gepflegten Rechtsformcodes liegen. Jedes Arbeitsverhältnis gehört genau
einer rechtlichen Einheit; gesellschaftsübergreifender Einsatz benötigt ein eigenes
Arbeitsverhältnis oder einen ausdrücklich modellierten Einsatz.

Stationen führen Bundesland und Zeitzone. Regeln werden versioniert und zeitlich gültig
zugewiesen:

1. gesetzliche Basis
2. Bundesland und Feiertagskalender
3. Tarifvertrag
4. Betriebsvereinbarung
5. Arbeitgeber-/Mandantenregel
6. Arbeitsvertrag/Beschäftigtengruppe
7. Schutzprofil, beispielsweise minderjährig oder Auszubildender

Diese Hierarchie ist keine einfache „letzte Einstellung gewinnt“-Logik. Konflikte werden
sichtbar und über ein fachlich/juristisch freigegebenes Regelprofil aufgelöst. Änderungen
verändern abgeschlossene Zeiträume nicht rückwirkend.

## Bezahlte Pausen und Alleinarbeit

Pausenzeit und Vergütungszeit werden getrennt modelliert. Eine Pause enthält Beginn,
Ende, geplant/tatsächlich, bezahlt/unbezahlt/teilweise sowie vollständig freigestellt,
bereitschaftsgebunden oder unterbrochen.

Bezahlung allein macht eine Unterbrechung nicht automatisch zur gesetzlichen Ruhepause.
Im Pilot müssen allein arbeitende Beschäftigte weiter Kunden bedienen und die Kasse
beobachten. Dafür wird keine vollständige Freistellung unterstellt. Das System
erfasst diese Unterbrechungen getrennt und weist auf notwendige Ablösung oder fachliche
Prüfung hin.

Pausenabzüge werden als versioniertes Arbeitgeber-Regelprofil konfiguriert: nur manuelle
Pausen, Warnung ohne Abzug oder automatischer Abzug mit Schwelle und Dauer. Ein
automatischer Abzug verändert niemals die unveränderlichen Zeitereignisse, sondern nur
den nachvollziehbaren berechneten Abrechnungsstand. Er trägt Regelversion und Grund, ist
für den Mitarbeiter sichtbar und kann über den Korrekturprozess angefochten werden.
Bereits freigegebene Perioden bleiben bei späteren Regeländerungen unverändert. Die
konkrete Konfiguration benötigt vor Einsatz eine arbeitsrechtliche und fachliche Prüfung.

## Payroll-Exportarchitektur

```text
unveränderliche Zeitereignisse
→ freigegebener kanonischer Abrechnungsstand
→ versioniertes Mapping/Exportprofil
→ CSV / PDF / DATEV / ADDISON / eurodata
```

Das kanonische Modell enthält nur abrechnungsrelevante Angaben wie Personalnummer,
Gesellschaft, Zeitraum, Zeitarten, bezahlte/unbezahlte Pausen, Kostenstelle/Station,
Korrekturen und Freigabestatus.

Jeder Herstelleradapter ist an Zielprodukt, Produktversion, Schnittstellenbeschreibung,
Übertragungsart und Profilversion gebunden. Ein Kompatibilitätsversprechen erfolgt erst
nach Dokumentationsprüfung und Abnahmetest mit Steuerberater/Lohnbüro. Bis dahin gilt:

- CSV: konfigurierbarer maschinenlesbarer Universalweg
- PDF: Kontroll- und Freigabenachweis, keine Importschnittstelle
- DATEV, ADDISON und eurodata: geplante Adapterziele

Exporte benötigen `payroll.export`, Step-up-MFA, explizite Reichweite und vollständiges
Audit. Optional gilt Vier-Augen-Freigabe. Dateien werden verschlüsselt, kurzzeitig
bereitgestellt und nicht unverschlüsselt per E-Mail versandt. CSV-Formelinjektion,
Zeichensatz-, Datums-/Zahlenformat und Fremdmandantendaten werden automatisiert geprüft.

Der Steuerberater erhält kein Benutzerkonto und keinen Zugriff auf das Portal. Der erste
Übertragungsweg ist E-Mail an eine vorab bestätigte Empfängeradresse. Personenbezogene
CSV-/PDF-Dateien werden nicht offen angehängt, sondern entweder als verschlüsseltes
Archiv mit Kennwortübermittlung über einen getrennten Kanal oder per S/MIME/PGP versandt.
Ein optionaler manueller Download bleibt für den Partner verfügbar. Spätere direkte
Übertragung folgt über einen separaten Kanaladapter pro Zielsystem, beispielsweise API
oder SFTP, mit Empfängerfreigabe, Schlüsselverwaltung, Zustellstatus und Wiederholschutz.

Vor jedem Versand zeigt das System Empfänger, Gesellschaft, Zeitraum, Mitarbeiterumfang,
Formate und Profilversion. Versand benötigt `payroll.export`, Step-up-MFA und optional
Vier-Augen-Freigabe. Änderung einer Empfängeradresse muss separat bestätigt werden.
Audit erfasst Ersteller, Freigeber, Empfänger, Dateihash, Verschlüsselungsverfahren,
Versandzeit und Zustellstatus, aber keine vollständigen Abrechnungsinhalte.
