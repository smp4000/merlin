# Modul-Blueprint: Partnerverwaltung

Status: `Fachlich freigegeben am 30.08.2026 – Umsetzung in freigegebenen Schnitten`

## 1. Zweck und Abgrenzung

Die Partnerverwaltung bildet den organisatorischen und vertraglichen Datenraum eines
Tankstellenpartners. Ein Partner kann ein einzelnes Betreiberunternehmen oder eine
Unternehmensgruppe mit mehreren rechtlich selbstständigen Gesellschaften sein.

Das Modul ermöglicht:

- Anlage eines isolierten Mandanten nach bestätigter Registrierung;
- Verwaltung des Partnerprofils und einer oder mehrerer rechtlicher Gesellschaften;
- Verwaltung des Inhabers und weiterer Mandantenadministratoren;
- Anzeige und Steuerung von Status, Trial und Modulfreischaltungen;
- mandantenweite Grundeinstellungen einschließlich Sprache und Farbschema;
- kontrollierte Supportfreigaben und nachvollziehbares Audit.

Nicht Bestandteil dieses Moduls sind Tankstellenstammdaten, Mitarbeiterfachdaten,
Schichtplanung, Zeiterfassung, Billing/Zahlung, Lieferanten, Kunden und operative
Fachmodule. Diese referenzieren den hier geschaffenen Mandantenkontext später nur.

## 2. Nutzer und Oberflächen

### Plattform-Panel

- `Platform Super Admin`: Partner-Metadaten, Lifecycle, Trialverlängerung und Entitlements;
- `Platform Support`: Supportanfrage und Nutzung eines freigegebenen Supportgrants;
- `Platform Security Auditor`: lesender Zugriff auf Security- und Supportaudit;
- `Platform Catalog Admin`: kein Zugriff auf die Partnerverwaltung.

Das Plattform-Panel zeigt ohne aktiven Supportgrant keine operativen Mandantendaten,
Steuerkennungen, Dokumente oder personenbezogenen Inhalte. Gesellschafts- und
Stationsanzahlen dürfen nur als technische Metadaten angezeigt werden, sofern dadurch
keine vertraulichen Inhalte offengelegt werden.

### Partner-Panel

- `Tenant Owner`: vollständige Eigentums- und Delegationshoheit im eigenen Mandanten;
- `Tenant Administrator`: breite Verwaltung ohne Eigentumsübertragung, Löschantrag oder
  reservierte Hochrisikorechte;
- spätere eigene Rollen nur mit ausdrücklich freigegebenen Permissions.

Stationsleitungen erhalten keine allgemeine Partnerverwaltung. Sie sehen später nur
die für ihre operative Aufgabe notwendigen Unternehmensbezeichnungen.

## 3. Bestätigte Anforderungen

- Ein Mandant kann ein Einzelunternehmen oder eine Unternehmensgruppe abbilden.
- Ein Mandant kann mehrere rechtliche Gesellschaften enthalten.
- Registrierung wird erst nach E-Mail-Bestätigung wirksam.
- E-Mail-Bestätigung erzeugt atomar Mandant, autoritative Owner-Zuordnung,
  Administrator-Membership und 14-Tage-Trial.
- Wiederholte Bestätigung darf keinen zweiten Mandanten erzeugen.
- Nach Trialablauf gilt Nur-Lesen; Lesen und zulässige Exporte bleiben möglich.
- Ein Super-Admin darf den Trial genau einmal mit Begründung um 14 Tage verlängern.
- Partner dürfen ihre Gesellschaften selbst anlegen.
- Deutsch ist Fallbacksprache; die technische Grundlage ist mehrsprachig.
- Der Partner wählt ein geprüftes mandantenweites Farbschema.
- Super-Admin-Zugriff auf konkrete Inhaltsdaten ist standardmäßig gesperrt.
- Regulärer Supportzugriff benötigt Partnerfreigabe, Step-up-MFA, Zweck, Scope,
  Zeitlimit und vollständiges Audit.

