# Modul-Blueprint: Partnerverwaltung

Status: `Fachlich freigegeben am 30.08.2026 – Ist-Abgleich und nächster Schnitt am 31.08.2026 ergänzt`

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

Das geschützte Erst-Onboarding darf als einmaliger Bootstrap bereits die erste
Rechtsgesellschaft, die erste Tankstelle, deren Kontakt und optional eine Bankverbindung
anlegen. Die anschließende Pflege zusätzlicher Tankstellen und stationsspezifischer
Stammdaten bleibt trotzdem Bestandteil der späteren Tankstellenverwaltung.

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

## 20. Ist-Abgleich vom 31.08.2026

### Bereits umgesetzt und geprüft

- Registrierung, neutrale E-Mail-Antwort, Bestätigungsseite und idempotente Anlage von
  Benutzer, Tenant, aktiver Membership und 14-Tage-Trial;
- sichere manuelle Partner-Einladung aus der Plattformliste;
- zentrale Auflösung eines aktiven TenantContext ausschließlich über eine wirksame
  Membership sowie ein zentraler fachlicher Nur-Lesen-Guard;
- geschütztes Erst-Onboarding für Hauptgesellschaft, erste Tankstelle,
  Stationskontakt und optionale verschlüsselte Bankverbindung;
- Bundesbank-Verzeichnis, deutsche IBAN-Prüfung und IBAN-Berechnung als Eingabehilfe;
- datenarme Plattform-Partnerliste;
- mandantenbezogene Dashboard-Fortschrittsanzeige ohne Raten eines Tenants bei mehreren
  aktiven Memberships;
- getrenntes Partner-Panel unter `/admin` und Plattform-Panel unter `/platform`;
- bewusste Betriebsauswahl bei mehreren wirksamen Memberships sowie eindeutige
  Auto-Auswahl bei genau einer Membership;
- dauerhaft sichtbarer aktiver Betrieb im Kopfbereich des Partner-Panels;
- automatisierte Tests für Registrierung, Onboarding, Plattform-Einladung,
  TenantContext, Paneltrennung, Betriebsauswahl, Nur-Lesen-Grundlage, IBAN und Dashboard.

### Noch nicht umgesetzt

- Partnerprofil- und Gesellschaftsverwaltung nach dem Erst-Onboarding;
- zentrale, versionierte Rechtsformen und typisierte Gesellschaftskennungen;
- Datenbankgarantie für genau eine Hauptgesellschaft pro Tenant;
- vollständige Policies und granulare Permissions der Partnerverwaltung;
- Administrator-Einladungen, Rollenvergabe und Ownership-Transfer;
- Trial-Ablaufjob, einmalige Verlängerungsaktion und Erinnerungsnachrichten;
- mandantenweite Sprachen, Farbschema und weitere Partner-Settings;
- SupportAccessGrant, Exporte, Löschworkflow und vollständige Fach-Auditansichten.

### Erkannte Modellabweichungen, die vor dem Gesellschaftsformular bereinigt werden

- `legal_entities.legal_form` ist derzeit ein freier Text. Ziel ist eine Referenz auf
  einen zentralen, deaktivierbaren und historisch stabilen Rechtsformkatalog.
- Umsatzsteuer-ID liegt derzeit direkt an `legal_entities`. Ziel ist ein typisiertes,
  verschlüsseltes Identifier-Modell für USt-ID, Steuernummer, Wirtschafts-ID,
  Register- und Arbeitgeberkennungen.
- Anschrift und Rechnungsanschrift sind im Bootstrap noch nicht als wiederverwendbare,
  eindeutig benannte Feldgruppen modelliert. Die Migration muss bestehende Pilotdaten
  verlustfrei übernehmen.
- `is_primary` besitzt bisher nur einen Index. Genau eine aktive Hauptgesellschaft muss
  zusätzlich transaktional und mit Datenbankunterstützung abgesichert werden.
- Tenant-Sprache und Theme sind bisher nur Grundwerte beziehungsweise globale
  Designvorgaben; erlaubte Sprachen und mandantenweite Palette fehlen noch.

## 21. Nächster freizugebender Umsetzungsschnitt: Partnerkern

Der nächste Schnitt wird bewusst vor Administratoren, Rollen, Trial-Automation und
Supportzugriff abgeschlossen. Er besteht aus drei aufeinander aufbauenden Lieferpaketen.

