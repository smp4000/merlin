# Fachkonzept Zeiterfassung – Pilot

## Ziel und Grenze

Der Pilot für zwei Aral-Tankstellen und ca. 25 Mitarbeitende erfasst tatsächliche
Arbeitszeiten, Pausen, Stationswechsel, Korrekturen und Monatsfreigaben. Buchungen
erfolgen primär am registrierten Android-MDE. Die private PWA dient zunächst der
Einsicht, Korrekturanträgen und Benachrichtigungen; mobiles Stempeln wird erst nach
gesonderter Datenschutz- und Missbrauchsbewertung freigeschaltet.

Bestätigter Kontext: Beide Stationen gehören einem Einzelunternehmen in Hessen. Eine
Station ist 24/7 geöffnet. Beschäftigt werden Vollzeit-,
Teilzeit- und Minijobkräfte sowie Auszubildende und Minderjährige. Ein bestehender
digitaler Pausenprozess ist nicht vorhanden und wird daher im Pilot neu eingeführt.
Bezahlte Unterbrechungen und Alleinarbeit sind reale Pilotfälle. Da dabei weiterhin
Kunden bedient und die Kasse überwacht werden müssen, werden sie nicht automatisch als
gesetzlich wirksame Ruhepause behandelt.

Enthalten:

- Arbeitsbeginn/-ende und Pausenbeginn/-ende
- Mehrfachzuordnung und stationsbezogene Erfassung
- Schichten über Mitternacht
- Offlinebuchung auf registrierten Android-MDEs
- fehlende Buchungen, Konflikte und Regelwarnungen
- Korrekturantrag, Genehmigung/Ablehnung und lückenlose Historie
- Periodenabschluss und reproduzierbarer Standardexport
- versionierte CSV- und PDF-Exporte für den Steuerberater

Nicht enthalten:

- Schicht-/Personalplanung, Urlaub und vollständige Krankheitsverwaltung
- Lohn-, Zuschlags- oder Gehaltsberechnung
- Biometrie oder permanente Standortüberwachung
- direkte TMS5000-Integration vor Schnittstellenprüfung
- direkte DATEV-, ADDISON- oder eurodata-Übertragung vor Bestätigung des konkreten Importformats
- ein systemweit unveränderlicher automatischer Pausenabzug

## Pausen, Alleinarbeit und einstellbare Abzüge

Eine echte Ruhepause wird nur als solche erfasst, wenn der Mitarbeiter vollständig
abgelöst ist. Muss er weiterhin Kasse oder Kunden betreuen, wird die Zeit als bezahlte
Arbeitsbereitschaft beziehungsweise Unterbrechung geführt. Wird eine echte Pause durch
Arbeit unterbrochen, endet der laufende Pausenabschnitt; ein späterer Abschnitt wird neu
begonnen.

Der Partner konfiguriert im Menü `Einstellungen → Zeit und Pausen` ein versioniertes
Regelprofil. Je rechtlichem Arbeitgeber und optional Beschäftigtengruppe stehen bereit:

- `manual`: Nur tatsächlich gestempelte oder freigegeben nachgetragene Pausen zählen;
- `warning`: Fehlende oder zu kurze Pausen erzeugen einen Prüfhinweis, aber keinen Abzug;
- `automatic`: Nach konfigurierbarer Arbeitszeitschwelle wird eine konfigurierte Dauer
  vom berechneten Abrechnungsstand abgezogen.

Beide Pilot-Tankstellen starten verbindlich im Modus `warning`. Im Pilot entstehen daher
bei fehlenden oder zu kurzen Pausen nur Prüfhinweise; Arbeitszeit wird nicht automatisch
gekürzt. Ein späterer Wechsel auf `manual` oder `automatic` ist eine versionierte,
auditierte Partnerentscheidung und wirkt nur für zukünftige Zeiträume.

