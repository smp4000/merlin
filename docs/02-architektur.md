# Zielarchitektur

## Architekturform

Das System startet als modularer Monolith. Fachmodule haben eigene Anwendungslogik,
Tabellen, APIs und Ereignisse, werden aber gemeinsam ausgeliefert und betrieben. Eine
Auslagerung in Services erfolgt nur bei gemessenem Skalierungs- oder Organisationsbedarf.

```text
Landingpage / Portale / PWA / MDE-Kiosk
                 │
       OpenID Connect Identity Provider
                 │
        Versionierte REST/OpenAPI API
                 │
          Modularer Monolith
       ├── Identity & Membership
       ├── Tenant & Station
       ├── Rollen & Permissions
       ├── Trial & Entitlements
       ├── Geräte
       ├── Audit & Supportzugriff
       └── spätere Fachmodule
                 │
      MySQL / Jobs / Objektspeicher
```

## Kernentitäten

- `Tenant`, eine oder mehrere `LegalEntity`, `Station`
- `Brand` als globaler Plattformkatalog; `Station.brand_id` referenziert ihn
- `UserIdentity`, `TenantMembership`, `Employee`, `Employment`
- `StationAssignment` mit Rolle und Gültigkeitszeitraum
- `Permission`, `SystemRole`, `CustomRole`, `RoleAssignment`
- `Device`, `DeviceRegistration`, `DeviceSession`
- `Trial`, `Subscription`, `ModuleEntitlement`
- `SupportAccessGrant`, `AuditEvent`
- `TenantAppearanceSetting` für das geprüfte mandantenweite Farbschema

Benutzerkonto und Mitarbeiterdatensatz bleiben getrennt. Jede mandantenbezogene
Entität trägt eine unveränderliche, nicht-nullfähige `tenant_id`. Eine Station und ein
Arbeitsverhältnis referenzieren zusätzlich ihre rechtliche Gesellschaft. So kann derselbe
Mandant sowohl einen einzelnen Betreiber als auch eine Unternehmensgruppe abbilden.

Eine globale `UserIdentity` kann mehrere aktive `TenantMembership`-Datensätze besitzen
und damit für rechtlich unabhängige Betreiber verwendet werden. `Employee`, `Employment`,
Stationszuordnungen, Rollen, Zeiten, Dokumente und MDE-Credentials bleiben je Tenant
getrennt. Innerhalb eines Tenants kann eine Identität höchstens einem `Employee`
zugeordnet sein; dieser kann mehrere Beschäftigungen und Stationen besitzen.

Nach der Anmeldung wird ein Tenant ausschließlich aus den aktiven Memberships der
Identität ausgewählt und danach als unveränderlicher `TenantContext` gebunden. Ein
Tenantwechsel beendet den vorherigen fachlichen Kontext und autorisiert alle Abfragen
neu. Rollen, Einstellungen, Benachrichtigungen und MFA-Vorgaben werden nie von einem
Tenant in einen anderen übernommen.

`TenantAppearanceSetting` speichert ausschließlich einen von Merlin bereitgestellten
stabilen `theme_key`; beliebiges CSS, JavaScript oder ungeprüfte Farbwerte werden nicht
aus Mandanteneingaben übernommen. Die Auflösung erfolgt erst nach Bindung des geprüften
`TenantContext`. Änderungen werden als mandantenbezogene Settings-Änderungen mit Akteur,
Zeitpunkt sowie altem und neuem Wert auditiert.

Nach dem `TenantContext` wird für operative Arbeit zusätzlich ein `StationContext`
gebunden. Besitzt die Person mehrere aktuell wirksame und freigegebene Stationen, muss
sie eine Station bewusst auswählen. Bei genau einer berechtigten Station kann diese
automatisch gesetzt werden, bleibt aber dauerhaft sichtbar. Eine übertragene
`station_id` ist nur ein Auswahlwunsch: Der Server prüft bei jedem Request erneut Tenant,
Membership, Stationszuordnung, Permission, Freigabe und Gültigkeitszeitraum. Ablauf oder
Widerruf einer Zuordnung invalidiert den Stationskontext sofort.

Mandantenweite Verwaltungsaufgaben wie Partner-Settings können ohne Stationskontext
ausgeführt werden, wenn ein mandantenweites Recht besteht. Jeder stationsbezogene
Schreibvorgang benötigt dagegen genau eine aktive Station. Eine Ansicht `Alle Stationen`
darf nur ausdrücklich erlaubte, lesende Gesamtauswertungen liefern und ist niemals ein
Ersatzwert für `station_id`.

`LegalEntity` führt Rechtsform, juristische Firmierung, Arbeitgeberkennung und Sitz.
Rechtsformen werden als versionierter Katalog statt als festes Freitextfeld modelliert.
Eine `Station` speichert Adresse und Bundesland; Feiertags- und Arbeitszeitregelwerke
werden zeitlich versioniert referenziert und nicht dauerhaft in Programmcode eingebaut.

