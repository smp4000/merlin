# Mitarbeiter-Ersterfassung und Onboarding

## Ziel

Für den Pilot werden ca. 25 Mitarbeitende erstmals digital erfasst. Private E-Mail,
Mobilnummer und eigenes Smartphone sind vollständig optional. Zeiterfassung,
Pflicht-Onboarding, Pflichtnachrichten und Zugangswiederherstellung müssen über
betriebliche Wege und das Android-MDE möglich bleiben.

```text
Voreintrag + Einladungslink → Mitarbeiter ergänzt eigene Daten → Validierung
→ Freigabe durch Partner oder zuständige Stationsleitung
→ Credential-Ausgabe → Aktivierung für freigegebene Station(en)
```

Der Inhaber oder eine berechtigte Leitung legt zunächst einen minimalen Voreintrag mit
Name, Arbeitgeber und vorgesehener Station an. Der Mitarbeiter erhält einen einmaligen
Einladungslink und ergänzt nur die für ihn freigegebenen persönlichen Angaben. Arbeitgeber,
Stationen, Rollen, Schutzprofile und Freigaberechte werden ausschließlich durch
berechtigte Verwaltungsrollen festgelegt.

## Minimale Stammdaten

Pflicht:

- Personalnummer, je rechtlichem Arbeitgeber eindeutig
- Vorname, Nachname und MDE-Anzeigename
- rechtlicher Arbeitgeber und Beschäftigungsbeginn
- Status: vorbereitet, aktiv, ruhend oder ausgeschieden
- Beschäftigungsart
- mindestens eine gültige Stationszuordnung
- Basisrolle und zuständiger Korrekturprüfer
- erforderliches Schutz-/Regelprofil

Nur bei fachlicher Notwendigkeit:

- Beschäftigungsende, Wochenstunden und Kostenstelle
- Payroll-ID und Ziel-Lohnartenmapping
- Geburtsdatum oder datensparsamer Alters-/Minderjährigenstatus
- geschäftliche Kontaktdaten

Private E-Mail und Mobilnummer werden getrennt, freiwillig und entfernbar gespeichert.
Fehlende oder später entfernte private Kontaktdaten dürfen weder Aktivierung noch
MDE-Nutzung, Pflichtinformationen oder Recovery verhindern.
Anschrift, Bank-, Steuer- und Gesundheitsdaten gehören nicht in das Zeiterfassungsprofil.

## Beschäftigung, Stationen und Schutzprofile

Person und Beschäftigungsverhältnis bleiben getrennt. Jede Beschäftigung gehört zu einer
Legal Entity. Stationszuordnungen führen Zeitraum, Stamm-/Zusatzstation, Rolle,
Kostenstelle, erlaubte Buchungskanäle und Korrekturprüfer.

Regel-/Schutzprofile bilden mindestens Standard, minderjährig, Minijob und Ausbildung
ab. `Azubi`, `minderjährig`, `Vollzeit/Teilzeit` und `Minijob` sind getrennte Merkmale und
nicht gegenseitig ausschließend. Diagnosen und besondere Gesundheitsangaben werden nicht
als allgemeine Rollenmarker geführt.

## Beschäftigung bei unabhängigen Partnern

Eine `UserIdentity` kann mehreren unabhängigen Mandanten angehören. Jeder Mandant besitzt
einen eigenen `Employee` mit eigenen `Employment`-, Stations-, Rollen-, Schutzprofil-
und Zeitdaten. Kein Partner kann erkennen, suchen oder exportieren, ob dieselbe Identität
bei einem anderen Partner verwendet wird.

Bei einer Einladung kann der Mitarbeiter nach bestätigter Anmeldung selbst entscheiden,
ein vorhandenes Konto zu verwenden. Ohne private E-Mail erfolgt die Verknüpfung durch
persönlich übergebenen Einmalcode. Name, Personalnummer, Telefonnummer oder ähnliche
Stammdaten lösen niemals automatisch eine mandantenübergreifende Zusammenführung aus.
Nach der Anmeldung wählt der Mitarbeiter bewusst den Betrieb; jeder Wechsel erneuert den
Tenant-Kontext und die Berechtigungsprüfung.

