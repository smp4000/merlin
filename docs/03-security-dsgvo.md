# Security- und DSGVO-Baseline

Diese Unterlage ist eine technische und organisatorische Planungsbasis, keine
Rechtsberatung. Vor Pilot- und Produktivbetrieb sind Datenschutzbeauftragte bzw.
Fachjuristen und – soweit relevant – Betriebsrat einzubeziehen.

## Verantwortlichkeiten

Voraussichtlich ist der Tankstellenpartner Verantwortlicher für seine Mitarbeiter-,
Lieferanten- und Kundendaten; der SaaS-Anbieter ist dafür Auftragsverarbeiter. Für
Landingpage, Vertrag, Abrechnung und eigene Sicherheitsverwaltung kann der Anbieter
selbst Verantwortlicher sein. Das wird vor Go-live in AVV, Datenschutzhinweisen und
Verzeichnis der Verarbeitungstätigkeiten verbindlich geklärt.

Eigene Produktanalyse, Benchmarking oder KI-Training mit Mandantendaten sind ohne
gesonderte Rechtsgrundlage und transparente Vereinbarung ausgeschlossen.

## Privacy Module Check

Vor Freigabe jedes Moduls werden dokumentiert:

- Zweck, Datenfelder und betroffene Personen;
- Rechtsgrundlage und Verantwortlichkeit;
- Rollen, Stationen und Zugriffspfade;
- Empfänger, Unterauftragnehmer und Drittlandtransfers;
- Speicher- und Löschfristen;
- Audit-, Export- und Betroffenenrechte;
- Missbrauchsfälle und DSFA-Entscheidung.

## Datenschutz durch Voreinstellung

- Datenminimierung bei Personalstammdaten;
- keine Diagnose-Freitexte bei Krankheit;
- keine permanente GPS-Ortung und keine Biometrie im MVP;
- Push-Nachrichten ohne sensible Sperrbildschirm-Inhalte;
- kein verdecktes Leistungs- oder Verhaltensranking;
- unveränderliche Historie statt Überschreiben von Zeit- und Prüfereignissen;
- differenzierte Löschung statt pauschaler unbegrenzter Aufbewahrung.

Eine Person kann dieselbe globale Identität bei mehreren unabhängigen Mandanten nutzen.
Jeder Partner sieht ausschließlich seinen eigenen Mitarbeiter-, Beschäftigungs-, Rollen-
und Zeitdatensatz. Dass weitere Memberships existieren, wird anderen Partnern nicht
offengelegt. Einladungen dürfen kein globales Personenverzeichnis durchsuchen und keine
automatische Verknüpfung anhand personenbezogener Ähnlichkeiten durchführen.

Das Ende einer Beschäftigung sperrt nur die betroffene TenantMembership beziehungsweise
deren Fachzugang. Die globale Identität bleibt bestehen, solange eine andere aktive
Membership oder ein anderer zulässiger Kontozweck besteht. Löschung und Datenexport
werden deshalb getrennt für Identitätskonto und jeden Tenant-Fachdatensatz geplant.

## Super-Admin-Kontrollen

Zentrale Konfiguration und Mandantendateneinsicht sind getrennte Permissions. Der
reguläre Zugriff auf globale Kataloge wie Brands, Kraftstoffsorten, Rechtsformen und
Moduldefinitionen benötigt keinen zeitlich begrenzten Mandantenzugriff. Er darf jedoch
keine operativen Mandantendaten laden und jede Katalogänderung wird auditiert.

Der Super-Admin-Zugriff auf Inhaltsdaten ist `deny by default`. Regulärer Supportzugriff
erfordert Step-up-MFA, Begründung, höchstens acht Stunden Laufzeit, Mandant/Scope,
vollständiges Audit und vorherige Freigabe durch den Partner. Exporte erfordern eine
zusätzliche Bestätigung.

Break-glass ohne vorherige Partnerfreigabe ist ausschließlich bei einem schweren
Sicherheits- oder Systemvorfall zulässig, wenn eine Verzögerung den Schaden voraussichtlich
vergrößert. Er benötigt Incident-ID, minimalen Scope, sofortige Partner- und Security-
Benachrichtigung, automatische Beendigung und verpflichtende unabhängige Nachkontrolle.
Exporte sind über Break-glass nicht zulässig. Break-glass endet spätestens nach 60
Minuten. Eine Verlängerung ist ausgeschlossen; weiterer Zugriff benötigt einen neuen
Vorgang und eine erneute Prüfung der Voraussetzungen.

