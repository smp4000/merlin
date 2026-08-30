# Core-MVP-Blueprint

Status: `Planungsentwurf – nicht implementierungsfreigegeben`

## Ziel

Phase 1 ermöglicht einem bestätigten Tankstellenpartner:

- automatischen Start eines isolierten 14-Tage-Trials;
- Anlage von Legal Entity und eigenen Tankstellen;
- Auswahl zentral gepflegter Brands und Kataloge;
- Mitarbeitervoreintrag, Einladungslink, Selbsterfassung und bereichsgebundene Freigabe;
- Mehrfachzuordnung von Mitarbeitenden zu Stationen;
- eigene Rollen innerhalb zentraler Sicherheitsgrenzen;
- Registrierung und Sperrung von Android-MDEs;
- tatsächliche Arbeitszeit online und 48 Stunden offline;
- Korrekturen, Periodenfreigabe, Audit und Partner-Settings.

## Nicht Teil von Phase 1

Preise/Billing, Schichtplanung, Urlaub/Krankheit, Lohnberechnung, MHD, HACCP,
Inventur, Abschreibung, TMS5000-Direktanbindung, native Apps sowie produktive
DATEV-/ADDISON-/eurodata-Adapter ohne bestätigte Formatspezifikation.

## Zentrale Abläufe

### Registrierung

```text
Registrierungsabsicht → Rechtstexte → E-Mail-Bestätigung
→ atomar Tenant + Owner + Trial + Basis-Entitlements → Onboarding
```

Wiederholte Callbacks sind idempotent. Unbestätigte Registrierungen erhalten keinen
operativen Zugriff. Nach 14 Tagen wechselt der Tenant automatisch in den Nur-Lese-Modus.
Vorhandene Daten bleiben sichtbar und exportierbar; fachliche Schreibvorgänge werden
zentral serverseitig gesperrt. Ein Super-Admin kann den Trial genau einmal mit Grund und
um genau 14 weitere Tage verlängern. Entscheidung und Ausführung werden auditiert.

### Organisation und Station

Der Owner legt eine oder mehrere Legal Entities an. Stationen referenzieren Tenant,
Legal Entity und zentrales Brand. Formulare verwenden die Tabs `Allgemein`, `Adresse`,
`Öffnungszeiten`, `Shop`, `Karten`, `Kennungen` und `Dokumente`. Partner aktivieren
gültige Stationen selbst; kritische Änderungen können einen Prüfstatus auslösen.

### Mitarbeiter-Onboarding

```text
Voreintrag → Einladungslink/Code → Selbsterfassung
→ Freigabe Partner oder zuständige Stationsleitung
→ Credential/PWA für freigegebene Station(en) aktiv
```

Mitarbeitende dürfen Arbeitgeber, Schutzprofil, Rollen und Stationsrechte nicht selbst
festlegen. Der Partner darf alle vorgesehenen Stationen im eigenen Mandanten freigeben;
eine Stationsleitung nur ihre zugeordneten Stationen. Bei Mehrfachzuordnung besitzt jede
Station einen eigenen Freigabestatus. Private E-Mail, Mobilnummer und Smartphone bleiben
vollständig optional. Pflichtnachrichten und Recovery werden auch über persönliche,
betriebliche MDE-Abläufe angeboten.

### Mehrfachzuordnung

Eine Person, ein Benutzerkonto und im Pilot ein Beschäftigungsverhältnis können mehrere
zeitlich gültige Stationszuordnungen besitzen. Jede Arbeitsbuchung trägt die tatsächliche
Station. Parallele Arbeitssitzungen werden als Konflikt erhalten.

### Verbindliche Stationsauswahl

Partner, Leitungen, Vertretungen und Mitarbeitende wählen nach der Tenantwahl eine
Station, bevor sie stationsbezogen arbeiten. Bei mehreren aktuell erlaubten Stationen ist
die Auswahl verpflichtend; bei genau einer kann Merlin sie automatisch setzen. Angezeigt
werden nur aktive Stationen, für die zum aktuellen Zeitpunkt eine wirksame Berechtigung
beziehungsweise freigegebene Stationszuordnung besteht. Eine befristete Vertretungs- oder
Mitarbeiterzuordnung erscheint erst ab ihrem Beginn und verschwindet bei Ablauf oder
Widerruf automatisch.

Der gewählte Stationskontext ist in Kopfbereich und Seitentitel ständig sichtbar.
Wechseln ist über einen zentralen Stationsschalter möglich. Ungespeicherte Eingaben
erfordern eine Warnung. Während einer aktiven Arbeitszeitsitzung darf ein Mitarbeiter die
Station nicht still wechseln; dafür ist später ein eigener Stationswechselprozess oder
das Beenden und erneute Starten an der anderen Station nötig.

Ein stationsgebundenes MDE gibt seine registrierte Station fest vor und zeigt sie vor der
Aktion deutlich an. Der Mitarbeiter wählt dort keine andere Station, seine aktuell
wirksame Zuordnung zur Gerätestation wird aber serverseitig beziehungsweise im gültigen
Offlinepaket geprüft. Auf dem persönlichen Gerät erfolgt bei mehreren freigegebenen
Stationen die bewusste Auswahl.

