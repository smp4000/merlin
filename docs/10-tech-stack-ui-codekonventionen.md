# Merlin – Technologiestack, UI und Codekonventionen

## Verbindlicher Stack

- Name: **Merlin**
- Laravel 13.x, aktuelle stabile Minor-/Patch-Version bei Projektinitialisierung
- Filament 5.x für Plattform- und Partner-Backoffice
- Livewire 4 und Tailwind CSS 4 gemäß Filament-Anforderungen
- MySQL 8.4 LTS für den produktiven Datenbestand
- Redis für Cache, Locks und Queues
- S3-kompatibler Objektspeicher in der EU

Die offiziellen Dokumentationen führen aktuell [Laravel 13.x](https://laravel.com/framework/docs/changelog)
und [Filament 5.x](https://filamentphp.com/docs/5.x/upgrade-guide). MySQL 8.4 wird als
[LTS-Produktionszweig](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)
eingesetzt. Exakte Versionen werden in `composer.lock` und der Infrastruktur fixiert.

## Oberflächen

### Filament-Panels

- Plattform-Panel für Super-Admin, Brands, zentrale Kataloge, Mandanten und Supportzugriff
- Partner-Panel für Gesellschaften, Tankstellen, Mitarbeiter, Rollen, Zeiten und Module

Mitarbeiter- und MDE-Funktionen werden als eigene Laravel-PWA umgesetzt. Filament und
Livewire bleiben für die Backoffice-Panels, da die MDE-PWA 48 Stunden offline arbeiten
muss und dafür lokalen Zustand, Service Worker und Synchronisationslogik benötigt.

## Eigenständiges Merlin-Design

Merlin verwendet ein eigenes Filament-Theme und soll optisch nicht wie ein unverändertes
Standard-Filament erscheinen. Vorgesehen sind:

- eigene Farbpalette, Typografie, Abstände, Radien, Schatten und Icon-Sprache;
- eigene Dashboardkarten, Empty States, Statusdarstellungen und Navigation;
- responsive Bedienung und klare Informationshierarchie;
- Barrierefreiheit, Tastaturbedienung und ausreichende Kontraste;
- konsistente Light-/Dark-Entscheidung nach UX-Konzept;
- keine Theme-Plugins als kritische Produktabhängigkeit; das Theme gehört Merlin.

Filament unterstützt dafür ein eigenes kompiliertes Tailwind-Theme. Design-Tokens und
wiederverwendbare Komponenten werden vor den Fachseiten definiert.

### Mandantenbezogene Farbauswahl

Der Partner kann unter `Einstellungen → Erscheinungsbild` aus mehreren von Merlin
geprüften Farbschemata wählen. Das gewählte Schema gilt mandantenweit für das
Partner-Panel und die Mitarbeiter-PWA. Die MDE-PWA übernimmt die Markenakzente nur dort,
wo Scanbarkeit, Gerätezustand und schnelle Bedienung nicht beeinträchtigt werden.

- Merlin liefert mindestens sechs benannte, übersetzbare Farbschemata aus, zum Beispiel
  `Merlin Petrol`, `Ozeanblau`, `Waldgrün`, `Violett`, `Koralle` und `Graphit`.
- Der Partner sieht vor dem Speichern eine Vorschau mit Navigation, Schaltflächen,
  Formularfeldern und Fokuszuständen und kann jederzeit auf den Merlin-Standard
  zurücksetzen.
- Im ersten Ausbau werden keine beliebigen HEX-Werte zugelassen. Jedes angebotene Schema
  wird für Hell-/Dunkelvarianten, Tastaturfokus und WCAG-Kontrast geprüft.
- Erfolgs-, Warn-, Fehler-, Informations- und Offlinefarben bleiben systemweit
  semantisch festgelegt und dürfen durch die Partnerfarbe nicht umgedeutet werden.
- Öffentliche Seiten, das Plattform-Panel und die Anmeldung vor Auswahl eines Mandanten
  verwenden das neutrale Merlin-Design. Das Mandantenschema wird erst nach sicherer
  Auflösung des `TenantContext` geladen.
- Die Änderung benötigt eine eigene Permission, wird mit altem und neuem Schema
  auditiert und wirkt nicht auf andere Mandanten.

## Formulare im Tab-Design

Größere Formulare verwenden ein frisches, responsives Tab-Layout mit klaren Gruppen,
beispielsweise `Allgemein`, `Adresse`, `Öffnungszeiten`, `Shop`, `Kennungen` und
`Dokumente`. Regeln:

- Tabs reduzieren Komplexität, verstecken aber keine Fehler oder Pflichtfelder.
- Tab-Titel zeigen Fehler-, Warnungs- und Vollständigkeitsstatus.
- Speichern als Entwurf ist jederzeit möglich.
- Auf Mobilgeräten wechseln Tabs bei Bedarf in Stepper/Akkordeon-Navigation.
- Kritische Änderungen erhalten Zusammenfassung und Bestätigung.
- Zustand und Eingaben bleiben beim Tabwechsel erhalten.

## Partner-Menüpunkt „Einstellungen“

Das Partner-Panel besitzt einen festen Menüpunkt `Einstellungen`. Darunter werden
mandantenweite Konfigurationen gebündelt:

- Unternehmen, Gesellschaften und Standardwerte
- Rollen und Berechtigungen
- Module und Entitlements
- Zeiterfassungs-, Pausen- und Korrekturregeln
- Einladungen, Onboarding und Benachrichtigungen
- Geräte- und Offline-Richtlinien
- Payroll-Exportprofile und bestätigte Steuerberater-Empfänger
- Datenschutz, Aufbewahrung und Audit-Einstellungen
- Integrationen und Credential-Referenzen
- später Abonnement und Abrechnung

Operative Datensätze wie einzelne Tankstellen oder Mitarbeiter bleiben in eigenen
Menüpunkten; Settings enthält ihre mandantenweiten Regeln, nicht sämtliche Datensätze.
Sichtbarkeit jeder Einstellungsseite folgt granularen Permissions.

## Sprache und Kommentare

Alle technischen Bezeichner sind Englisch:

- PHP-Klassen, Methoden, Properties und Variablen
- Tabellen, Spalten, Indizes und Constraints
- Routen, Events, Jobs, Commands, Policies und Permission-Keys
- API-Felder, Statuscodes und technische Konfiguration

Deutsche Inhalte:

- UI-Labels, Beschreibungen, Hilfetexte und Fehlermeldungen
- PHPDoc und Kommentare im eigenen Code
- Architektur- und Betriebsdokumentation

### Mehrsprachige Anwendung

- Kein sichtbarer Text wird dauerhaft in PHP-, Blade-, JavaScript- oder Filament-Code
  fest eingebaut; Labels, Hilfetexte, Validierung, Navigation und Statuswerte verwenden
  versionierte Übersetzungsschlüssel.
- Die wirksame Sprache wird in dieser Reihenfolge aufgelöst: persönliche
  Benutzereinstellung, Mandantenstandard, Systemstandard Deutsch.
- Öffentliche Landingpage und Registrierung können die Sprache bereits vor der Anmeldung
  wählen. Die Auswahl bleibt über Anmeldung und E-Mail-Bestätigung erhalten.
- E-Mails, Benachrichtigungen, PDFs und Exporte verwenden die Sprache des Empfängers oder
  des auslösenden Exportprofils. Nachweispflichtige Dokumente speichern zusätzlich
  verwendete Sprache, Vorlagenversion und Erzeugungszeitpunkt.
- Zentrale übersetzbare Katalogtexte erhalten bei Bedarf eigene Translation-Datensätze
  mit eindeutigem Schlüssel aus Objekt und Locale. Fachliche IDs und stabile Slugs sind
  niemals sprachabhängig.
- Freitexte von Partnern oder Mitarbeitenden bleiben in ihrer Eingabesprache und werden
  nicht unbemerkt automatisch übersetzt.
- Datum, Uhrzeit, Zahlen und Währungen werden lokal formatiert, intern jedoch kanonisch
  gespeichert. Zeitzone und Sprache bleiben getrennte Einstellungen.
- Fehlende Übersetzungen fallen kontrolliert auf Deutsch zurück und werden in Tests sowie
  Monitoring als Qualitätsfehler sichtbar.
- Übersetzungen verändern keine Berechtigungen, Tenant-Grenzen oder fachlichen Regeln.

Jede eigene Klasse und jeder nicht triviale Ablauf wird von Anfang an auf Deutsch
dokumentiert. Kommentare erklären das Warum, fachliche Regeln, Tenant-/Security-Grenzen,
Seiteneffekte und Ausnahmen. Sie wiederholen nicht lediglich die PHP-Syntax. Der
Code-Dokumentations-Agent kontrolliert dies in jedem Review.

## Modul-Gate

Vor jedem neuen Modul erstellt der Modul-Discovery-Agent einen vollständigen Blueprint:

1. Problem, Ziel, Nicht-Ziele und Pilotnutzer
2. Rollen, Permissions und Datenreichweite
3. Workflows, Zustandsautomaten und Sonderfälle
4. Datenmodell, Validierungen und Historisierung
5. UI-Seiten, Tabs, MDE/PWA und Barrierefreiheit
6. Audit, Datenschutz, Löschung und Missbrauchsfälle
7. Offline-/Sync-, Benachrichtigungs- und Integrationsverhalten
8. Migration, Rollout, Monitoring und Support
9. Positiv-, Negativ-, Tenant- und Securitytests
10. messbare Akzeptanzkriterien und offene Freigaben

Ohne schriftliche Freigabe dieses Blueprints beginnt keine Implementierung.