## 4. Empfohlener fachlicher Umfang für den ersten Ausbau

### Registrierung

Das öffentliche Registrierungsformular fragt zunächst nur ab:

- geschäftliche E-Mail-Adresse;
- Vor- und Nachname der registrierenden Person;
- gewünschter Anzeigename des Partners;
- Land und bevorzugte Sprache;
- Partnerart `single_operator` oder `company_group`;
- versionierte Zustimmung zu den erforderlichen Vertrags- und Datenschutzhinweisen.

Rechtsform, Anschrift und Steuerkennungen werden erst im geschützten Onboarding erfasst.
Dadurch bleiben unbestätigte Registrierungen datenarm.

### Partnerprofil

- Anzeigename;
- interne Partnernummer, durch Merlin erzeugt und unveränderlich;
- Typ `single_operator` oder `company_group`;
- Status, Standardsprache, erlaubte Sprachen, Zeitzone und Land;
- primäre geschäftliche Kontaktadresse;
- gewähltes Farbschema;
- Onboardingfortschritt.

### Rechtliche Gesellschaft

Mindestens eine rechtliche Gesellschaft ist erforderlich, bevor eine Tankstelle
aktiviert werden kann. Vorgesehene Feldgruppen:

#### Allgemein

- juristische Firmierung;
- optionaler Handels-/Anzeigename;
- zentrale, versionierte Rechtsform;
- Kennzeichnung als Hauptgesellschaft;
- Status `draft`, `active` oder `inactive`;
- Gründungs- beziehungsweise Wirksamkeitsdatum optional.

#### Anschrift

- Straße, Hausnummer, Adresszusatz;
- Postleitzahl, Ort, Bundesland/Region und Land;
- abweichende Postanschrift optional.

#### Register und Behördenkennungen

- Registerart, Registergericht und Registernummer optional;
- Umsatzsteuer-ID optional;
- nationale Steuernummer optional;
- Wirtschafts-Identifikationsnummer optional;
- Betriebsnummer/Arbeitgeberkennung optional;
- weitere Kennungen als typisierte, nicht frei als Passwort verwendbare Einträge.

Steuer- und Behördenkennungen sind vertraulich, werden verschlüsselt gespeichert,
standardmäßig maskiert und nur mit granularer Hochrisiko-Permission vollständig angezeigt.
Sie erscheinen weder vollständig im Audit noch in technischen Logs.

#### Geschäftlicher Kontakt

- allgemeine E-Mail-Adresse;
- Telefon und optionale Faxnummer;
- Kontaktperson nur, wenn fachlich erforderlich;
- Website optional.

Bankverbindungen werden nach der späteren Produktentscheidung im geschützten Onboarding
optional aufgenommen. Schutz, IBAN-Assistent und Bundesbank-Verzeichnis sind im
Blueprint `19-onboarding-bankverzeichnis-iban-blueprint.md` festgelegt. Sonstige
Zahlungsabwicklung und hochgeladene Vertragsdokumente sind nicht Teil dieses Ausbaus.

### Inhaber und Administratoren

- Der Registrierende wird zunächst `Tenant Owner`.
- Ownership wird ausschließlich über die verpflichtende `owner_user_id` des Mandanten
  abgebildet; die zugehörige Zugangsmembership verwendet die Systemrolle
  `Tenant Administrator` und ist keine zweite Owner-Wahrheit.
- Der Owner kann weitere `Tenant Administrator` per Einladungslink hinzufügen.
- Einladungen sind einmalig, widerrufbar, zeitlich begrenzt und offenbaren keine
  Mitgliedschaften derselben Identität bei anderen Mandanten.
- Owner-Rolle, Eigentumsübertragung und Mandantenlöschung sind nicht delegierbar.
- Das Entfernen des letzten aktiven Owners ist technisch ausgeschlossen.

### Erscheinungsbild