### Mehrere unabhängige Partner

Eine globale Benutzeridentität kann mehrere getrennte TenantMemberships besitzen. Jeder
Partner führt einen eigenen tenant-gebundenen Mitarbeiter- und Beschäftigungsdatensatz
und erfährt nicht, ob weitere Memberships existieren. Nach der Anmeldung wählt der
Mitarbeiter bewusst den Betrieb; alle Rollen, Stationen, Zeiten, Nachrichten und
MDE-Credentials werden ausschließlich aus diesem Tenant-Kontext geladen. Verknüpfungen
entstehen nur durch angenommene Einladung oder bestätigte Anmeldung, niemals durch
automatisches Stammdaten-Matching.

### Zeitlich befristete Vertretung

Partner und Stationsleitungen können Personen mit festem Beginn, Ende, Stations-Scope und
ausdrücklich ausgewählten Rechten als Vertretung zuweisen. Stationsleitungen bleiben auf
ihre eigenen Stationen begrenzt. Rechte beginnen und enden automatisch. Eine Vertretung
kann sich nicht selbst verlängern und keine weitere Vertretung einsetzen. Rollenvergabe
durch die Vertretung ist nur erlaubt, wenn sie separat zugewiesen wurde, und bleibt auf
den bestehenden Zeitraum und Scope begrenzt. Alle Änderungen und kritischen Aktionen
werden auditiert.

### Zeiterfassung und MDE

Rohereignisse für Arbeitsbeginn/-ende, Pausenbeginn/-ende und Stationswechsel sind
unveränderlich. Korrekturen erzeugen neue Versionen. Das registrierte MDE ist genau einer
Station zugeordnet und erlaubt 48 Stunden Offline-Anmeldung/-Stempeln für vorher
synchronisierte Personen. Offline sind keine Korrekturen, Exporte oder Adminfunktionen
erlaubt.

Personalnummer+PIN ist im Pilot der Standard und Fallback. QR+PIN und NFC+PIN können je
Gerät zusätzlich aktiviert werden; QR oder NFC ohne PIN sind nicht zulässig. Verlorene
QR-/NFC-Identifikatoren werden unabhängig vom Mitarbeiterkonto widerrufen.

Das verbleibende Risiko einer bis zu 48 Stunden verzögerten Offline-Sperrwirkung ist für
den Pilot akzeptiert. Beim Sync werden Ereignisse mit zwischenzeitlich widerrufenen
Credentials als Konflikt markiert und Partner beziehungsweise zuständiger Stationsleitung
zur dokumentierten Prüfung vorgelegt. Rohereignisse bleiben unverändert.

Korrekturen können durch den Partner, die zuständige Stationsleitung oder eine aktive
Vertretung mit `time.correction.review` genehmigt oder abgelehnt werden. Stationsleitung
und Vertretung bleiben auf ihren wirksamen Stations-Scope begrenzt. Antragsteller,
betroffene Person und Ersteller einer manuellen Änderung dürfen dieselbe Korrektur nicht
genehmigen.

Pausenregeln sind je Arbeitgeber versioniert als manuelle Erfassung, Warnung oder
automatischer Abzug konfigurierbar. Automatische Abzüge erzeugen transparente
Berechnungspositionen, verändern keine Rohereignisse und wirken nicht rückwirkend auf
freigegebene Perioden. Zeiten mit fortgesetzter Kunden- oder Kassenbetreuung bleiben als
Arbeitsbereitschaft sichtbar. Mitarbeitende können Abzüge per Korrekturantrag anfechten.
Die beiden Pilot-Tankstellen starten im Modus `warning` und kürzen Arbeitszeit nicht
automatisch.

### Settings

Der feste Partner-Menüpunkt `Einstellungen` enthält mandantenweite Regeln für Rollen,
Module, Onboarding, Zeit/Pausen, Geräte/Offline, Benachrichtigungen, Exporte,
Datenschutz/Aufbewahrung und Integrationen. Operative Stationen und Mitarbeitende bleiben
eigene Menüpunkte.

### Plattform-Support

Regulärer Zugriff auf Mandantendaten benötigt einen vom Partner freigegebenen,
zweckgebundenen und auf höchstens acht Stunden begrenzten `SupportAccessGrant`.
Break-glass ohne vorherige Freigabe ist nur bei einem schweren Sicherheits- oder
Systemvorfall mit drohender Schadensvergrößerung zulässig und endet nach spätestens 60
Minuten. Er benötigt Incident-ID, Step-up-MFA, minimalen Scope, sofortige Benachrichtigung
und unabhängige Nachkontrolle; Exporte bleiben gesperrt. Beide Zugriffsarten enden
automatisch und können nicht verlängert werden. Weiterer Zugriff benötigt einen neuen
Vorgang.

## Zustände