Ein automatischer Abzug erzeugt eine eigene, sichtbare Berechnungsposition mit Regelversion
und Grund. Er verändert keine `TimeEvent`-Rohbuchung und wird nicht rückwirkend auf bereits
freigegebene Perioden angewandt. Bereits als Arbeitsbereitschaft oder Unterbrechung mit
Kunden-/Kassenbetreuung gekennzeichnete Zeit wird nicht still als echte Ruhepause
umklassifiziert. Mitarbeiter werden über Abzüge informiert und können eine Korrektur
beantragen. Änderungen am Regelprofil benötigen `settings.time_rules.manage` und Audit.
Modus, Schwellen, Dauern, Zielgruppen und Ausnahmen müssen vor dem Piloten fachlich und
arbeitsrechtlich geprüft und freigegeben werden.

## Buchungsereignisse

- `ARBEITSBEGINN`
- `ARBEITSENDE`
- `PAUSE_BEGINN`
- `PAUSE_ENDE`
- `STATIONSWECHSEL_ABGANG`
- `STATIONSWECHSEL_ANKUNFT`

Ergänzend existieren unveränderlich protokollierte Nachbuchungen, Korrekturanträge,
Entscheidungen, Periodenfreigaben, Wiederöffnungen und Exporte. Rohbuchungen werden nie
überschrieben. Korrekturen erzeugen eine neue fachliche Version mit Vorher-/Nachher-Wert,
Grund, Antragsteller, Entscheider und Zeitpunkt.

## Rollen und Funktionstrennung

- Mitarbeiter: eigene Zeiten buchen/sehen und Korrekturen beantragen
- Stationsleitung: Zeitdaten und Anträge der berechtigten Station prüfen
- Zeitwirtschafts-Admin: Regeln, Perioden und stationenübergreifende Konflikte
- Korrekturprüfer: Korrekturen genehmigen/ablehnen
- Lohnexport-Berechtigter: freigegebene Versionen exportieren
- Read-only-Prüfer: zeitlich und sachlich begrenzte Einsicht

Partner können diese Rollen aus granularen Permissions zusammensetzen. Niemand darf
eigene Korrekturen genehmigen. Export, Wiederöffnung, manuelle Fremdbuchung und
Rechteänderung benötigen besondere Permissions und Audit.

Korrekturen dürfen vom Partner, von der zuständigen Stationsleitung oder von einer
zeitlich aktiven Vertretung mit `time.correction.review` entschieden werden. Partner
handeln innerhalb ihres Mandanten; Stationsleitungen und Vertretungen nur für Stationen
in ihrem wirksamen Scope. Antragsteller, betroffene Person und Ersteller einer manuellen
Änderung dürfen dieselbe Korrektur nicht genehmigen. Betrifft eine Korrektur mehrere
Stationen, muss der Entscheider für alle betroffenen Stationen berechtigt sein; andernfalls
wird der Vorgang stationsbezogen aufgeteilt oder an den Partner weitergeleitet.

## MDE-Workflow

1. Das verwaltete Gerät ist genau einer Tankstelle zugeordnet.
2. Mitarbeiter meldet sich wahlweise mit Personalnummer, QR oder NFC plus persönlicher PIN an.
3. Gerät zeigt Station und die fachlich erwartete nächste Aktion.
4. Buchung erhält Ereignis-ID, Mitarbeiter, Station, Quelle, Geräte- und Serverzeit.
5. Online wird sie sofort bestätigt; offline wird der lokale Status klar angezeigt.
6. Nach wenigen Sekunden endet die persönliche Sitzung ohne Restdaten.

QR oder NFC allein genügt nicht. Biometrie ist nicht vorgesehen.

Die Anmeldung verwendet einen gemeinsamen serverseitigen Authentifizierungskern mit
austauschbaren Identifikatoren. Personalnummer, QR und NFC identifizieren nur die Person;
die PIN bestätigt den Besitz des persönlichen Geheimnisses. Verfahren können je Gerät
aktiviert oder gesperrt werden. Für den Pilot wird ein Standardverfahren festgelegt,
damit Support, Schulung und Missbrauchserkennung beherrschbar bleiben.

