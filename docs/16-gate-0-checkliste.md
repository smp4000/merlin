# Gate-0-Checkliste vor Programmstart

## Produktentscheidungen

- [x] mehrsprachige Architektur ab Plattformfundament; Deutsch als System- und
      Fallbacksprache, persönliche Sprache vor Mandantenstandard
- [ ] vollständig unterstützte Sprachen des ersten Releases und fachliche Freigabe
      rechtlich relevanter Übersetzungen

- [x] gemeinsames Benutzerkonto für Beschäftigungen bei unabhängigen Partnern;
      getrennte TenantMemberships, Mitarbeiter-/Beschäftigungsdaten, Rollen, Zeiten und
      MDE-Credentials; bewusste Tenantwahl und keine Offenlegung anderer Memberships
- [x] verpflichtende Stationsauswahl vor operativer Arbeit bei mehreren wirksamen
      Stationen; automatische Auswahl bei genau einer Station, serverseitige
      Neuvalidierung je Request und feste Station am registrierten MDE

- [x] Verhalten nach Tag 14: automatischer Nur-Lese-Modus; Lesen und Export bleiben
      möglich, fachliche Schreibvorgänge sind gesperrt; einmalige, begründete und
      auditierte Verlängerung um genau 14 weitere Tage nur durch Super-Admin
- [x] Rechtevergabe durch Partner mandantenweit und durch Stationsleitungen ausschließlich
      für zugeordnete Stationen; keine Vergabe über den eigenen Berechtigungsumfang und
      keine Delegation reservierter Plattform-, Eigentums-, Lösch- oder Vollzugriffsrechte
- [x] zeitlich befristete Vertretungen mit festem Zeitraum, Rollen-/Permission-Auswahl und
      Stations-Scope; automatischer Rechteentzug nach Ablauf, kein Selbstverlängern und
      keine Weiterdelegation
- [x] Mitarbeiterfreigabe wahlweise durch Partner oder zuständige Stationsleitung;
      Partner mandantenweit, Stationsleitung nur für eigene Stationen, bei
      Mehrfachzuordnung separater Freigabestatus je Station
- [x] Zeitkorrekturen durch Partner, zuständige Stationsleitung oder aktive Vertretung mit
      `time.correction.review`; keine Genehmigung durch Antragsteller, betroffene Person
      oder Ersteller derselben manuellen Änderung
- [x] Pausenmodell: echte Ruhepause nur bei vollständiger Ablösung; Kunden-/Kassenbetreuung
      als Arbeitsbereitschaft; Unterbrechung beendet den Pausenabschnitt; Abzug je
      Arbeitgeber als `manual`, `warning` oder `automatic` versioniert einstellbar
- [x] Pilot-Startmodus `warning`: Hinweise bei fehlenden oder zu kurzen Pausen, aber kein
      automatischer Abzug
- [ ] konkrete Pilot-Warnschwellen, Beschäftigtengruppen und betriebliche
      Ablösungsorganisation
- [x] MDE-Pilotstandard Personalnummer+PIN; optional QR+PIN und NFC+PIN je Gerät;
      Personalnummer+PIN als Fallback, kein QR/NFC ohne PIN
- [x] private E-Mail, Mobilnummer und Smartphone für Mitarbeiter vollständig optional;
      Onboarding, Zeitbuchung, Pflichtnachrichten und Recovery über betriebliche MDE-/
      Einmalcode-Prozesse möglich

## Technik und Betrieb

- [x] MFA/Passkeys für normale Admin-Logins rollenbezogen als `disabled`, `optional` oder
      `required` einstellbar; Pilot startet ohne Pflicht
- [x] Step-up-MFA für Mandantensupport, Break-glass und sensible Exporte bleibt unabhängig
      von der Login-Einstellung verpflichtend
- [ ] Identity Provider, konkrete MFA-/Passkey-Verfahren und Recovery-Prozess
- [ ] EU-Hosting, Redis, Objektspeicher, E-Mail und Secrets-Management
- [ ] RTO, RPO, Backup-/Restore- und Incidentmodell
- [ ] exakte MDE-Modelle, Androidstände, StrongBox/Keystore und MDM
- [x] 48-Stunden-Offline-Restrisiko ausdrücklich akzeptiert: zentrale Sperren wirken auf
      vollständig offline betriebene MDEs verzögert; spätere Ereignisse werden beim Sync
      markiert, auditiert und zur Prüfung vorgelegt
- [ ] technischer Sicherheitsnachweis für Offlinepaket, Geräteschlüssel, Ablauf,
      Widerrufsabgleich, Konflikterkennung und Tests vor Pilot
- [ ] geschützter Payroll-E-Mailweg und Empfängerbestätigung

## Recht, DSGVO und Security

- [ ] Arbeits-/Tarifprüfung für Minderjährige, Minijobs, Nacht, Sonn-/Feiertag,
      Pausen, Alleinarbeit und Stationsfahrten
- [ ] Datenschutzrollen, AVV/TOMs, Verarbeitungstätigkeiten und DSFA-Screening
- [ ] Lösch-/Aufbewahrungsmatrix
- [x] globale Plattformkataloge wie Brands und Kraftstoffsorten regulär durch getrennte
      Plattform-Permissions verwaltbar; keine operative Mandantendateneinsicht
- [x] versionierter DACH-Startkatalog mit Quellen, stabilen Slugs, Aliasen und
      kontrolliertem Auffangwert; idempotenter Laravel-Seeder für den Scaffold geplant
- [x] Mandantendateneinsicht ausschließlich zeitlich begrenzt, zweckgebunden,
      scope-begrenzt und vollständig auditiert
- [x] regulärer Mandanten-Support nur nach vorheriger Partnerfreigabe
- [x] Break-glass nur bei schwerem Sicherheits-/Systemvorfall und drohender
      Schadensvergrößerung; Incident-ID, Step-up-MFA, minimaler Scope, sofortige
      Benachrichtigung, keine Exporte und unabhängige Nachkontrolle
- [x] Höchstdauer: regulärer Supportgrant acht Stunden, Break-glass 60 Minuten;
      automatische Beendigung, keine Verlängerung, weiterer Zugriff nur über neuen Vorgang
- [ ] Audit-Aufbewahrung, Manipulationsschutz und Alarmierung
- [ ] Budget für Datenschutzprüfung und unabhängigen Penetrationstest

## Freigabeartefakte

- [ ] Core-MVP-Blueprint
- [ ] Permission-Matrix
- [ ] Architektur-ADRs und Scaffoldplan
- [ ] UX-Seiten-/Formularplan
- [ ] priorisiertes MVP-Backlog
- [ ] Pilot-, Support-, Incident- und Go/No-Go-Kriterien

Das reine Laravel-/Filament-Grundgerüst wurde am 30.08.2026 vom Nutzer separat
freigegeben und erstellt. Fachmodule beginnen weiterhin erst, wenn ihre echten Blocker
geschlossen und ihre vollständigen Blueprints vom Nutzer freigegeben sind.