### Paket A: Paneltrennung und autoritativer Arbeitskontext – umgesetzt am 31.08.2026

- Partner-Panel bleibt unter `/admin`, damit bestehende Login- und Onboardinglinks stabil
  bleiben.
- Plattform-Panel erhält den Pfad `/platform` und enthält ausschließlich globale
  Plattformressourcen wie Partner-Metadaten, Brands und Bankverzeichnis.
- Partnerbenutzer ohne Plattformrolle können das Plattform-Panel weder sehen noch direkt
  aufrufen; Plattformrollen erhalten aus ihrer Rolle allein keinen Mandanteninhalt.
- Das Partner-Panel verlangt nach Anmeldung genau einen wirksamen TenantContext. Bei
  mehreren Memberships erscheint vor dem Dashboard eine bewusste Betriebsauswahl.
- Tenantwechsel verwirft Navigation, gecachte Permissions, Formularzustände und spätere
  Stationsauswahl. Eine frei übertragene Tenant-ID gilt nie als Berechtigung.
- Onboarding, Dashboard und alle folgenden Partnerressourcen verwenden denselben
  Resolver; parallele, abweichende Tenantauflösungen werden nicht eingeführt.

Abnahme Paket A:

- direkte Aufrufe des jeweils fremden Panels liefern eine neutrale Ablehnung;
- ein Benutzer mit Memberships in zwei Tenants sieht niemals gemischte Daten;
- ein Benutzer mit genau einer Membership gelangt ohne unnötigen Zwischenschritt zum
  eigenen Dashboard;
- ein Plattform-Super-Admin sieht ohne Supportgrant keine Gesellschafts-, Bank- oder
  Stationsinhalte;
- bestehende Registrierungs-, Bestätigungs- und Onboardinglinks funktionieren weiter.

Umsetzungsnachweis:

- das Partner-Panel registriert keine Plattformressourcen;
- das Plattform-Panel registriert ausschließlich explizite globale Ressourcen;
- Panelzugriff prüft E-Mail-Bestätigung und danach getrennt Plattformrolle oder aktuell
  wirksame Partner-Membership;
- jede Partneranfrage bindet denselben zentral geprüften TenantContext;
- ein abgelaufener oder widerrufener alter Kontext führt zur erneuten bewussten Auswahl
  und niemals zu einem stillen Wechsel auf einen anderen Tenant;
- Cross-Panel-, Cross-Tenant-, Fremd-ULID-, Mehrfachmembership- und Ablaufprüfungen sind
  automatisiert abgedeckt.

### Paket B: belastbares Partner- und Gesellschaftsmodell – umgesetzt am 31.08.2026

Neue beziehungsweise erweiterte fachliche Strukturen:

- `LegalForm`: zentraler Katalog mit stabilem Schlüssel, lokalisierten Bezeichnungen,
  Ländern, Status und Gültigkeit; referenzierte Einträge werden deaktiviert statt gelöscht;
- `LegalEntity`: öffentliche ULID, `tenant_id`, Rechtsformreferenz, Firmierung,
  Handelsname, Status, Hauptkennzeichen, Wirksamkeitsdatum und strukturierte Geschäfts-
  sowie optionale Postanschrift;
- `LegalEntityIdentifier`: `tenant_id`, Gesellschaft, Typ, Land, verschlüsselter Wert,
  maskierte Anzeige, Fingerprint, Gültigkeit und Status;
- `TenantBusinessContact`: primäre geschäftliche E-Mail, Telefon, optionale Website und
  nur fachlich erforderliche Kontaktperson;
- Tenant-Erweiterung für Onboardingstand und später anschließbare Settings, ohne Trial,
  Entitlements oder Owner-Verantwortung in frei änderbare Partnerfelder zu mischen.

Verbindliche Identifier-Typen des ersten Schnitts:

- `vat_id` für Umsatzsteuer-ID;
- `national_tax_number` für nationale Steuernummer;
- `economic_id` für Wirtschafts-Identifikationsnummer;
- `commercial_register` für Registernummer einschließlich Registerart und Gericht;
- `employer_number` für Betriebsnummer beziehungsweise Arbeitgeberkennung.

Validierung und Schutz:

- jede Kindentität erhält eine unveränderliche `tenant_id` und tenant-gebundene
  Eindeutigkeiten;
- Aktivierung einer Gesellschaft verlangt Firmierung, aktive Rechtsform, vollständige
  Geschäftsanschrift, Land und geschäftliche Kontaktadresse;
- Entwürfe dürfen unvollständig gespeichert werden;
- es existiert genau eine aktive Hauptgesellschaft; Wechsel erfolgt transaktional;
- bestehende Onboardingdaten werden in einer vorwärtsgerichteten Migration übernommen;
  eine bestehende USt-ID wird entschlüsselt, in das Identifier-Modell übertragen und nie
  im Migrationslog ausgegeben;
- vorhandene freie Rechtsformtexte werden normalisiert einem Katalogeintrag zugeordnet;
  nicht sicher zuordenbare Werte bleiben als geschützter Legacy-Anzeigetext erhalten und
  müssen vor der nächsten fachlichen Änderung bewusst bestätigt werden;
- Kennungswerte sind verschlüsselt, standardmäßig maskiert und weder suchbar noch in URL,
  Exception, Audit oder Queue-Payload vollständig enthalten;
- Dublettenprüfung erfolgt nur tenant-intern über einen kontextgebundenen Fingerprint und
  offenbart niemals Treffer anderer Mandanten.

Abnahme Paket B:

- bestehende Pilotdaten bleiben vollständig erhalten und dem richtigen Tenant zugeordnet;
- fremde Tenant-, Gesellschafts-, Rechtsform- und Identifier-IDs werden ohne
  Existenzoffenlegung abgewiesen;
- Datenbank- und Servicetests verhindern null oder mehrere aktive Hauptgesellschaften
  nach einer abgeschlossenen Statusänderung;
- deaktivierte Rechtsformen bleiben an Bestandsdaten sichtbar, sind aber nicht neu
  auswählbar;
- vollständige Kennungen erscheinen in keinem Log-, Audit- oder Fehlertexttest.

Umsetzungsnachweis:

- ein zentraler, idempotenter DACH-Rechtsformkatalog enthält stabile Schlüssel,
  lokalisierte Bezeichnungen, Länder, Status und zeitliche Gültigkeit;
- Gesellschaften besitzen öffentliche ULIDs, Katalogreferenz, Handelsname,
  Wirksamkeitsdatum, strukturierte Geschäfts- und optionale Postanschrift sowie einen
  datenbankgestützten eindeutigen Hauptgesellschafts-Guard;
- der primäre Geschäftskontakt ist tenantgebunden und hält Personenfelder bewusst
  optional;
- USt-ID, nationale Steuernummer, Wirtschafts-ID, Handelsregister- und Arbeitgeberkennung
  werden typisiert, verschlüsselt, maskiert und über einen tenantgebundenen HMAC-
  Fingerprint auf Dubletten geprüft;
- Anlage, Hauptgesellschaftswechsel, Statuswechsel und Kennungsspeicherung erfolgen nur
  über TenantContext- und Nur-Lesen-geschützte Anwendungsdienste;
- die Vorwärtsmigration normalisiert bekannte Altrechtsformen, erhält unbekannte
  Legacytexte zur Bestätigung, migriert vorhandene verschlüsselte USt-IDs ohne Ausgabe
  ihres Klarwerts und normalisiert widersprüchliche Hauptmarkierungen deterministisch;
- zusammengesetzte Fremdschlüssel verhindern Kennungszuordnungen zu Gesellschaften eines
  anderen Mandanten bereits auf Datenbankebene;
- automatisierte Tests decken Migration, Rückwärtskompatibilität, Katalog-Idempotenz,
  Aktivierungsregeln, Hauptgesellschaft, Verschlüsselung, maskierte Fehlertexte,
  Cross-Tenant-Angriffe und Nur-Lesen ab;
- PHPUnit erzwingt unabhängig von lokalen `.env`-Werten eine flüchtige SQLite-Datenbank;
  ein separater Isolationstest stoppt den Lauf, falls diese Schutzgrenze verloren geht.

### Paket C: Partneroberfläche `Unternehmen`

Navigation und Seiten:

- `Unternehmen → Übersicht` mit Partnernummer, Anzeigename, Typ, Hauptgesellschaft,
  weiteren Gesellschaften, Onboardingstand und Trialhinweis;
