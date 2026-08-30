# Permission-Katalog und Rollen

## Entscheidungsformel

```text
Permission
AND aktiver Tenant
AND aktive Membership
AND LegalEntity-/Stations-/Ressourcen-Scope
AND aktives Modul-Entitlement
AND gültiger Zeitraum
AND keine Sperre
AND ggf. Step-up-MFA / Vier-Augen-Freigabe
```

Scopes: `platform`, `tenant`, `legal_entity`, `station`, `assigned_stations`, `self`
und `specific_resource`. Partnerrollen verwenden keine Wildcards und dürfen niemals
mehr Rechte oder Scope vergeben, als der zuweisende Nutzer selbst besitzt.

Eine globale Identität kann mehrere Memberships haben, aber eine Autorisierungsprüfung
verwendet immer genau eine aktive Membership und genau einen gebundenen TenantContext.
Permissions verschiedener Mandanten werden niemals vereinigt. Die Existenz weiterer
Memberships ist keine für Partner lesbare Ressource.

## Verbindliche Delegationsregel

- Partner mit dem erforderlichen Delegationsrecht dürfen Rollen und Rechte innerhalb
  ihres eigenen Mandanten vergeben.
- Stationsleitungen mit dem erforderlichen Delegationsrecht dürfen Rollen und Rechte nur
  für die ihnen zugeordneten Stationen vergeben.
- Eine Stationsleitung darf weder andere Stationen noch den gesamten Mandanten oder eine
  vollständige Legal Entity als Reichweite vergeben.
- Jeder Zuweisende darf nur Permissions und Scopes weitergeben, die er selbst wirksam
  besitzt. Eine eigene Rechteausweitung oder Selbstgenehmigung ist ausgeschlossen.
- Plattformrechte, Eigentumsübertragung, Mandantenlöschung, mandantenweiter Gesamtexport,
  Break-glass, Auditänderung, Offline-Signaturschlüssel und Secretanzeige bleiben für
  Stationsleitungen und Partnerrollen nicht delegierbar.
- Die Vergabe eines als hochriskant klassifizierten Stationsrechts benötigt erneute
  Authentisierung und erzeugt ein unveränderliches Auditereignis mit Zuweisendem,
  Empfänger, Rolle, Permission, Scope, Zeitpunkt und Begründung.

## Zeitlich befristete Vertretung

Partner und Stationsleitungen dürfen geeignete Personen als Vertretung bestimmen. Eine
Vertretungszuweisung benötigt zwingend `valid_from`, `valid_until`, Begründung, konkrete
Rolle beziehungsweise Permissions sowie Tenant- und Stations-Scope. Die Stationsleitung
kann eine Vertretung nur für eigene Stationen einrichten.

- Die Rechte werden erst ab `valid_from` wirksam und serverseitig unmittelbar nach
  `valid_until` entzogen.
- Die Vertretung kann nur ausdrücklich zugewiesene Rechte ausüben. Das Recht zur
  Rollenvergabe muss separat enthalten sein und bleibt auf denselben Scope begrenzt.
- Eine Vertretung darf sich nicht selbst Rechte geben, die eigene Laufzeit ändern,
  reservierte Rechte vergeben oder eine weitere Vertretung einsetzen.
- Partner oder zuweisende Stationsleitung können die Vertretung vorzeitig widerrufen.
- Beginn, Änderung, Widerruf, Ablauf und jede hochriskante Aktion werden auditiert.
- Aktive Sitzungen, Berechtigungscaches und noch nicht gestartete Exporte müssen spätestens
  beim Ablauf oder Widerruf neu geprüft beziehungsweise ungültig gemacht werden.
- Vor Ablauf erhalten zuweisende Person und Vertretung eine Benachrichtigung. Die konkrete
  Vorlaufzeit wird in den Partner-Settings konfiguriert.

## Systemrollen

- `Platform Super Admin`: Plattform-Lifecycle und Notfallsteuerung, kein permanenter Inhaltszugriff
- `Platform Catalog Admin`: Brands, Kraftstoffsorten und weitere globale Kataloge, keine
  operativen Mandantendaten
- `Platform Support`: beantragt zeitlich begrenzte Supportgrants
- `Platform Security Auditor`: liest Security-/Supportaudit
- `Tenant Owner`: Eigentums-, Delegations-, Lösch- und Supportfreigabehoheit
- `Tenant Administrator`: breite operative Verwaltung ohne reservierte Owner-Rechte
- `Employee`: unveränderliche Self-Service-Basisrechte
- `MDE Clock Terminal`: nur stationsgebundenes Paket, `time.clock` und eigener Sync
- `Read-only Auditor`: optionale zeitlich begrenzte Vorlage

## Login- und Step-up-Richtlinie

