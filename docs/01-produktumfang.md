# Produktumfang

## Zielbild

Die Plattform bedient zunächst Deutschland und bildet folgende Hierarchie ab. Ein
Mandant kann sowohl ein einzelnes Betreiberunternehmen als auch eine Unternehmensgruppe
mit mehreren rechtlichen Gesellschaften repräsentieren:

```text
Plattformbetreiber
└── Mandant / Tankstellenpartner
    ├── Rechtliche Einheiten
    ├── Tankstellen
    ├── Mitarbeiter
    │   └── eine oder mehrere Tankstellenzuordnungen
    ├── Rollen und Rechte
    ├── registrierte MDE-Geräte
    └── einzeln aktivierbare Module
```

Ein Mitarbeiter gehört im ersten Produktstand genau einem Mandanten. Sein konkretes
Arbeitsverhältnis wird einer rechtlichen Gesellschaft zugeordnet; innerhalb des Mandanten
kann er mehreren Tankstellen mit jeweils eigener Rolle und Gültigkeitsdauer zugeordnet
werden. Mandantenübergreifende Beschäftigung ist nicht Bestandteil des MVP.

## Nutzeroberflächen

1. Öffentliche Landingpage mit Registrierung und 14-Tage-Testphase
2. Plattformverwaltung für Super-Admins
3. Partner-/Administrationsportal
4. Mitarbeiter-PWA für private und dienstliche Smartphones
5. MDE-/Kiosk-PWA für ein registriertes Stationsgerät
6. Lieferantenportal – erst nach eigener fachlicher Discovery
7. Kundenportal – erst nach Klärung des Kundentyps und Nutzens

Windows Phone wird nicht als eigene Plattform unterstützt. Die Kernfunktionen werden
als responsive PWA für aktuelle Android-, iOS- und Desktop-Browser geplant. Native
Android-/iOS-Apps folgen nur, wenn Push, Offlinebetrieb oder Geräteschnittstellen dies
nach einem Hardware-Pilot erfordern.

Alle Oberflächen werden von Beginn an mehrsprachig aufgebaut. Deutsch ist die
Standardsprache; Benutzer können eine persönliche Sprache wählen, während ein Partner
eine Mandanten-Standardsprache festlegt. Die verbindliche Liste der Sprachen für den
ersten Release ist noch festzulegen.

## Pilotkontext

- Pilotbetreiber: eigener Betrieb des Auftraggebers
- Umfang: zwei Aral-Tankstellen mit zusammen etwa 25 Mitarbeitenden
- Kassensystem: TMS5000
- Stationsgerät: Android-MDE
- Hardwarefamilien: Zebra, Nerugged und Netum mit NFC und QR-/Barcodescanner;
  konkrete Modelle und Android-Versionen noch zu erfassen
- beide Stationen liegen unter einem Einzelunternehmen in Hessen
- Beschäftigungsarten: Vollzeit, Teilzeit, Minijob, Auszubildende und Minderjährige
- eine Station arbeitet rund um die Uhr
- bezahlte Unterbrechungen und Alleinarbeit kommen im Pilot vor; währenddessen müssen
  Mitarbeitende weiter Kunden/Kasse betreuen, weshalb keine automatische Wertung als
  gesetzliche Ruhepause erfolgt
- größte Problemfelder: Personalplanung, MHD und Dokumentation
- erste Produktreihenfolge: Partnerverwaltung, Tankstellenverwaltung,
  Mitarbeiterverwaltung und Zeiterfassung
- Mitarbeiterstammdaten liegen noch nicht digital vor und werden im Pilot neu erfasst

Die TMS5000-Anbindung ist zunächst ein Discovery-Thema. Das Plattformfundament und die
erste Zeiterfassung dürfen nicht von einer Kassenschnittstelle abhängig sein.

Das Produkt bleibt über den Pilot hinaus konfigurierbar: Ein Mandant kann mehrere
rechtliche Einheiten unterschiedlicher Rechtsformen führen, Stationen können in allen
deutschen Bundesländern liegen, und Tarif-/Betriebsvereinbarungen sowie Mitbestimmung
werden je Arbeitgeber und Beschäftigtengruppe abgebildet.

## Plattform-MVP