Unter `Einstellungen → Erscheinungsbild` stehen `Merlin Petrol`, `Ozeanblau`,
`Waldgrün`, `Violett`, `Koralle` und `Graphit` mit Live-Vorschau bereit. Gespeichert wird
nur ein geprüfter `theme_key`. Statusfarben und Mindestkontraste bleiben zentral.

## 5. Seiteninventar und Navigation

### Plattform-Panel: `Partner`

#### Partnerliste

- Suche nach Partnernummer und Anzeigename;
- Filter nach Status, Trialstatus, Land und Onboardingstand;
- Spalten: Partnernummer, Anzeigename, Typ, Status, Trialende, Gesellschaftsanzahl,
  Erstellungsdatum;
- keine Vorschau vertraulicher Kennungen oder operativer Daten.

#### Partnerdetail

Tabs:

1. `Übersicht`: Metadaten, Status, Onboarding und technische Hinweise;
2. `Trial & Lifecycle`: Laufzeit, Nur-Lesen, einmalige Verlängerung;
3. `Module`: Entitlements ohne Preismodell;
4. `Support`: Anfragen und Grants;
5. `Audit`: Lifecycle-, Security- und Supportereignisse.

Kritische Plattformaktionen zeigen Vorher/Nachher, verlangen Grund und gegebenenfalls
Step-up-MFA. Ein Einstieg in operative Daten ist nicht Teil der normalen Detailseite.

### Partner-Panel: `Unternehmen`

#### Unternehmensübersicht

- Partnerprofil und Onboardingfortschritt;
- Hauptgesellschaft und weitere Gesellschaften;
- Trial-/Nur-Lesen-Hinweis;
- offene Administrator-Einladungen;
- Schnellzugriff auf Einstellungen.

#### Gesellschaftsliste

- Status, Firmierung, Rechtsform, Ort, Hauptgesellschaft;
- Erstellen, bearbeiten, deaktivieren und Verlauf anzeigen;
- referenzierte Gesellschaften werden nicht physisch gelöscht.

#### Gesellschaftsformular

Tabs: `Allgemein` · `Anschrift` · `Register & Kennungen` · `Kontakt` · `Prüfen`

- Entwurf kann gespeichert werden;
- Tabtitel zeigen Fehler und Vollständigkeit;
- vertrauliche Werte werden nach dem Speichern maskiert;
- Aktivierung benötigt eine vollständige Zusammenfassung;
- mobil wechseln Tabs in einen Stepper beziehungsweise ein Akkordeon.

### Partner-Panel: `Einstellungen`

Für diesen Ausbau:

- `Allgemein und Standardwerte`;
- `Sprache und regionale Darstellung`;
- `Erscheinungsbild und Farbschema`;
- `Administratoren und Einladungen`;
- `Supportfreigaben`.

Weitere Settings erscheinen erst mit dem jeweiligen Fachmodul.

## 6. Zustände und Übergänge

### Registrierung

`started → email_pending → confirmed | expired | revoked`

- Nur `confirmed` darf Tenant und Owner erzeugen.
- Bestätigung ist idempotent und transaktional.
- Abgelaufene oder widerrufene Registrierung erzeugt keinen Zugriff.

### Mandant

`onboarding → active → read_only → closure_requested → closed`

Zusätzlicher Sicherheitszustand `suspended` kann den Zugang bei Sicherheitsvorfällen
sperren. Eine normale fachliche Deaktivierung darf nicht als Sicherheits-Sperre
missbraucht werden.

### Trial

`active → extended_once → read_only`

- `active` endet automatisch nach 14 Tagen;
- `extended_once` endet genau 14 Tage nach dem vorherigen Trialende;
- kein zweites Verlängern, kein beliebiges Enddatum;
- Ablauf und Verlängerung werden unveränderlich auditiert.

### Rechtliche Gesellschaft

`draft → active → inactive`