Die normale Login-Richtlinie verwendet je Rolle `disabled`, `optional` oder `required`.
Plattformregeln werden ausschließlich auf Plattformebene, Partnerregeln innerhalb des
eigenen Mandanten verwaltet. Eine untergeordnete Rolle kann eine übergeordnete Pflicht
nicht abschwächen. Der Pilot startet für Plattform-Admins, Partner und Stationsleitungen
mit `disabled`.

Aktionsbezogene Step-up-MFA ist davon getrennt. Sie bleibt für SupportAccessGrant,
Break-glass, Payroll-Exporte und ausdrücklich klassifizierte Hochrisikoaktionen
verpflichtend. Keine Partnerrolle darf diese Pflicht ändern oder umgehen.

## Permission-Gruppen

### Plattform

`platform.tenant.read_metadata`, `platform.tenant.manage_lifecycle`,
`platform.trial.extend`, `platform.entitlement.manage`,
`platform.catalog.brand.*`, `platform.catalog.fuel_type.*`,
`platform.catalog.legal_form.*`, `platform.catalog.module_definition.*`,
`platform.support_access.*`, `platform.security_audit.read`,
`platform.incident.manage`.

Globale Katalogrechte sind dauerhaft einer berechtigten Plattformrolle zuweisbar, laden
aber keine operativen Mandantendaten. Jede Änderung wird auditiert; bereits referenzierte
Katalogwerte werden versioniert oder deaktiviert statt physisch gelöscht. Operative
Mandantendatenrechte entstehen ausschließlich aus einem aktiven `SupportAccessGrant` mit
Mandant, Zweck, Scope, Beginn und Ablauf.

## SupportAccessGrant und Break-glass

Ein regulärer `SupportAccessGrant` entsteht erst nach einer Supportanfrage mit Mandant,
Zweck, Ticket, beantragtem Scope und Ablauf sowie der Freigabe durch den Partner. Erst
danach kann eine berechtigte Plattform-Supportrolle die freigegebenen Aktionen ausführen.
Die Sitzung ist sichtbar gekennzeichnet und endet spätestens nach acht Stunden automatisch.

Break-glass umgeht ausschließlich die vorherige Partnerfreigabe, nicht MFA, Scope,
Zeitlimit oder Audit. Zulässig ist er nur bei einem schweren Sicherheits- oder
Systemvorfall, wenn die Verzögerung den Schaden voraussichtlich vergrößert. Er verlangt
Incident-ID und Begründung, informiert Partner und Security sofort und wird unabhängig
nachkontrolliert. Normale Supportfälle, Datenpflege und Komfortzugriff sind ausgeschlossen;
Export-Permissions werden einem Break-glass-Grant nicht erteilt.
Ein Break-glass-Grant endet spätestens nach 60 Minuten automatisch. Weder reguläre Grants
noch Break-glass-Grants werden verlängert. Weiterer Zugriff erfordert einen neuen Vorgang;
regulärer Support zusätzlich eine neue Partnerfreigabe.

### Tenant und Legal Entity

`tenant.profile.*`, `tenant.membership.*`, `tenant.entitlement.read`,
`tenant.data_export.request`, `tenant.deletion.request`, `tenant.ownership.transfer`,
`legal_entity.*`, `legal_entity.identifier.*`, `legal_entity.tax_identifier.*`,
`legal_entity.employment.*`, `legal_entity.rule_set.*`.

Ownership-Transfer, Gesamtexport, Löschung und Steuerkennungen sind Hochrisikorechte.

### Station

`station.read`, `station.create`, `station.update`, `station.activate`,
`station.archive`, `station.opening_hours.manage`, `station.identifier.*`,
`station.sensitive_identifier.*`, `station.attachment.*`,
`station.assignment.*`. Partner dürfen Brands auswählen, aber nie global verändern.

### Mitarbeiter

`employee.read_basic`, `employee.read_private_contact`,
`employee.read_protection_profile`, `employee.create`, `employee.update_basic`,
`employee.import`, `employee.export`, `employee.invite`,
`employee.onboarding.review`, `employee.activate`, `employee.suspend`,
`employee.terminate`, `employee.credential.*`, `employment.*`,
`employee.station_assignment.*`, `employee.role_assignment.*`.

`employee.onboarding.review` folgt strikt dem wirksamen Scope: Partner mit Tenant-Scope
dürfen alle vorgesehenen Stationen des eigenen Mandanten bestätigen; Stationsleitungen
und zeitlich berechtigte Vertretungen nur zugeordnete Stationen. Bei Mehrfachzuordnung
wird jede Station separat autorisiert. Eigenfreigabe ist immer ausgeschlossen.

### Rollen

`role.read`, `role.custom.create`, `role.custom.update`, `role.custom.archive`,
`role.assignment.*`, `role.high_risk.delegate`, `role.delegation.manage`,
`role.delegation.revoke`, `role.system.read`.
Plattformrechte, Break-glass, Auditänderung, Offline-Signaturschlüssel und Secretanzeige
sind nicht delegierbar.

