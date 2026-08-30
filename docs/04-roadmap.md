# Roadmap und Freigaben

## Phase 0 – vollständige Planung

Ergebnisse vor Programmstart:

- bestätigte Zielkunden, Gesellschafts-/Mandantenstruktur und drei wichtigste Abläufe;
- freigegebene Rollen- und Permission-Matrix;
- MVP-User-Stories mit Akzeptanzkriterien;
- Domainmodell und Architecture Decision Records;
- Geräte-/Kasseninventar eines Pilotpartners;
- Datenschutzrollen, Dateninventar, Löschmatrix und DSFA-Screening;
- UX-Flows/Wireframes für Registrierung, Admin, Mitarbeiter und MDE;
- nichtfunktionale Anforderungen, RTO/RPO und Browser-/Gerätematrix;
- Stack-, Hosting- und Identity-Entscheidung;
- Pilot-, Test- und Rolloutplan.

Gate 0: schriftliche Produkt-, Architektur-, Datenschutz- und Budgetfreigabe.

Bestätigter Pilotrahmen: zwei Aral-Tankstellen, ca. 25 Mitarbeitende, TMS5000 und
Android-MDE. Priorisierte Problemfelder sind Personalplanung, MHD und Dokumentation.

## Phase 1 – Plattformfundament

Landingpage/Registrierung, Trial, Tenant mit mehreren rechtlichen Gesellschaften,
Tankstellen, Brand-Katalog, Mitarbeiter, Mehrfachzuordnung, hybrides RBAC,
Einladungs-Onboarding, Geräteverwaltung, Basis-Zeiterfassung, Entitlements, Audit und
kontrollierter Supportzugriff.

Gate 1: automatisierte Tenant-Isolation, Rollen-Negativtests, Restore-Test,
Security-Review und Pilotpartner-Abnahme.

## Phase 2 – operativer Pilot

Mitarbeiter-PWA, Android-MDE-Kiosk, vertiefte Zeiterfassung, Aufgaben/Checklisten und
Nachrichten. Danach wird MHD als nächster vollständig geplanter Pilotprozess vorbereitet.
Offlinebetrieb gilt nur für bestätigte Abläufe.

Gate 2: reale Geräte- und Netztests, Datenschutzfreigabe, Schulung, Incident-Übung und
erfolgreicher begrenzter Pilot.

## Phase 3 – Module einzeln

Schicht-/Personalplanung, MHD, Dokumentation, Abschreibung, Inventur und HACCP werden nacheinander
durch Discovery → UX/Fachkonzept → Datenschutz/Security → Implementierung → Pilot →
Freigabe geführt. Kein paralleler Big-Bang-Rollout.

## Phase 4 – externe Portale und Integrationen

Lieferanten-/Kundenportal sowie Kassen-, Warenwirtschafts- und Lohnschnittstellen erst
nach bestätigtem Geschäftsfall, Datenvertrag und Integrationspartner.

## Phase 5 – Kommerzialisierung

Tarife, modulbasierte Pakete, Zahlungsanbieter, Rechnungen, Self-Service-Upgrades,
Kulanz-/Sperrlogik und Support-SLAs. Zahlungsdaten werden durch einen spezialisierten
Anbieter verarbeitet; die Plattform speichert keine Kartendaten.