## Mandantentrennung

- gemeinsame MySQL-Datenbank mit gemeinsamem Schema;
- zentrale Laravel-Tenant-Auflösung und verpflichtende tenant-scoped Repositories/Queries;
- Tenant-Kontext nur aus geprüfter Sitzung und aktiver Mitgliedschaft;
- bei mehreren Memberships bewusste Tenant-Auswahl vor Bindung des `TenantContext`;
- bei mehreren berechtigten Stationen bewusste Stationsauswahl vor operativer Arbeit;
- keine Autorisierung anhand einer frei übertragenen `tenant_id`;
- keine Autorisierung anhand einer frei übertragenen `station_id`;
- zusammengesetzte Fremdschlüssel und Unique Constraints mit `tenant_id`;
- Tenantbindung auch für Dateien, Cache, Suche, Queues, Jobs, Exporte und Webhooks;
- automatisierte Cross-Tenant-Negativtests für jeden Zugriffspfad.

Es gibt kein mandantenübergreifend durch Partner durchsuchbares Mitarbeiterverzeichnis.
Ein vorhandenes Benutzerkonto wird nur durch eine vom Mitarbeiter angenommene Einladung
oder bestätigte Anmeldung verknüpft, niemals automatisch anhand von Name,
Personalnummer, E-Mail oder Mobilnummer.

MySQL bietet keine mit PostgreSQL RLS vergleichbare eingebaute Schutzschicht. Deshalb
dürfen einfache, ungescopte Eloquent-Abfragen in mandantenbezogenen Modulen nicht
verwendet werden. Tenant-Kontext, Policies, Query Scopes, Queue-Payloads und
zusammengesetzte Schlüssel werden zentral standardisiert und durch Architekturtests
erzwungen. Ein eigenes Datenbankschema pro Tenant ist nicht vorgesehen.

Eine eigene Datenbank pro Mandant kann später als Enterprise-Isolationsstufe angeboten
werden. Ein Schema pro Mandant ist nicht vorgesehen.

## Authentifizierung und Autorisierung

Authentifizierung erfolgt über einen etablierten OIDC/OAuth-kompatiblen Identity
Provider. MFA beziehungsweise Passkeys werden technisch von Beginn an unterstützt und
über eine Richtlinie pro Plattform- beziehungsweise Partnerrolle als `disabled`,
`optional` oder `required` konfiguriert. Im Pilot ist MFA für den normalen Login von
Plattform-Admins, Partnern und Stationsleitungen nicht verpflichtend. Access-Tokens sind
kurzlebig; Sitzungen und Geräte-Tokens können einzeln widerrufen werden.

Die konfigurierbare Login-Richtlinie schwächt keine aktionsbezogene Step-up-Pflicht ab.
Mandantensupport, Break-glass und andere ausdrücklich als Step-up-pflichtig definierte
Hochrisikoaktionen benötigen weiterhin einen zusätzlichen Faktor.

Autorisierung wird serverseitig über Permission, Modul, Tenant, Station, Ressource und
Zeitraum ausgewertet. UI-Ausblendung ist keine Sicherheitskontrolle.

## Super-Admin und zentrale Stammdaten

Brand-, Kraftstoffsorten-, Rechtsform-, Modul- und globale Vorlagenpflege benötigt keine
reguläre Einsicht in operative Mandantendaten. Berechtigte Plattformrollen dürfen diese
zentralen Kataloge im normalen Plattformbetrieb verwalten. Diese dauerhaften
Katalog-Permissions werden technisch und in der Oberfläche strikt von Mandantendaten-
und Supportrechten getrennt. Katalogansichten laden keine operativen Mandantendaten.

Zentrale Katalogänderungen werden mit Akteur und Zeitpunkt auditiert. Bereits verwendete
Werte werden versioniert oder deaktiviert statt physisch gelöscht, damit historische
Stations- und Vorgangsdaten verständlich bleiben.

Jede Einsicht oder Änderung in Daten eines konkreten Mandanten läuft dagegen als
Just-in-Time-Supportzugriff:

1. Step-up-MFA
2. Auswahl von Mandant, Zweck und Ticket/Begründung
3. verpflichtende Freigabe durch den Partner für regulären Support
4. maximal acht Stunden Laufzeit und minimaler Scope
5. sichtbare Kennzeichnung der Support-Sitzung
6. unveränderliches Audit jeder Suche, Ansicht und jedes Exports
7. automatische Beendigung und nachträgliche Kontrolle