Verbindliche Vorgabe für den Pilot:

- Standard: Personalnummer + PIN
- Komfort: QR + PIN und NFC + PIN
- Fallback bei Scanner-/NFC-Ausfall: Personalnummer + PIN
- QR/NFC enthalten nur eine zufällige, widerrufbare Kennung, keine Personalnummer/PIN
- Gerät und Person werden getrennt authentifiziert
- Rate Limits und Sperrzeiten gelten pro Person, Gerät, Station und Verbindung
- PINs werden nie im Klartext, auf dem Tag oder im lokalen Gerätespeicher abgelegt
- Verlust von QR-Code oder NFC-Medium sperrt den jeweiligen Identifikator; das
  Mitarbeiterkonto und andere gültige Anmeldewege bleiben separat verwaltbar

## Offline und Konflikte

Offlinebuchungen und eine frische Offline-Anmeldung sind im Pilot auf registrierten MDEs
Pflicht. Eine verschlüsselte,
begrenzte Queue speichert eindeutige Ereignisse und synchronisiert idempotent. Gerätezeit
und Serverempfangszeit bleiben getrennt sichtbar. Die Offline-Anmeldung verwendet ein
zeitlich begrenztes, signiertes und verschlüsseltes Berechtigungspaket für die eigene
Station. Das genaue Credential-, Widerrufs-, Ablauf- und Geräteschutzkonzept ist vor
Pilotfreigabe als Security-Entscheidung abzunehmen. Die Offline-Freigabe gilt im Pilot
48 Stunden und wird bei jeder Verbindung erneuert.

Der Auftraggeber akzeptiert für den Pilot ausdrücklich, dass eine zwischenzeitliche
zentrale Mitarbeiter-, Rollen- oder Gerätesperre ein vollständig offline betriebenes MDE
nicht sofort erreicht. Bis zum Ablauf des vorhandenen 48-Stunden-Pakets kann deshalb
weiter gestempelt werden. Beim Sync vergleicht das System Credential-/Widerrufsversion,
Ereigniszeit und Serverempfangszeit. Betroffene Ereignisse bleiben unverändert erhalten,
werden als Konflikt markiert und Partner beziehungsweise zuständiger Stationsleitung zur
Prüfung angezeigt. Nach Paketablauf ist keine neue Offline-Anmeldung möglich.

Folgende Fälle werden nicht automatisch gelöscht oder überschrieben, sondern als
Prüffall dargestellt:

- parallele Starts an zwei Stationen/Geräten
- doppelte Online-/Offlinebuchung
- Arbeitsende vor synchronisiertem Pausenende
- auffällige Änderung der Geräteuhr
- Stationswechsel, während beide Geräte offline sind
- lange offene Arbeitssitzung oder vergessenes Ausstempeln

## Stationswechsel

Ein Wechsel beendet den stationsbezogenen Abschnitt an Station A und beginnt einen
Abschnitt an Station B. Die Zwischenzeit wird nur nach einer vorher fachlich und
juristisch freigegebenen Regel als Fahrtzeit, Pause oder Unterbrechung klassifiziert.
Das System nimmt dies nicht selbst an. Gesamtarbeitszeit wird auf Ebene des rechtlichen
Arbeitgebers geprüft; Stationsleitungen sehen nur ihre berechtigten Ausschnitte.

## Freigabeprozess

```text
Korrektur: Entwurf → eingereicht → genehmigt / abgelehnt / Klärung
Periode:   offen → prüfbereit → freigegeben → exportiert
                                  └────────→ begründet wieder geöffnet
```

Vor Periodenfreigabe zeigt das System offene Sitzungen, fehlende Buchungen,
Überschneidungen und Regelwarnungen. Warnungen verändern Rohzeiten nicht. Ein Export
referenziert immer die konkrete freigegebene Version und bleibt über Prüfsumme und
Exportlauf reproduzierbar.