- Entwurf darf unvollständig sein;
- Aktivierung erfordert alle Pflichtfelder;
- `inactive` bleibt für historische Referenzen erhalten;
- Reaktivierung ist eine auditierte Statusänderung.

### Membership und Einladung

- Membership: `invited → active → suspended | ended`;
- Einladung: `created → opened → accepted | expired | revoked`;
- Annahme einer Einladung erzeugt niemals automatisch Einblick in andere Mandanten.

## 7. Datenmodell

| Entität | Zweck und zentrale Felder |
|---|---|
| `Tenant` | `id`, `public_id`, `display_name`, `type`, `status`, `country_code`, `default_locale`, `timezone`, Zeitstempel |
| `LegalEntity` | `id`, unveränderliche `tenant_id`, `legal_form_id`, Firmierung, Anzeigename, Hauptkennzeichen, Status, Anschrift, Wirksamkeitsdatum |
| `LegalEntityIdentifier` | `tenant_id`, `legal_entity_id`, typisierter Identifier, verschlüsselter Wert, maskierte Anzeige, Land, Gültigkeit |
| `TenantMembership` | `tenant_id`, globale `user_id`, Status, Beginn/Ende, Sperrgrundreferenz |
| `RoleAssignment` | `tenant_id`, Membership, System-/Custom-Rolle, Scope, Gültigkeit |
| `RegistrationIntent` | E-Mail, minimale Registrierungsdaten, Token-Hash, Locale, Ablauf, Status |
| `ConsentRecord` | Vorlagenkennung, Version, Zweck, Zeitpunkt und notwendige Nachweis-Metadaten |
| `Trial` | `tenant_id`, Beginn, Ende, Status, Verlängerungszähler, Begründungsreferenz |
| `TenantAppearanceSetting` | `tenant_id`, geprüfter `theme_key`, Änderungszeitpunkt |
| `AuditEvent` | Tenant, Akteur, Aktion, Objekt, Zeitpunkt, sichere Änderungsmetadaten |

Alle mandantenbezogenen Tabellen besitzen eine nicht-nullfähige, unveränderliche
`tenant_id`. Fremdschlüssel und Eindeutigkeiten werden soweit möglich mit `tenant_id`
zusammengesetzt. Frei übertragene `tenant_id` wird nie als Autorisierungsnachweis genutzt.

## 8. Validierung und Eindeutigkeit

- `public_id` ist global eindeutig und nicht erratbar;
- Anzeigename ist nicht global eindeutig, da unterschiedliche Betreiber gleich heißen können;
- genau eine Hauptgesellschaft je aktivem Mandanten;
- Kennungen werden innerhalb von Typ, Land, Tenant und Gültigkeitszeitraum auf fachlich
  sinnvolle Eindeutigkeit geprüft;
- globale Dublettenprüfungen vertraulicher Kennungen dürfen keine Existenz eines fremden
  Mandanten offenlegen;
- E-Mail wird kanonisch normalisiert, aber nicht als globale Partneridentität verwendet;
- Sprache muss aktiviert, Zeitzone eine IANA-Zeitzone und Land ein unterstützter
  ISO-Ländercode sein;
- `theme_key` muss einem freigegebenen `ThemePalette`-Wert entsprechen;
- deaktivierte Rechtsformen dürfen für neue Gesellschaften nicht gewählt werden, bleiben
  aber historisch sichtbar.

## 9. Permissions

### Plattform

- `platform.tenant.read_metadata`
- `platform.tenant.manage_lifecycle`
- `platform.trial.extend`
- `platform.entitlement.manage`
- `platform.support_access.request`
- `platform.support_access.use`
- `platform.security_audit.read`

### Partner