Ohne vorherige Partnerfreigabe ist nur ein gesonderter Break-glass-Zugriff bei einem
schweren Sicherheits- oder Systemvorfall zulässig, wenn das Warten auf die Freigabe den
Schaden voraussichtlich vergrößern würde. Er benötigt Step-up-MFA, Incident-ID,
Begründung, minimalen Scope, sofortige Partnerbenachrichtigung, automatische Beendigung
und unabhängige Nachkontrolle. Gewöhnlicher Support, Komfortzugriff und normale
Datenkorrekturen sind keine Break-glass-Gründe. Exporte bleiben im Notfallweg gesperrt.
Break-glass endet spätestens nach 60 Minuten. Supportgrants und Break-glass-Zugriffe
können nicht verlängert werden; weiterer Zugriff benötigt einen neuen Antrag und beim
regulären Support eine neue Partnerfreigabe.

## Pilotgeräte und Kassensystem

Der erste Pilot nutzt Android-MDEs und das Kassensystem TMS5000. Die MDE-Oberfläche wird
deshalb browserbasiert und Android-tauglich geplant. Vor einer TMS5000-Integration sind
verfügbare Schnittstellen, Datenhoheit, Herstellerfreigaben, Netzsegmentierung und ein
Testsystem zu klären. Der interne Kern bleibt herstellerneutral und bindet Kassensysteme
später über Adapter an.

## MDE und Offlinebetrieb

Ein MDE ist eine eigene Geräteidentität und genau einer Tankstelle zugeordnet. Nutzer
melden sich kurzzeitig per Personalnummer, QR oder NFC jeweils plus PIN an; persönliche
Sitzungen enden nach kurzer Inaktivität. Alle Identifikatoren werden über eine gemeinsame
Provider-Schnittstelle auf dieselbe interne Mitarbeiteridentität abgebildet. QR/NFC
enthalten nur zufällige, widerrufbare Kennungen. Für den Pilot werden verwaltete,
gepatchte Android-Geräte im Kioskmodus bevorzugt.

Offlinefähigkeit wird nicht global versprochen. Sie wird pro Workflow definiert:

- minimale Daten in IndexedDB;
- Operation Queue mit Client-UUID als Idempotenzschlüssel;
- Geräte- und Serverzeit sowie Synchronisationsstatus;
- serverseitige Neuvalidierung;
- explizite Konfliktregeln statt blindem Überschreiben;
- verschlüsselte, mengen- und zeitbegrenzte lokale Daten.

Für die Pilot-Zeiterfassung ist zusätzlich eine frische Offline-Anmeldung am registrierten
MDE Pflicht. Dafür benötigt das Gerät ein zeitlich begrenztes, signiertes Offline-
Berechtigungspaket mit ausschließlich den Mitarbeitenden der eigenen Station. Widerruf,
Geräteverlust, PIN-Prüfung, Ablaufzeit und Synchronisationskonflikte werden als eigene
Security-Entscheidung spezifiziert; ein dauerhaft autonomes Offline-Benutzerverzeichnis
ist ausgeschlossen.

## Verbindlicher Technologiestack

- Produktname: **Merlin**
- Backend/Anwendung: Laravel 13.x; genaue Minor-/Patch-Version wird beim Projektstart
  über Composer auf die dann aktuelle stabile Version festgeschrieben
- Administration: Filament 5.x mit Livewire 4 und Tailwind CSS 4
- Datenbank: MySQL 8.4 LTS
- Partner- und Plattformverwaltung: getrennte Filament-Panels
- Mitarbeiter- und MDE-Oberfläche: Laravel-basierte, maßgeschneiderte PWA; wegen des
  48-Stunden-Offlinebetriebs nicht ausschließlich serverabhängige Filament-/Livewire-UI
- Queue/Cache: Redis und Laravel Queues
- Dateien: S3-kompatibler EU-Objektspeicher
- Verträge/Schnittstellen: versionierte REST-API/OpenAPI, wo externe Clients sie benötigen
- Tests: PHPUnit/Pest, Laravel Feature Tests und Browser-/PWA-Tests

Abhängigkeiten werden im Lockfile reproduzierbar festgeschrieben. „Neueste Version“
bedeutet nicht automatische ungeprüfte Produktions-Upgrades; Updates durchlaufen Tests,
Security-Review und Staging.

## Betrieb

Getrennte Entwicklungs-, Staging- und Produktionsumgebungen, EU-Hosting, private
Datenbank, Secrets-Management, verschlüsselte Backups mit Restore-Tests, CI/CD mit
Freigabeschritt, WAF/Rate Limits und Malware-Prüfung für Uploads.

Technische Logs und fachliches Audit sind getrennt. Observability nutzt strukturierte
Logs ohne unnötige Personendaten, Metriken, Tracing und Alarmierung für Auth-Anomalien,
Backups und nicht synchronisierte MDE-Aktionen.