## Konfigurierbare MFA-Richtlinie

Für den normalen Login ist MFA pro Plattform- beziehungsweise Partnerrolle als
`disabled`, `optional` oder `required` einstellbar. Der Pilot startet für Plattform-Admins,
Partner und Stationsleitungen mit `disabled`. Diese Entscheidung erhöht das Risiko einer
Kontoübernahme und muss vor Produktivfreigabe im Security-Review erneut bewertet werden.

Die Einstellung betrifft ausschließlich den normalen Login. Step-up-MFA für
Mandantensupport, Break-glass, Payroll-Exporte und weitere ausdrücklich festgelegte
Hochrisikoaktionen bleibt verpflichtend und kann durch Partnerrollen nicht deaktiviert
werden. Änderungen der MFA-Richtlinie werden auditiert; Recovery benötigt einen eigenen,
missbrauchsresistenten Prozess.

## MDE/BYOD

- MDE per MDM, Verschlüsselung, Patchmanagement und Kioskmodus;
- keine geteilten Personenkonten;
- sicherer Benutzerwechsel ohne Restdaten;
- widerrufbare Geräte und Sitzungen;
- minimale, zeitbegrenzte Offline-Daten mit Replay-/Duplikatschutz;
- keine privaten Kontakte, Werbe-IDs oder Gerätefingerprints;
- kein Fernlöschen privater BYOD-Daten;
- Push-Tokens als personenbezogene Daten behandeln.

Eine Offline-Anmeldung verwendet nur vorab synchronisierte, pseudonyme und zeitlich
begrenzte Stationsberechtigungen. Widerrufe wirken bei vollständig getrennten Geräten
erst beim nächsten Kontakt oder spätestens durch Ablauf des Offline-Grants. Dieses
Restrisiko wird durch höchstens 24 Stunden Standardlaufzeit (dokumentiert maximal
72 Stunden), starke PIN-Sperren, Android-Keystore/StrongBox, minimale lokale Daten und
physische Gerätesicherung begrenzt und muss vom Mandanten ausdrücklich akzeptiert werden.

Payroll-Exporte benötigen ein eigenes Hochrisikorecht, Step-up-MFA, Datenminimierung,
kurzlebige personengebundene Downloads und ein Audit von Erzeugung, Freigabe, Download
und Löschung. Keine Payroll-Datei wird unverschlüsselt per E-Mail versandt.

## Löschmatrix

Vor Go-live wird pro Datenkategorie eine juristisch geprüfte Frist festgelegt. Benötigt
werden mindestens Regeln für unbestätigte Registrierungen, Einladungen, Mitarbeiterdaten,
Zeitbuchungen, Nachrichten, MHD/HACCP, Inventuren, Audit, technische Logs, Push-Tokens,
Dateien und Backups. Austritt sperrt den Zugang sofort; anschließend werden Daten je
Zweck gelöscht oder zugriffsbeschränkt archiviert.

## TOMs und Release-Gates

- TLS, Verschlüsselung ruhender Daten und geregelte Schlüsselrotation;
- technisch verfügbare und rollenbezogen konfigurierbare MFA/Passkeys; erneute
  Risikofreigabe vor Produktivbetrieb;
- Joiner/Mover/Leaver-Prozess und regelmäßige Rechte-Rezertifizierung;
- Secret-, Dependency-, SAST- und IaC-Scans;
- Vier-Augen-Review und unabhängiger Penetrationstest vor Pilotbetrieb;
- verschlüsselte Backups und nachgewiesene Restore-Tests;
- Incident-Playbooks für Tenant-Durchgriff, Kontoübernahme, MDE-Verlust,
  Datenexport, Ransomware und kompromittierte Unterauftragnehmer;
- keine offenen kritischen oder hohen Security-Befunde bei Release.

## DSGVO-Artefakte vor Pilotbetrieb

- AV-Vertrag und TOM-Anlage
- Unterauftragnehmerliste und Transferbewertung
- Verzeichnis der Verarbeitungstätigkeiten
- Datenschutzinformationen und versionierte Einwilligungs-/Vertragsnachweise
- Lösch- und Aufbewahrungskonzept
- Betroffenenrechte-Prozess
- Incident-/Data-Breach-Prozess
- DSFA-Screening pro Modul
- Betriebsrats-/arbeitsrechtliche Prüfung für Zeit, Ortung und Kommunikation