- `tenant.profile.read`, `tenant.profile.update`
- `tenant.membership.read`, `tenant.membership.invite`, `tenant.membership.suspend`
- `tenant.ownership.transfer`, `tenant.deletion.request`
- `legal_entity.read`, `legal_entity.create`, `legal_entity.update`
- `legal_entity.activate`, `legal_entity.deactivate`
- `legal_entity.identifier.read_masked`, `legal_entity.identifier.manage`
- `legal_entity.tax_identifier.read_full`, `legal_entity.tax_identifier.manage`
- `settings.general.read`, `settings.general.manage`
- `settings.appearance.read`, `settings.appearance.manage`
- `audit.tenant.read`

Vollständige Steuerkennungen, Eigentumsübertragung und Löschanträge sind
Hochrisikoaktionen. UI-Ausblendung ersetzt keine serverseitige Policy.

## 10. Trial- und Nur-Lesen-Regel

Im Nur-Lesen-Modus bleiben zulässig:

- Partnerprofil, Gesellschaften, Audit und Trialstatus lesen;
- zulässige bestehende Daten exportieren;
- Supportanfrage stellen;
- Sicherheitsaktionen wie Passwortwechsel, MFA, Sitzungswiderruf und Datenexport für
  Betroffenenrechte durchführen.

Gesperrt werden:

- neue Gesellschaft, Membership oder Einladung;
- fachliche Profil-, Kennungs-, Rollen-, Theme- oder Einstellungsänderung;
- neue operative Daten in späteren Modulen.

Die Sperre liegt zentral in der Anwendungslogik und Policy, nicht nur in Filament.

## 11. Benachrichtigungen

Verbindlich:

- E-Mail-Bestätigung der Registrierung;
- Benachrichtigung bei Trialablauf und Wechsel in Nur-Lesen;
- Benachrichtigung über Trialverlängerung;
- Administrator-Einladung, Widerruf und Ablauf;
- Benachrichtigung über Sperrung, Owner-Wechsel und Supportfreigaben.

Trialhinweise werden 7, 3 und 1 Tag vor Ablauf versendet. Inhalte verwenden die
Empfängersprache und enthalten keine vertraulichen Kennungen.

## 12. Audit

Unveränderlich protokolliert werden mindestens:

- Registrierung bestätigt und Mandant erzeugt;
- Partnerprofil und Gesellschaft angelegt, geändert, aktiviert oder deaktiviert;
- vertrauliche Kennung angelegt, geändert oder vollständig angezeigt, jedoch nie ihr Wert;
- Membership eingeladen, aktiviert, gesperrt oder beendet;
- Rollen- und Owner-Änderung;
- Theme, Sprache und regionale Einstellungen geändert;
- Trial abgelaufen oder einmalig verlängert;
- Lifecycle-, Export-, Support- und Löschaktion.

Audit speichert Änderungskategorie und sichere Vorher-/Nachher-Metadaten. Passwörter,
Tokens, vollständige Steuerwerte und unnötige personenbezogene Freitexte sind verboten.

## 13. DSGVO und Aufbewahrung

Datenkategorien:

- B2B-Unternehmens- und Vertragsstammdaten;
- personenbezogene Daten des Owners, der Administratoren und gegebenenfalls eines
  Einzelunternehmers;
- vertrauliche Steuer-/Behördenkennungen;
- Sicherheits-, Zustimmungs- und Auditdaten.

Voraussichtlich ist Merlin für Registrierung, Vertrag und eigene Sicherheitsverwaltung
selbst Verantwortlicher und für spätere operative Partnerdaten Auftragsverarbeiter. Diese
Rollen, Rechtsgrundlagen und Informationspflichten müssen vor Go-live juristisch bestätigt
werden.

Unbestätigte Registrierungen werden nach einer noch festzulegenden kurzen Frist gelöscht.
Geschlossene Mandanten werden zunächst gesperrt und anschließend je Zweck und gesetzlicher
Pflicht gelöscht oder zugriffsbeschränkt archiviert. Konkrete Fristen bleiben Bestandteil
der juristisch geprüften Löschmatrix. Backups folgen zeitversetzten Löschläufen.