### Geräte und Zeit

`device.read`, `device.register`, `device.approve`, `device.assign_station`,
`device.policy.manage`, `device.session.revoke`, `device.mark_lost`, `device.revoke`,
`device.offline_status.read`, `device.offline_grant.rotate/revoke`.

`time.clock.self`, `time.read.self/station/legal_entity`,
`time.correction.request.self`, `time.correction.review`,
`time.manual_event.create`, `time.conflict.review`, `time.rule_finding.*`,
`time.rule_set.*`, `time.period.prepare/approve/reopen/read`, `time.audit.read`.

Keine Selbstfreigabe; Fremdbuchung, Korrektur, Periodenfreigabe und Wiederöffnung sind
getrennte Rechte.

`time.correction.review` kann Partnern, Stationsleitungen und zeitlich aktiven
Vertretungen zugewiesen werden. Die Policy verlangt Scope für alle von der Korrektur
betroffenen Stationen. Antragsteller, betroffene Person und Ersteller einer manuellen
Änderung sind als Entscheider derselben Korrektur ausgeschlossen. Bei Ablauf oder Widerruf
einer Vertretung darf kein bereits geöffnetes Formular die Entscheidung noch speichern.

### Export, Settings und Audit

`export.station_data`, `export.employee_basic`, `export.time_preview/final`,
`export.payroll`, `export.audit`, `export.data_subject`, `export.tenant_full`,
`export.run.read`, `export.file.download/revoke`.

`settings.general.*`, `settings.identity_policy.manage`, `settings.notification.manage`,
`settings.retention.*`, `settings.time_rules.manage`, `settings.offline_policy.manage`,
`settings.export_profile.*`, `settings.payroll_recipient.*`,
`settings.integration.*`, `settings.integration_secret.rotate`, `settings.module.*`,
`settings.appearance.read`, `settings.appearance.manage`.

`audit.self/station/legal_entity/tenant/security/export/support_access.read`.
Audit ist unveränderlich; Secrets können ersetzt/widerrufen, aber nie rückgelesen werden.

## Pflicht-Negativtests

- fremde IDs in URL, Formular, Suche, Autocomplete, Batch, Import, Export und Download;
- fremder Tenant in Queue, Cache, Datei, Audit oder Offlineereignis;
- Identität mit Memberships in Tenant A und B versucht Rollen, Sessions, Benachrichtigungen,
  Exporte oder MDE-Credentials zwischen beiden Kontexten zu verwenden;
- Partner versucht über Einladung, Suche, Dublettenprüfung oder Export die Existenz einer
  Membership derselben Person bei einem anderen Tenant zu erkennen;
- Stationsleitung A greift auf Station B zu;
- Nutzer erzeugt Rolle mit reservierter oder größerer Permission/Reichweite;
- eigene Korrektur oder Rollenausweitung wird selbst genehmigt;
- Ersteller einer manuellen Zeitänderung versucht, die daraus entstandene Korrektur selbst
  zu genehmigen;
- Stationsleitung oder Vertretung entscheidet eine Korrektur mit mindestens einer Station
  außerhalb ihres wirksamen Scopes;
- abgelaufene oder widerrufene Vertretung versucht Lesen, Änderung, Export oder
  Rollenvergabe;
- Vertretung versucht eigene Laufzeit, eigenen Scope oder eigene Rechte zu erweitern oder
  eine weitere Vertretung einzusetzen;
- Permission vorhanden, Entitlement oder zeitlicher Scope fehlt;
- Rollen- oder Tenant-Einstellung versucht eine übergeordnete MFA- oder Step-up-Pflicht
  abzuschwächen;
- abgelaufener Supportgrant versucht Lesen, Änderung oder Export;
- Support- oder Break-glass-Grant versucht seine eigene Laufzeit zu verlängern oder nach
  Ablauf eine bereits geöffnete Aktion zu speichern;
- regulärer Support versucht vor Partnerfreigabe auf Mandantendaten zuzugreifen;
- Break-glass wird ohne schweren Incident, Incident-ID, Step-up-MFA oder sofortige
  Benachrichtigung angefordert beziehungsweise verwendet;
- Break-glass versucht einen Export oder eine Aktion außerhalb des genehmigten Scopes;
- Platform Catalog Admin versucht über Katalogseiten, Suche, Autocomplete oder direkte API
  auf Stations-, Mitarbeiter-, Zeit- oder andere operative Mandantendaten zuzugreifen;
- Payroll-Empfänger wird geändert und ohne Bestätigung verwendet;
- UI-versteckte Aktion wird direkt über API aufgerufen.

Erwartung: `403/404`, keine Existenzoffenlegung, keine Seiteneffekte und Security-Audit.