| Objekt | Zustände |
|---|---|
| Registrierung | begonnen → E-Mail ausstehend → bestätigt / abgelaufen / widerrufen |
| Trial | aktiv → Nur-Lesen / einmalig verlängert → Nur-Lesen |
| Legal Entity | Entwurf → aktiv → inaktiv |
| Station | Entwurf → optional Prüfung → aktiv → temporär geschlossen → geschlossen |
| Mitarbeiter | vorbereitet → Selbsterfassung → Prüfung → freigegeben → aktiv → ruhend → ausgeschieden |
| Einladung | erzeugt → ausgegeben → geöffnet → verbraucht / abgelaufen / widerrufen |
| Vertretung | geplant → aktiv → abgelaufen / vorzeitig widerrufen |
| Gerät | vorbereitet → registriert → aktiv → offline → gesperrt → widerrufen |
| Korrektur | Entwurf → eingereicht → Klärung → genehmigt / abgelehnt |
| Periode | offen → prüfbereit → freigegeben → exportiert → wieder geöffnet |
| Supportgrant | beantragt → freigegeben → aktiv → abgelaufen / widerrufen |

Statusänderungen führen Akteur, Zeitpunkt, Grund und gegebenenfalls Freigeber. Historie
wird nicht spurlos überschrieben.

## Kernakzeptanz

- wiederholte E-Mail-Bestätigung erzeugt keinen zweiten Tenant;
- der Ablauf des 14. Tages aktiviert ohne manuellen Eingriff den Nur-Lese-Modus;
- im Nur-Lese-Modus bleiben Ansichten und Exporte möglich, während fachliche
  Schreibvorgänge serverseitig abgewiesen werden;
- nur ein Super-Admin kann einen Trial genau einmal mit Grund um genau 14 weitere Tage
  verlängern; jeder Versuch wird auditiert;
- jede Ressource bleibt durch Tenant, Permission, Scope und Entitlement geschützt;
- Partner können gültige Stationen selbst anlegen und aktivieren;
- Mitarbeiter ohne private E-Mail, Mobilnummer oder Smartphone können Onboarding,
  Zeiterfassung, Pflichtnachrichten und Recovery vollständig über betriebliche Wege nutzen;
- Partner können Onboardings mandantenweit und Stationsleitungen ausschließlich für ihre
  zugeordneten Stationen freigeben;
- eine Mehrfachzuordnung gewährt nur an bereits freigegebenen Stationen Zugriff;
- Mehrfachzuordnung erzeugt keine zweite Identität;
- bei mehreren erlaubten Stationen ist vor operativer Arbeit eine bewusste
  Stationsauswahl erforderlich;
- die Stationsauswahl erweitert keine Rechte und wird bei jedem Request gegen Zeitraum,
  Freigabe, Rolle und Tenant geprüft;
- abgelaufene oder widerrufene Zuordnungen können weder ausgewählt noch durch eine alte
  Sitzung weiterverwendet werden;
- jeder stationsbezogene Schreibvorgang und jede Arbeitsbuchung trägt genau die geprüfte
  Station; `Alle Stationen` ist kein zulässiger Schreibkontext;
- ein stationsgebundenes MDE akzeptiert ausschließlich Aktionen für seine feste Station;
- dieselbe Identität kann getrennte Mitarbeiterdatensätze bei unabhängigen Mandanten
  verwenden, ohne Cross-Tenant-Dateneinsicht oder automatische Zusammenführung;
- ein Tenantwechsel bindet einen neuen geprüften Kontext und übernimmt keine Rolle,
  Berechtigung, MFA-Einstellung oder MDE-Kennung des vorherigen Tenants;
- Vertretungsrechte sind vor Beginn und nach Ablauf oder Widerruf serverseitig unwirksam;
- eine Vertretung kann Laufzeit, Scope und eigene Rechte nicht erweitern und keine weitere
  Vertretung einsetzen;
- niemand genehmigt eigene Korrekturen oder Rechte;
- Korrekturprüfer benötigen Scope für alle betroffenen Stationen; andernfalls wird der
  Vorgang aufgeteilt oder an den Partner weitergeleitet;
- automatische Pausenabzüge referenzieren eine gültige Regelversion, sind sichtbar und
  verändern weder Rohereignisse noch bereits freigegebene Perioden;
- das Pilotprofil `warning` erzeugt Hinweise, aber niemals einen automatischen Abzug;
- Offlineereignisse sind signiert, idempotent und konfliktfähig;
- Ereignisse mit zwischenzeitlich widerrufenen Offline-Credentials werden beim Sync
  erkannt und niemals still verworfen oder automatisch freigegeben;
- Personalnummer+PIN funktioniert als MDE-Standard und Fallback; optionale QR-/NFC-Wege
  erfordern ebenfalls die persönliche PIN und sind separat widerrufbar;
- regulärer Supportzugriff benötigt Partnerfreigabe, MFA, Zweck, Scope, Zeitlimit und Audit;
- Break-glass ist auf schwere Vorfälle begrenzt, benachrichtigt den Partner sofort und
  erlaubt keine Exporte;
- Cross-Tenant-/Cross-Station-Negativtests bestehen;
- Secrets erscheinen weder in Stammdaten noch Logs oder Exporten.