DSFA-Screening ist erforderlich; eine vollständige DSFA ist für reine Partnerstammdaten
nicht automatisch angenommen, muss aber zusammen mit Beschäftigtendaten und späteren
Modulen neu bewertet werden.

## 14. Fehler- und Missbrauchsfälle

- manipulierte oder wiederholte E-Mail-Bestätigung;
- Erraten fremder Partner- oder Gesellschafts-IDs;
- Mass Assignment einer fremden `tenant_id` oder reservierten Rolle;
- globales Suchen nach E-Mail, Steuer-ID oder Registernummer zur Fremdmandantenerkennung;
- Partner versucht Trialende, Status oder Entitlements selbst zu verändern;
- Super-Admin versucht ohne Supportgrant operative Inhalte zu laden;
- Administrator versucht Owner-Rechte oder eigenen Scope zu erweitern;
- letzter Owner wird entfernt oder gesperrt;
- Nur-Lesen wird über API, Batch, Import, Queue oder alte Browserseite umgangen;
- vertrauliche Kennung erscheint in URL, Log, Audit oder Exception;
- deaktivierte Gesellschaft wird einer neuen Station zugeordnet;
- abgelaufene Einladung wird erneut verwendet.

Erwartung: `403/404` ohne Existenzoffenlegung, keine Seiteneffekte und angemessenes
Security-Audit.

## 15. Offline, Export und Integration

Die Partnerverwaltung benötigt keine Offline-Schreibfunktion. Bei Netzausfall zeigt das
Backoffice einen klaren Fehlerzustand und speichert keine scheinbar erfolgreichen lokalen
Änderungen.

Im ersten Ausbau existiert kein Fremdsystemadapter. Ein Partnerdatenexport wird als
asynchroner, tenant-gebundener Vorgang vorbereitet. Vollständige Mandantenexporte und
Steuerkennungen benötigen Hochrisikorecht, Step-up-MFA, kurzlebige Downloads und Audit.

## 16. Umsetzungsschnitte nach Freigabe

1. zentrale TenantContext-Auflösung und tenant-sichere Query-/Policy-Basis;
2. Registrierung, E-Mail-Bestätigung und atomare Tenant-/Owner-/Trial-Anlage;
3. Trial-Lifecycle und zentraler Nur-Lesen-Guard;
4. Plattform-Partnerliste und Metadatenansicht;
5. Partnerprofil und Legal-Entity-Verwaltung im Tab-Design;
6. Owner-/Administrator-Membership und Einladung;
7. Sprache, regionale Einstellungen und Farbschema;
8. Audit, Benachrichtigungen, Exporte und kontrollierter Supportzugriff;
9. Security-, Tenant-, Rollen-, Browser- und Barrierefreiheitstests.

## 17. Test- und Abnahmematrix

### Positive Tests

- bestätigte Registrierung erzeugt genau einen Tenant, Owner und Trial;
- Owner legt Einzelunternehmen oder Gruppe mit mehreren Gesellschaften an;
- Entwurf speichert unvollständige Gesellschaft, Aktivierung verlangt Pflichtfelder;
- Partner ändert Sprache und Farbschema nur im eigenen Tenant;
- Administrator-Einladung kann angenommen und widerrufen werden;
- Trialablauf setzt automatisch Nur-Lesen;
- einmalige Verlängerung um genau 14 Tage funktioniert mit Grund und Audit.

### Negative und Securitytests

- jede fremde ID in URL, Formular, Suche, Relation, Batch und Export wird abgewiesen;
- Registrierungscallback ist idempotent und gegen Tokenmissbrauch geschützt;
- Partner kann Status, Trial, Entitlements und reservierte Rollen nicht verändern;
- Administrator kann Owner weder entfernen noch ersetzen;
- vollständige Steuerkennung ist ohne Hochrisikorecht nicht abrufbar;
- Logs, Exceptions und Audit enthalten keine vollständigen Kennungen oder Tokens;
- Nur-Lesen blockiert Schreiben über UI, HTTP, Queue, Import und direkte Action;
- Plattformrollen ohne aktiven Supportgrant erhalten keine operativen Daten;
- Mandantenwechsel leert Berechtigungs-, Cache- und Formularzustand vollständig.

