# Merlin – Tankstellen-SaaS

Dieses Repository enthält das vom Nutzer freigegebene technische Laravel-/Filament-
Grundgerüst. Fachmodule werden weiterhin erst nach vollständiger Planung und Freigabe
ihres jeweiligen Blueprints implementiert.

## Produktziel

Eine mandantenfähige SaaS-Plattform für Tankstellenpartner mit mehreren Standorten,
Mitarbeitern und standortbezogenen Rollen. Operative Funktionen wie Schichtplanung,
Zeiterfassung, MHD, HACCP, Abschreibungen und Inventuren werden als einzeln planbare
und freigebbare Module umgesetzt.

## Planungsunterlagen

- [Produktumfang](docs/01-produktumfang.md)
- [Architektur](docs/02-architektur.md)
- [Security und DSGVO](docs/03-security-dsgvo.md)
- [Roadmap und Freigaben](docs/04-roadmap.md)
- [Offene Entscheidungen](docs/05-offene-entscheidungen.md)
- [Fachkonzept Zeiterfassung für den Pilot](docs/06-zeiterfassung-pilot.md)
- [Offline-MDE, Regelwerke und Payroll-Exporte](docs/07-offline-export-regelwerke.md)
- [Mitarbeiter-Ersterfassung und Onboarding](docs/08-mitarbeiter-onboarding.md)
- [Stationsstammdaten und Self-Service-Anlage](docs/09-stationsstammdaten.md)
- [Technologiestack, Merlin-Design und Codekonventionen](docs/10-tech-stack-ui-codekonventionen.md)
- [Agententeam und Aufruf](docs/11-agententeam-und-aufruf.md)
- [Core-MVP-Blueprint](docs/12-core-mvp-blueprint.md)
- [Permission-Katalog](docs/13-permission-katalog.md)
- [Laravel-Scaffoldplan](docs/14-laravel-scaffold-plan.md)
- [UX-, Navigation- und Formularplan](docs/15-ux-navigation.md)
- [Gate-0-Checkliste](docs/16-gate-0-checkliste.md)
- [DACH-Markenkatalog und Seeder-Spezifikation](docs/17-dach-markenkatalog-seeder.md)
- [Versionierte DACH-Seed-Daten](docs/data/fuel-station-brands-dach.json)
- [Arbeitsweise der spezialisierten Agents](AGENTS.md)

## Aktueller Status

`Technisches Scaffold / fachliche Planung` – Laravel 13 und Filament 5 sind installiert.
MySQL-Zugang, Migrationen, Benutzer und Fachmodule wurden noch nicht eingerichtet.
Annahmen sind noch nicht vollständig fachlich, technisch und juristisch abgenommen.
Preise, Zahlungsanbieter und operative Fachmodule sind ausdrücklich noch nicht
endgültig spezifiziert.
