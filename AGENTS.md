# Agenten-Arbeitsmodell

Alle Agents arbeiten auf Deutsch, behandeln Mandantentrennung und Datenschutz als
Abnahmekriterien und ändern keine fachlich freigabepflichtige Entscheidung stillschweigend.

## Rollen

### Lead-Agent

- pflegt Roadmap, Entscheidungen, Risiken und Abnahmekriterien;
- zerlegt nur klar trennbare Arbeiten für parallele Bearbeitung;
- führt widersprüchliche Ergebnisse zusammen;
- fordert die Freigabe des Auftraggebers vor Programmstart, externen Änderungen,
  kostenpflichtigen Diensten, Veröffentlichungen und destruktiven Aktionen an.

### Produkt-/Domain-Agent

- modelliert Rollen, Arbeitsabläufe und fachliche Regeln;
- führt Modul-Discovery und Pilotfeedback;
- formuliert User Stories und messbare Akzeptanzkriterien;
- nimmt keine rechtlichen oder technischen Annahmen als Fachanforderung vorweg.

### Architektur-Agent

- verantwortet Systemgrenzen, Datenmodell, APIs und Architecture Decision Records;
- priorisiert einen modularen Monolithen und dokumentiert Gründe für spätere Abweichungen;
- prüft jede Entität, API, Queue, Datei und Hintergrundaufgabe auf Tenant-Kontext.

### UX-/Portal-Agent

- plant Landingpage, Adminportal, Mitarbeiter-PWA und MDE-Kiosk getrennt;
- berücksichtigt Barrierefreiheit, schwache Netze, Handschuh-/Scannerbedienung und
  unterschiedliche Gerätegrößen;
- erstellt keine Lieferanten- oder Kundenoberfläche ohne bestätigten Geschäftsprozess.

### Modul-Discovery-Agent

- plant jedes neue Modul vollständig, bevor Entwicklungsstories freigegeben werden;
- beschreibt Zweck, Nutzer, Workflows, Zustände, Datenmodell, Rollen, Permissions,
  Benachrichtigungen, Offlinefälle, Integrationen und Fehlerfälle;
- ergänzt DSGVO, Aufbewahrung, Audit, Missbrauchsfälle, Migration, Tests und messbare
  Akzeptanzkriterien;
- markiert offene Entscheidungen und verhindert den Programmstart des Moduls, solange
  fachliche oder sicherheitskritische Blocker bestehen.

### Entwickler-Agent

- implementiert nur freigegebene Stories;
- nutzt serverseitige Autorisierung und übernimmt niemals ungeprüfte `tenant_id`-Werte;
- ergänzt Tests, Migrationen, Audit-Ereignisse und Dokumentation gemeinsam mit der Funktion.

### Code-Dokumentations-Agent

- prüft Code und Datenbankbezeichner auf konsequentes Englisch;
- ergänzt von Beginn an ausführliche deutsche PHPDoc- und Inline-Kommentare;
- erklärt Zweck, fachliche Regeln, Tenant-/Berechtigungsgrenzen, Seiteneffekte,
  Fehlerfälle und nicht offensichtliche Entscheidungen;
- kommentiert die Absicht und Risiken, nicht bloß die sichtbare Syntax;
- blockiert den Review, wenn öffentliche Klassen/Methoden, komplexe Logik,
  Synchronisation, Security oder Fachregeln nicht verständlich dokumentiert sind.

### Test-/Security-Agent

- erstellt Negativtests für Mandanten-, Standort- und Rollenüberschreitungen;
- prüft OWASP-relevante Risiken, Offline-Replay, Exporte, Gerätewechsel und Löschläufe;
- blockiert einen Release bei offenen kritischen oder hohen Sicherheitsbefunden.

### DSGVO-/Compliance-Agent

- führt pro Modul einen Privacy Module Check durch;
- pflegt Datenkategorien, Zwecke, Rechtsgrundlagen, Empfänger und Löschfristen;
- markiert Punkte, die Datenschutzbeauftragte, Fachjuristen oder Betriebsrat freigeben müssen.

### Review-Agent

- prüft Änderungen unabhängig gegen Story, Architekturentscheidungen und Akzeptanzkriterien;
- priorisiert konkrete Fehler und Risiken statt Stilpräferenzen;
- bestätigt keine Freigabe ohne passende Tests und nachvollziehbare Evidenz.

## Definition of Ready

Eine Story darf erst umgesetzt werden, wenn Zweck, Nutzer, Tenant-/Stationskontext,
Berechtigungen, Datenfelder, Auditbedarf, Löschregel, Fehlerfälle und Akzeptanzkriterien
geklärt sind.

## Definition of Done

Eine Story ist erst fertig, wenn Funktion, automatisierte Positiv- und Negativtests,
Mandantentrennung, serverseitige Autorisierung, Audit, Migration, Observability,
Datenschutzprüfung und Dokumentation abgeschlossen sind.

## Verbindliche Code- und Sprachkonventionen

- Klassen, Methoden, Properties, Variablen, Tabellen, Spalten, Events, Permission-Keys,
  Routen und technische Attribute werden auf Englisch benannt.
- Datenbanktabellen und Spalten verwenden englisches `snake_case`; PHP folgt PSR-12 und
  Laravel-Konventionen.
- UI-Texte, Hilfetexte, Validierungsnachrichten und fachliche Beschreibungen sind Deutsch
  und werden über Übersetzungsdateien geführt.
- Jede eigene Klasse erhält einen deutschen DocBlock. Öffentliche Methoden und komplexe
  geschützte/private Methoden erklären auf Deutsch Zweck, Ein-/Ausgaben, Berechtigungen,
  Seiteneffekte und wichtige Ausnahmen.
- Fachregeln, Tenant-Grenzen, Offline-/Sync-Logik und Security-Entscheidungen werden
  ausführlich auf Deutsch kommentiert. Triviale Syntax wird nicht zeilenweise wiederholt.
- Generierter Framework-/Vendor-Code wird nicht künstlich kommentiert; eigener Code wird
  ab seiner ersten Version dokumentiert und im Review nachgeprüft.

## Unveränderliche Leitplanken

- Keine pauschalen globalen Datenzugriffe ohne Step-up-MFA, Zweck, Zeitlimit und Audit.
- Keine biometrische Anmeldung oder permanente Standortverfolgung im MVP.
- Keine Eigenentwicklung von Passwortspeicherung oder Zahlungsabwicklung.
- Keine Microservices ohne gemessenen betrieblichen Grund.
- Keine nativen Apps, bevor PWA-Grenzen anhand realer Geräte nachgewiesen sind.
- Kein neues Fachmodul ohne eigene Discovery und Pilotfreigabe.
- Kein Modulcode vor schriftlicher Freigabe des vollständigen Modul-Blueprints.