### UX und Barrierefreiheit

- Desktop, Tablet und Mobilansicht funktionieren ohne horizontales Formularscrollen;
- alle Felder, Tabs, Fehler und Status sind per Tastatur und Screenreader erreichbar;
- Tabfehler werden im Tabtitel und in einer verlinkten Fehlerübersicht angezeigt;
- Maskierung ist verständlich und vollständige Anzeige benötigt bewusste Aktion;
- Nur-Lesen, Supportmodus und Sicherheits-Sperre sind visuell eindeutig unterscheidbar;
- jedes Farbschema erfüllt die definierten Kontrastanforderungen.

## 18. Messbare Akzeptanzkriterien

- kein Request kann Daten eines nicht gebundenen Tenants lesen oder verändern;
- wiederholte E-Mail-Bestätigung erzeugt niemals Duplikate;
- der Trialstatus wird spätestens durch den ersten Request nach Ablauf oder einen
  planmäßigen Job auf Nur-Lesen gesetzt;
- sämtliche fachlichen Schreibpfade respektieren den Nur-Lesen-Guard;
- genau eine Hauptgesellschaft ist aktiv, bevor die erste Station aktiviert wird;
- vertrauliche Kennungen sind verschlüsselt, standardmäßig maskiert und nicht geloggt;
- Super-Admin sieht ohne Grant ausschließlich definierte Partner-Metadaten;
- Farbschema, Sprache und Einstellungen wirken ausschließlich im aktiven Tenant;
- alle definierten Positiv-, Cross-Tenant-, Rollen- und Securitytests bestehen;
- keine kritischen oder hohen Securitybefunde sind offen.

## 19. Freigegebene Produktentscheidungen

- Ein Super-Admin darf zusätzlich zur Selbstregistrierung einen Partner manuell anlegen
  und dem vorgesehenen Owner eine sichere Einladung senden.
- Jeder Mandant besitzt genau einen aktiven `Tenant Owner`; weitere verantwortliche
  Personen erhalten `Tenant Administrator` oder später eine eigene Partnerrolle.
- Die öffentliche Registrierung bleibt datenarm. Anschrift, Rechtsform und vertrauliche
  Kennungen werden erst nach Anmeldung im geschützten Onboarding erfasst.
- Ein Lösch- beziehungsweise Schließungsantrag setzt zuerst `closure_requested` und wird
  durch den Plattformbetreiber geprüft; es gibt keine Sofortlöschung aus der Oberfläche.
- Trial-Erinnerungen werden 7, 3 und 1 Tag vor Ablauf versendet.
- Der Bestätigungslink ist 60 Minuten gültig. Sein GET-Aufruf zeigt nur die lokale
  Bestätigungsseite; erst ein CSRF-geschützter POST legt Benutzer, Mandant und Trial an.
- Das Passwort wird erst auf der Bestätigungsseite festgelegt und niemals im
  Registrierungsvorgang gespeichert.
- Unbestätigte Registrierungsvorgänge werden nach sieben Tagen gelöscht.
- Bereits bekannte E-Mail-Adressen erhalten dieselbe neutrale Antwort wie neue Adressen;
  Konten werden weder überschrieben noch automatisch zusammengeführt.
- Vertragsbestätigung und Kenntnisnahme der Datenschutzhinweise werden getrennt,
  zweckgebunden und mit unveränderlicher Dokumentversion dokumentiert.

Der Auftraggeber hat diese Entscheidungen und den Blueprint am 30.08.2026 schriftlich
freigegeben. Anwendungscode wird weiterhin ausschließlich in den definierten,
einzeln prüfbaren Umsetzungsschnitten erstellt.