- `Unternehmen → Gesellschaften` mit tenant-sicherer Liste, Statusfiltern und Aktionen
  für Entwurf, Bearbeitung, Aktivierung, Deaktivierung und Verlauf;
- Formular-Tabs `Allgemein`, `Anschrift`, `Register & Kennungen`, `Kontakt`, `Prüfen`;
- sichtbare Fehler- und Vollständigkeitszustände je Tab, Fehlerübersicht mit Fokusziel,
  Entwurfsspeicherung und responsive Stepper-/Akkordeondarstellung;
- gespeicherte Kennungen werden maskiert. Eine vollständige Anzeige wird in diesem
  Schnitt noch nicht angeboten, bis Step-up-MFA und Hochrisikopermission umgesetzt sind;
- Partnerprofiländerungen und Gesellschaftsaktionen verwenden Services und Policies,
  nicht direkte ungeschützte Filament-Modellschreibvorgänge.

Erste Rollenfreigabe dieses Pakets:

- Tenant Owner darf eigenes Partnerprofil und eigene Gesellschaften lesen und ändern;
- die bestehende Administrator-Membership erhält vor Einführung des vollständigen
  Rollensystems ausschließlich den ausdrücklich definierten Partnerverwaltungsumfang;
- Stationsleitungen erhalten keinen Zugriff auf diese Navigation;
- Nur-Lesen-Tenants dürfen alle zulässigen Seiten sehen, aber keine fachliche Aktion
  speichern, auch nicht über direkte URLs oder alte Formularzustände.

Abnahme Paket C:

- Browser-, Policy- und Featuretests decken Erstellen, Entwurf, Aktivieren, Deaktivieren,
  Hauptgesellschaftswechsel und Nur-Lesen ab;
- Cross-Tenant-Tests manipulieren Route, Formular, Relation, Suche, Filter und
  Stapelaktion;
- Formulare sind per Tastatur bedienbar, melden Fehler verständlich auf Deutsch und
  funktionieren auf Desktop, Tablet und Mobilgerät ohne horizontales Scrollen;
- englische technische Bezeichner sowie ausführliche deutsche PHPDoc- und gezielte
  Inline-Kommentare dokumentieren insbesondere Tenantgrenzen, Kennungsschutz und
  Statusübergänge;
- vollständiger Laravel-Testlauf, Frontendtests und Produktions-Build sind grün;
- keine kritischen oder hohen Securitybefunde bleiben offen.

## 22. Bewusst nachgelagerte Partnerverwaltungs-Schnitte

Nach erfolgreicher Abnahme des Partnerkerns folgen jeweils separat geplant und geprüft:

1. Administrator-Einladungen, Membership-Lifecycle und sichere Ownership-Übertragung;
2. Rollen, eigene Partnerrollen, Delegationsgrenzen und zeitliche Vertretungen;
3. Trial-Automation, Erinnerungen, einmalige Verlängerung und Nur-Lesen-Kommunikation;
4. Sprache, erlaubte Sprachen, regionale Werte und mandantenweites Farbschema;
5. Auditansichten, asynchrone Exporte und Datenschutz-/Schließungsworkflow;
6. SupportAccessGrant und Break-glass mit Step-up-MFA, Scope und Zeitlimit.

Keiner dieser nachgelagerten Schnitte wird stillschweigend in den Partnerkern gezogen.
Jeder erhält vor Implementierung eigene Datenflüsse, Missbrauchsfälle, Tests und eine
ausdrückliche Freigabe.

### Freigegebener Pilotschnitt Erscheinungsbild

Auf ausdrücklichen Pilotwunsch ist aus Punkt 4 ausschließlich das mandantenweite
Farbschema vorgezogen. Es speichert einen geprüften `ThemePalette`-Schlüssel je Tenant,
ist zunächst nur für die wirksame Administrator-Membership sichtbar, respektiert den
zentralen Nur-Lesen-Schutz und auditiert alten sowie neuen Schlüssel. Freie HEX-Werte,
CSS oder JavaScript werden nicht akzeptiert. Die Anmeldung und das Plattform-Panel bleiben
neutral; Statusfarben ändern sich nicht. Sprache und regionale Werte bleiben weiterhin
ein eigener nachgelagerter Schnitt.