Hat der Mitarbeiter im gewählten Betrieb mehrere aktuell gültige und freigegebene
Stationszuordnungen, wählt er anschließend bewusst die Station für seine operative
Sitzung. Bei genau einer Station wird sie automatisch gesetzt und sichtbar angezeigt.
Abgelaufene, noch nicht begonnene, gesperrte oder nicht freigegebene Zuordnungen werden
nicht angeboten. Die Auswahl selbst verleiht keine Berechtigung.

MDE-Personalnummern, PIN-Verifier, QR-/NFC-Kennungen und Offlinepakete bleiben Tenant- und
Stations-spezifisch. Eine Sperre oder ein Austritt bei Partner A sperrt Partner B nicht.

## Manuelle Anlage und Erfassungsvorlage

Ein Assistent unterstützt einzelne Neueinstellungen. Für die Erstmigration wird zusätzlich
eine kontrollierte Tabellen-Vorlage verwendet, obwohl noch keine digitale Quelle besteht.
Sie enthält Codes statt freier Rollen-/Stationsnamen:

- `personalnummer`, `vorname`, `nachname`, `anzeigename`
- `arbeitgeber_code`, `eintrittsdatum`, `austrittsdatum`
- `beschaeftigungsart`, `schutzprofil_code`, `wochenstunden_optional`
- `stammstation_code`, `weitere_station_codes`, `rollen_codes`
- `payroll_id_optional`, `sprache`
- `private_email_optional`, `private_mobilnummer_optional`

Harte Fehler wie doppelte Personalnummer, unbekannte Codes, ungültige Zeiträume, fehlende
Station oder mandantenfremde Zuordnung verhindern die Anlage. Dubletten, weitreichende
Rollen, fehlende Payroll-ID oder unpassendes Schutzprofil erzeugen Prüfhinweise. Vor dem
Import erscheint eine Vorschau; fehlerhafte Zeilen erzeugen keine halbfertigen Konten.

## Freigabe durch Partner oder Stationsleitung

Nach der Selbsterfassung genügt im Pilot eine Freigabe durch den Partner oder die für die
Station zuständige Stationsleitung. Der Partner kann alle vorgesehenen Stationen im
eigenen Mandanten gemeinsam freigeben. Eine Stationsleitung kann ausschließlich die ihr
zugeordneten Stationen bestätigen. Bei einer Mehrfachzuordnung führt jede Station einen
eigenen Freigabestatus. Nach der ersten bestätigten Station kann der Mitarbeiter für diese
Station aktiviert werden; weitere Stationen bleiben bis zu ihrer Freigabe gesperrt.

Freigeber verwenden persönliche Konten. Eine zeitlich aktive Vertretung darf freigeben,
wenn `employee.onboarding.review` ausdrücklich für die betreffende Station zugewiesen
wurde. Kritische Felder wie Arbeitgeber, Schutzprofil, Stationen und Adminrolle werden
hervorgehoben. Niemand darf die eigene Person, Rolle oder Stationsberechtigung freigeben.
Freigabe oder Ablehnung mit Begründung erzeugt eine versionierte Änderungsakte.
Plattform-Super-Admins sind nicht reguläre Freigeber von Personaldaten eines Partners.

## Aktivierung ohne private E-Mail

Mögliche Wege:

- einmaliger Einladungslink an einen freiwillig angegebenen Kontaktkanal
- versiegelter Ausdruck mit Einmalcode und QR
- persönliche NFC-Ausgabe plus temporärer PIN
- beaufsichtigte Aktivierung am MDE
- PWA-Aktivierung per Einmalcode und anschließend eigenem Passwort/Passkey
- optional E-Mail/SMS bei freiwillig angegebenem privaten Kontakt

Einmalcodes sind kurzlebig und einmalig. Leitungspersonen kennen keine endgültigen
Mitarbeiterpasswörter. Entfernen privater Kontakte sperrt den MDE-Zugang nicht.

Nach persönlicher MDE-Anmeldung steht ein geschütztes Mitarbeiter-Postfach für
Pflichtnachrichten, Freigaben und Korrekturentscheidungen bereit. Es zeigt keine Inhalte
vor der persönlichen Anmeldung und entfernt sie nach Sitzungsende aus der sichtbaren
Oberfläche. Erforderliche Kenntnisnahmen werden mit Nachrichten- und Versions-ID,
Zeitpunkt und Mitarbeiter protokolliert.

