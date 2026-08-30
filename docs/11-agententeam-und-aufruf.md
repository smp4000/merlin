# Merlin-Agententeam und Aufruf

## Wo das Team gespeichert ist

Das verbindliche Team, seine Regeln und Qualitätsgrenzen stehen in der Datei
`AGENTS.md` im Projektstamm. Codex liest diese Projektanweisungen bei Arbeiten im
Repository. Fachentscheidungen der Agents werden nicht nur im Chat belassen, sondern
als versionierte Planungsdokumente unter `docs/` gespeichert.

Die Agents selbst sind keine dauerhaft laufenden Programme oder Benutzerkonten. Der
Lead-Agent startet für eine konkrete, klar abgegrenzte Aufgabe passende Subagents,
führt ihre Ergebnisse zusammen und beendet die Arbeitsrunde. Dadurch bleiben Kontext,
Kosten und Verantwortlichkeit kontrollierbar.

## Team

### Lead-Agent

Koordiniert das Gesamtprojekt, wählt Spezialisten, führt Ergebnisse zusammen, pflegt
Entscheidungen und stoppt vor nicht freigegebenen externen, kostenpflichtigen oder
destruktiven Aktionen.

### Produkt-/Domain-Agent

Übersetzt Tankstellenabläufe in Rollen, Workflows, User Stories und Akzeptanzkriterien.

### Modul-Discovery-Agent

Plant jedes neue Modul bis in die Details. Ohne freigegebenen Blueprint darf kein
Entwickler-Agent mit dem Modul beginnen.

### Architektur-/Daten-Agent

Verantwortet Laravel-Struktur, MySQL-Modell, Tenancy, APIs, Events, Queues und
Offline-Synchronisation.

### UX-/Filament-Agent

Entwirft Merlin-Design, Navigation, Tabs, Formulare, Settings und responsive
MDE-/Mitarbeiterabläufe.

### Rollen-/Berechtigungs-Agent

Pflegt Permission-Katalog, Systemrollen, frei definierbare Partnerrollen und
Stations-/Modulreichweiten.

### DSGVO-/Security-Agent

Prüft Datenschutz, Tenant-Isolation, Supportzugriff, Exporte, Secrets, Audit,
Aufbewahrung und Missbrauchsfälle.

### Hardware-/Integrations-Agent

Plant Zebra/Nerugged/Netum, TMS5000 sowie DATEV-, ADDISON- und eurodata-Adapter.

### Backend-Agent

Implementiert Laravel-Domänenmodule, Eloquent-Modelle, Policies, Services, Jobs,
Exports und Tests nach freigegebenem Blueprint.

### Frontend-/PWA-Agent

Implementiert Filament-Panels, Merlin-Theme und die offlinefähige MDE-/Mitarbeiter-PWA.

### Code-Dokumentations-Agent

Stellt englische technische Bezeichner und ausführliche deutsche DocBlocks/Kommentare
sicher. Er prüft jede Implementierungsrunde, nicht erst am Projektende.

### Test-/Security-Agent

Erstellt Positiv-, Negativ-, Tenant-, Berechtigungs-, Browser-, Offline- und
Securitytests und blockiert Releases mit hohen Befunden.

### Review-/Release-Agent

Prüft Umsetzung, Kommentare, Tests, Migrationen und Dokumentation unabhängig vor Merge,
Pilot und Veröffentlichung.

## Wie das Team aufgerufen wird

Der Nutzer kann den Lead natürlichsprachlich beauftragen. Beispiele:

- `Plane das Modul MHD vollständig mit dem Modul-Discovery-Agenten.`
- `Lass den UX-Agenten die Partner-Settings und Tab-Formulare entwerfen.`
- `Lass Architektur- und Security-Agent den Offline-Login gemeinsam prüfen.`
- `Implementiere die freigegebene Story mit Backend-, Test- und Kommentar-Agent.`
- `Lass den Review-Agent die letzte Änderung kontrollieren.`

Es ist nicht nötig, jeden Agent einzeln aufzurufen. Bei `Plane das nächste Modul` oder
`Setze die freigegebene Story um` wählt der Lead automatisch die erforderlichen Rollen
aus und berichtet, welche Agents eingesetzt wurden.

In der aktuellen Arbeitsumgebung können Lead und bis zu drei Spezialisten gleichzeitig
arbeiten. Größere Teams laufen deshalb in aufeinanderfolgenden Wellen, beispielsweise:

1. Modul, UX und Architektur
2. Security, Rechte und Review
3. Backend, Frontend und Tests
4. Dokumentation und Releaseprüfung

Wenn später direkte, wiederverwendbare persönliche Befehle statt der Lead-Steuerung
gewünscht sind, können die Rollen zusätzlich als Codex-Skills paketiert werden. Für die
jetzige Projektarbeit ist `AGENTS.md` plus Lead-Koordination die einfachere und
nachvollziehbarere Lösung.
