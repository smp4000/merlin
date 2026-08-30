# Laravel-Scaffoldplan

Dieser Plan beschreibt den Start nach Gate-0-Freigabe. Er erzeugt noch keinen Code.

## Zielstruktur

```text
app/
├── Foundation/{Tenancy,Authorization,Audit,Files,Settings,Queue,SupportAccess}
├── Modules/{Identity,Registration,PlatformCatalog,Tenants,Organizations,
│            AccessControl,Trials,Entitlements,Employees,Devices,TimeTracking}
├── Filament/{Platform,Partner,Shared}
└── Http/{Api,Middleware}

resources/
├── lang/{de,en,...}/
├── css/{filament-platform,filament-partner,pwa}
└── js/pwa/{shared,employee,mde}

tests/
├── Architecture/
├── Unit/
├── Feature/{Platform,Partner,Api,Tenancy}
├── Integration/
├── Browser/
└── Pwa/
```

Jedes Modul gliedert sich nach Bedarf in `Domain`, `Application`, `Infrastructure`,
`Presentation`, `Policies`, `Events`, `Jobs` und `Tests`. `Foundation` enthält keine
Fachlogik. Migrationen bleiben zunächst zentral und geordnet.

## Tenant-Enforcement

```text
Authenticate → ResolveTenant → ValidateMembership → Bind TenantContext
→ Resolve/Validate StationContext for operational routes
→ CheckEntitlement → Query Scope → Policy → Application Service
```

- `TenantContext` ist request-/job-scoped und unveränderlich.
- `tenant_id` ist `NOT NULL`, nicht mass-assignable und nach Anlage unveränderlich.
- Anlage setzt Tenant ausschließlich aus dem Kontext.
- zusammengesetzte `(tenant_id, parent_id)`-Constraints schützen Beziehungen.
- Listenabfragen werden vor der Policy bereits nach Tenant/Scope begrenzt.
- Jobs, CLI, Cache, Locks, Dateien und Exporte tragen expliziten Tenant-Kontext.
- Global Scope allein genügt nicht; Architektur- und Negativtests erzwingen die Grenzen.
- `StationContext` wird nur aus Stationen gebunden, die im aktiven Tenant und im
  aktuellen Rollen-/Zuordnungszeitraum erlaubt sind. Alte Sitzungswerte werden je
  Request neu geprüft.

## Migrationreihenfolge

1. technische Queue-/Cache-/Lock-/Outbox-Grundlagen
2. globale Kataloge, Brands, Rechtsformen, Module und Permissions
3. Users, externe Identitäten, Registrierung und Zustimmungen
4. Tenants, mehrfach mögliche Memberships, Trials und Entitlements
5. Legal Entities, Stationen, Adressen, Zeiten und Kennungen
6. Plattform-/Tenantrollen, Permissions und Scopes
7. Employees, Employments, Schutzprofile, Stationszuordnungen und Einladungen
8. Devices, Registrierungen, Sessions und Offline-Metadaten
9. Settings, Files, Audit und Supportgrants
10. freigegebenes Zeiterfassungsmodul

Interne numerische Schlüssel vereinfachen MySQL-FKs; opake `public_id`-Werte werden für
URLs und APIs verwendet.

`user_identities` bleibt global. `tenant_memberships` verbindet eine Identität mit einem
Tenant; `employees` und alle Beschäftigungsdaten tragen `tenant_id`. Ein Unique Constraint
auf `(tenant_id, user_identity_id)` verhindert doppelte Mitarbeiteridentitäten innerhalb
eines Tenants, ohne unabhängige Datensätze in anderen Tenants zu verhindern. Tenantwahl
und Tenantwechsel werden als eigene Security-Flows mit Negativtests umgesetzt.

## Präsentationsgrenzen

- Filament Platform Panel: Mandantenmetadaten, Trials, Brands/Kataloge, Support und Audit
- Filament Partner Panel: Organisation, Stationen, Mitarbeiter, Rollen, Geräte,
  Zeitwirtschaft, Exporte und Settings
- eigene REST-API/PWA: Mitarbeiter, Scanner/NFC, IndexedDB, Service Worker,
  48-Stunden-Offline-Credentials und Synchronisation

Filament und PWA teilen Domain-/Application-Services, nicht die Livewire-Sitzungslogik.

## Scaffold-Reihenfolge nach Freigabe

1. stabile Versionen prüfen und Lockfiles erzeugen
2. Laravel 13, MySQL 8.4, Redis und lokale Storage-Umgebung
3. CI, Coding Style, statische Analyse und Test-Gates
4. Modulstruktur und Architekturtests
5. `TenantContext`, tenant-aware Models, Queries, Policies, Route Binding und Jobs
6. globale Kataloge und Identitäts-Shadow-Modell
   - `FuelStationBrandSeeder` liest den versionierten DACH-Datensatz, validiert ihn und
     führt idempotente Upserts anhand stabiler Slugs aus
   - Locale-Auflösung, Übersetzungsschlüssel und übersetzbare Katalogtexte werden vor
     den ersten Fachseiten eingerichtet
7. Registrierung, E-Mail-Verifizierung, atomare Tenant-/Trial-Anlage
8. Legal Entities, Stationen und Brand-Zuordnung
9. hybrides RBAC und Hochrisikorechte
10. sichere Panel-Shells und Merlin-Theme
11. Audit, Supportgrants, Settings, Files, Entitlements und Queues
12. Mitarbeiter-Onboarding
13. Geräteverwaltung und PWA-API
14. Offline-Security-Spike auf realen Geräten
15. erst danach Zeiterfassung implementieren

Konkrete Composer-/NPM-Pakete werden erst nach Versions-, Lizenz-, Wartungs-, Security-
und Laravel-/Filament-Kompatibilitätsprüfung gewählt. Das Fachmodell wird keinem
Permission- oder Tenancy-Paket angepasst.