Jede Korrekturentscheidung enthält Vorher-/Nachher-Werte, Grund, Antragsteller,
Entscheider, Berechtigungsscope und Zeitpunkt. Die betroffene Person wird über die
Entscheidung benachrichtigt. Rohereignisse bleiben unverändert erhalten.

## Fachliche Datenobjekte

- `TimeEvent`: unveränderliches Rohereignis mit Person, Beschäftigung, Station,
  Ereignis-/Empfangszeit, Zeitzone, Quelle, Gerät und Idempotenzkennung
- `WorkSession` und `Break`: aus Rohereignissen gebildete Arbeits-/Pausenabschnitte
- `StationTransfer`: Abgang, Ankunft und freigegebene Klassifikation der Zwischenzeit
- `RuleFinding`: Warnung/Verstoß mit Regelversion und Prüfstatus
- `CorrectionRequest`, `CorrectionDecision`, `TimeVersion`
- `ClosingPeriod` und `ExportRun`
- `ExportProfile`: versionierte Spalten-/Formatdefinition für CSV bzw. Berichtsvorlage für PDF
- `WorkTimeRuleSet`: arbeitgeber-/gruppenbezogene Regeln mit Gültigkeitszeitraum
- `WorkerProtectionProfile`: Alters-/Schutzstatus, Ausbildung, Berufsschulbezug und
  dokumentierte Ausnahmen; getrennt von Vollzeit/Teilzeit/Minijob

## Rechtliche Prüfbedarfe

Vor Pilotfreigabe werden Arbeitsverträge, Tarif-/Betriebsregeln, Regeln für Minderjährige,
Auszubildende und Minijobs, Nacht-/Sonn-/Feiertagsarbeit, Pausen, Ruhezeiten, Fahrt zwischen
Stationen, Rüst-/Kassenübergabezeiten und Aufbewahrung juristisch geprüft. Besteht ein
Betriebsrat, ist er frühzeitig einzubeziehen. Das Produkt protokolliert tatsächliche
Arbeitsaufnahme auch bei einer Regelwarnung; es macht sie nicht durch einen harten
Stempelstopp unsichtbar.

Primärquellen für die spätere juristische Prüfung:

- [BAG 1 ABR 22/21](https://www.bundesarbeitsgericht.de/entscheidung/1-abr-22-21/)
- [Arbeitszeitgesetz](https://www.gesetze-im-internet.de/arbzg/)
- [§ 17 Mindestlohngesetz](https://www.gesetze-im-internet.de/milog/__17.html)
- [§ 26 Bundesdatenschutzgesetz](https://www.gesetze-im-internet.de/bdsg_2018/__26.html)
- [§ 87 Betriebsverfassungsgesetz](https://www.gesetze-im-internet.de/betrvg/__87.html)

## Abnahmekriterien des Piloten

- jedes Ereignis wird höchstens einmal verarbeitet und bleibt unveränderlich;
- Mitarbeiter sehen nur eigene Zeiten, Leitungen nur freigegebene Stationen;
- keine Selbstfreigabe und keine stille administrative Änderung;
- Partner können Korrekturen mandantenweit, Stationsleitungen und aktive Vertretungen nur
  für berechtigte Stationen entscheiden;
- Antragsteller, betroffene Person und Ersteller einer manuellen Änderung können dieselbe
  Korrektur nicht genehmigen;
- Offlinebuchungen sind klar erkennbar, verschlüsselt und konfliktfähig;
- Ereignisse nach einer zwischenzeitlichen zentralen Sperre werden beim Sync erkannt,
  auditiert und als Prüffall an die zuständige Leitung geleitet;
- nach Ablauf des 48-Stunden-Pakets ist ohne erfolgreiche Erneuerung keine neue
  Offline-Anmeldung möglich;
- Personalnummer+PIN funktioniert online und innerhalb des gültigen Offlinepakets als
  Standard und Fallback; QR+PIN und NFC+PIN können gerätebezogen deaktiviert werden;
- QR/NFC ohne PIN sowie fremde, widerrufene oder einer anderen Station zugeordnete
  Identifikatoren werden abgewiesen;
- Rohzeit, korrigierte Zeit und freigegebene Zeit sind unterscheidbar;
- automatische Pausenabzüge sind als eigene Berechnungsposition sichtbar, referenzieren
  eine Regelversion und verändern keine Rohereignisse;
- Änderungen eines Pausenregelprofils verändern keine bereits freigegebenen Perioden;
- jede Korrektur, Wiederöffnung und jeder Export ist auditiert;
- Cross-Tenant- und Cross-Station-Negativtests bestehen;
- Mitarbeiter werden über Änderungen informiert;
- freigegebene Perioden und Exporte sind reproduzierbar;
- CSV ist maschinenlesbar und versioniert, PDF ist ein unveränderlicher Prüf-/Übersichtsbericht;
- CSV und PDF entstehen aus demselben freigegebenen Datenstand und müssen identische Summen liefern;
- Steuerberater erhalten kein Plattformkonto; der Versand erfolgt geschützt per E-Mail
  an bestätigte Empfängeradressen und bleibt vollständig auditiert;
- Datenschutz-, Arbeitsrechts- und gegebenenfalls Betriebsratsfreigaben liegen vor.

## Noch zu entscheiden

1. **Entschieden:** Beide Stationen gehören im Pilot einem Einzelunternehmen in Hessen.
2. **Entschieden:** Vollzeit, Teilzeit, Minijob, Auszubildende und Minderjährige müssen unterstützt werden.
3. **Entschieden für Pilot:** Kein Betriebsrat; Tarif-/Betriebsvereinbarungen noch bestätigen. Produkt bleibt dafür konfigurierbar.
4. **Entschieden:** Eine Station ist 24/7; Schichten über Mitternacht sind Pflichtfälle.
5. **Teilweise entschieden:** Echte Ruhepause nur bei vollständiger Ablösung;
   Kunden-/Kassenbetreuung gilt als Arbeitsbereitschaft. Unterbrochene Ruhepausen werden
   beendet und später neu begonnen. Pausenabzug ist als `manual`, `warning` oder
   `automatic` konfigurierbar. Beide Pilot-Tankstellen starten mit `warning` ohne Abzug.
   Konkrete Warnschwellen und Ablösungsorganisation noch klären.
6. Wie werden Fahrten und Arbeitszeit zwischen den Stationen vergütet?
7. **Entschieden:** Personalnummer+PIN ist Pilotstandard und Fallback; QR+PIN und NFC+PIN
   sind optional je Gerät aktivierbar. Kein QR/NFC ohne PIN.
8. **Entschieden:** Das MDE muss 48 Stunden vollständig offline funktionieren.
9. **Entschieden:** Partner, zuständige Stationsleitung oder aktive Vertretung mit
   `time.correction.review`; keine Genehmigung durch Antragsteller, betroffene Person oder
   Ersteller derselben manuellen Änderung.
10. **Teilweise entschieden:** CSV/PDF sowie DATEV, ADDISON und eurodata; Versand geschützt
    per E-Mail ohne Steuerberater-Login. Konkrete Produktvarianten/Importformate noch klären.
11. Welche TMS5000-Version und welche verfügbare Schnittstelle liegen vor?
12. Soll die private PWA später außerhalb der Station stempeln dürfen?
13. **Entschieden:** Frische Anmeldung und Stempeln müssen bei vollständigem Netzausfall funktionieren.
14. Welche konkreten Produkte/Versionen von DATEV, ADDISON und eurodata und welche Importformate erwarten die Steuerberater?
15. **Entschieden:** Pilot in Hessen; das Produkt unterstützt alle deutschen Bundesländer über versionierte Regelkalender.
16. Welche exakten Zebra-, Nerugged- und Netum-Modelle, Android-Versionen und MDM-Funktionen sind vorhanden?