Recovery ohne private Kontaktdaten erfolgt durch Identitätsprüfung bei Partner oder
zuständiger Stationsleitung und Ausgabe eines kurzlebigen Einmalcodes beziehungsweise
eines versiegelten QR-Codes. Die ausgebende Person kann weder die endgültige PIN noch ein
endgültiges Passwort sehen oder festlegen. Ausgabe, Verwendung, Ablauf und Widerruf
werden auditiert.

Einladungslinks laufen nach einer konfigurierten Frist ab, können widerrufen und neu
ausgestellt werden und zeigen keine Personaldaten in der URL. Vor Aktivierung wird die
Identität durch einen zweiten Faktor oder persönliche Übergabe bestätigt. Ein Link allein
berechtigt weder zum Stempeln noch zum Einsehen fremder Stationsdaten.

## Pilotmigration

1. Einzelunternehmen, zwei Stationscodes und zentrale Aral-Brandzuordnung bestätigen.
2. Beschäftigungs-, Schutzprofil-, Rollen- und Kostenstellencodes festlegen.
3. Kontrollierte Vorlage aus vorhandenen Papier-/Vertragsquellen erfassen.
4. Zweite Person gleicht alle 25 Datensätze mit der verantwortlichen Quelle ab.
5. Testimport ohne Aktivierung durchführen und Fehler/Dubletten bereinigen.
6. Zuerst Leitungen und zwei bis vier Testmitarbeitende aktivieren.
7. MDE, 48-Stunden-Offlinepaket und Stationswechsel testen.
8. Restliche Mitarbeitende in kleinen Gruppen aktivieren.
9. Mitarbeitende bestätigen Name, Personalnummer, Arbeitgeber und Stationen.
10. Nach einer Pilotwoche erfolgt eine formelle Datenqualitätskontrolle.

Die Erfassungsvorlage wird anschließend gemäß Löschkonzept geschützt archiviert oder
gelöscht.

## Akzeptanzkriterien

- alle 25 Personen sind genau einmal oder bewusst mit getrennten Beschäftigungen vorhanden;
- jede aktive Person hat Arbeitgeber, Station, Rolle und passendes Schutzprofil;
- Mitarbeiter ohne Smartphone/private E-Mail können vollständig am MDE arbeiten;
- Mitarbeiter ohne private Kontaktdaten können Pflichtnachrichten persönlich abrufen und
  ihren Zugang über einen beaufsichtigten Einmalprozess wiederherstellen;
- kein Nachrichteninhalt eines Mitarbeiters bleibt nach dessen MDE-Sitzung für die nächste
  Person sichtbar;
- Mehrfachzuordnung erzeugt kein zweites Benutzerkonto;
- Mitarbeitende mit mehreren freigegebenen Stationen müssen vor operativen Aktionen eine
  Station wählen; Zeiten und Vorgänge werden genau dieser geprüften Station zugeordnet;
- eine abgelaufene oder widerrufene Stationszuordnung beendet auch einen noch in der
  Sitzung gespeicherten Stationskontext;
- ein Konto kann mit getrennten Mitarbeiterdatensätzen unabhängiger Mandanten verbunden
  sein, ohne deren Daten oder Mitgliedschaften gegenseitig offenzulegen;
- Austritt, Sperre, Rolle und MDE-Credential eines Mandanten beeinflussen keinen anderen;
- automatische Cross-Tenant-Zusammenführung anhand von Stammdaten ist ausgeschlossen;
- Importfehler aktivieren keine Teilkonten;
- Mitarbeiter-Selbsterfassung und Freigabe durch Partner, Stationsleitung oder berechtigte
  Vertretung sind getrennt auditiert;
- eine Stationsleitung kann keine Zuordnung zu einer fremden Station freigeben;
- bei mehreren Stationen wird nur für bereits freigegebene Stationen Zugriff erteilt;
- niemand genehmigt die eigene Rollen- oder Stationsberechtigung;
- private Kontakte sind optional und vom betrieblichen Zugang getrennt;
- MDEs erhalten nur minimale, freigegebene Identitäten für 48 Stunden Offlinebetrieb;
- Mitarbeitende sehen, wenn ein Gerät noch nicht synchronisierte Berechtigungen nutzt;
- bezahlte Arbeitsbereitschaft wird nicht als echte Ruhepause abgezogen;
- Erfassung, Korrektur, Freigabe und Credential-Ausgabe sind auditiert.