- Registrierung, E-Mail-Verifizierung und versionierte Zustimmungen
- Anlage eines isolierten Mandanten und einer 14-tägigen Testphase
- Unternehmens- und Tankstellenverwaltung
- zentraler, nur durch Plattformrollen änderbarer Brand-Katalog
- Mitarbeiteranlage, Einladung, Sperrung und Austritt
- Mehrfachzuordnung zu Tankstellen mit Gültigkeitszeitraum
- hybrides Rollen-/Rechtesystem
- Basis-Onboarding für Mitarbeiter
- Modulfreischaltungen als Entitlements
- Geräteverwaltung für MDE/Kiosk
- Auditprotokoll und kontrollierter Supportzugriff
- mehrsprachige Oberflächen, E-Mails, Benachrichtigungen und erzeugte Dokumente
- Datenexport, Einschränkung und geregelte Löschung
- Basis-Zeiterfassung für den Pilot

Das MVP ist erfolgreich, wenn der Pilotpartner seine zwei Tankstellen und etwa 25
Mitarbeitenden verwalten, individuelle Rollen vergeben, ein Android-MDE sicher einer
Station zuordnen und Arbeitszeiten nachvollziehbar erfassen und korrigieren kann. Noch
nicht enthalten sind Zahlungsabwicklung, vollständige Personal-/Schichtplanung und die
weiteren operativen Fachmodule.

## Rollenmodell

Das Modell kombiniert unveränderliche Plattformrechte mit frei definierbaren
Mandantenrollen.

### Plattformebene

- `Platform Super Admin`: Plattformkonfiguration, Mandantenverwaltung und kontrollierte
  Einsicht in Mandantendaten
- `Platform Catalog Admin`: zentrale Stammdaten wie Brands, Modulkatalog und globale
  Vorlagen; keine automatische Einsicht in Mitarbeiterdaten
- `Platform Support`: ausschließlich freigegebener, zeitlich begrenzter Supportzugriff
- `Platform Auditor`: lesender Zugriff auf Security- und Support-Audits

### Mandantenebene

Geschützte Basisrollen: Inhaber und Mandantenadministrator. Partner können zusätzliche
Rollen anlegen, benennen und aus einem zentralen Permission-Katalog zusammenstellen,
zum Beispiel Regionalleitung, Stationsleitung, Schichtleitung, Personalverwaltung,
HACCP-Verantwortung oder Mitarbeiter.

Eine Berechtigungsentscheidung lautet stets:

```text
Permission erlaubt
AND Modul aktiv
AND aktive Mandantenmitgliedschaft
AND erlaubte Tankstelle/Ressource
AND gültiger Zeitraum
AND keine explizite Sperre
```

Eigene Rollen dürfen keine Plattformrechte, Abrechnungshoheit, fremde Mandantenrechte
oder reservierte System-Permissions enthalten. Rechteänderungen werden serverseitig
validiert und auditiert.

## Spätere Module

Jedes Modul erhält eigene Discovery, Datenklassifikation, Rechte, Audit-Ereignisse,
Löschregeln, Offlinekonflikte und Pilotabnahme:

1. Aufgaben, Checklisten und Nachrichten
2. MHD-Prüfungen und Folgemaßnahmen
3. Zeiterfassung und Korrekturfreigaben
4. Schichtplanung, Verfügbarkeit, Urlaub und Krankheit
5. Abschreibungen mit konfigurierbaren Freigabeschwellen
6. Inventuren und Zählkonflikte
7. HACCP, Grenzwerte, Abweichungen und Korrekturmaßnahmen
8. Unterweisungen und Dokumentenmanagement
9. Lieferantenportal
10. Kundenportal
11. Kassen-, Warenwirtschafts- und Lohnintegrationen

## Trial-Lifecycle

Von Beginn an werden `trial_start`, `trial_end`, Status und Entitlements modelliert.
Nach erfolgreicher E-Mail-Bestätigung werden Mandant und 14-tägige Testphase automatisch
aktiviert. Der Partner kann seine Gesellschaft und Tankstellen anschließend selbst
anlegen. Nach Ablauf des 14. Tages wechselt der Mandant automatisch in den Nur-Lese-Modus.
Vorhandene Daten bleiben sichtbar und exportierbar; neue Datensätze und fachliche
Änderungen sind gesperrt. Ein Super-Admin darf die Testphase genau einmal mit Begründung
um weitere 14 Tage verlängern. Die Verlängerung wird vollständig auditiert. Preise,
Zahlungsabwicklung und der spätere Übergang in ein Abonnement sind nicht Teil des MVP.
