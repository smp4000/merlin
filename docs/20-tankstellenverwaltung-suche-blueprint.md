# Tankstellenverwaltung mit Standortsuche

Status: `Fachlich freigegeben – erster deutscher Pilotschnitt umgesetzt; produktive Nutzungsfreigabe offen`

## 1. Problem und Ziel

Partner sollen bestehende Tankstellen nicht vollständig von Hand erfassen müssen. Beim
Anlegen einer Station sucht Merlin zuerst in einem externen Standortverzeichnis, zeigt
passende Treffer an und übernimmt nach bewusster Auswahl ausschließlich zulässige
Stammdaten in einen mandantengebundenen Stationsentwurf.

Die Suche ist eine Eingabehilfe, keine amtliche Bestätigung. Der Partner prüft Marke,
Anschrift, Betreiber, Öffnungszeiten und Kennungen vor der Aktivierung selbst. Ein nicht
gefundener oder fehlerhafter Standort kann weiterhin manuell angelegt werden.

## 2. Bestätigte Anforderungen

- Es gibt im Partner-Panel eine eigene Tankstellenverwaltung.
- Beim Anlegen wird zuerst eine Tankstellensuche angeboten.
- Ein Partner kann mehrere Tankstellen führen.
- Jede Station gehört unveränderlich genau einem Tenant und genau einer Legal Entity.
- Brandwerte stammen aus dem zentralen, vom Super-Admin gepflegten DACH-Katalog.
- Der Partner wählt vor stationsbezogener Arbeit eine aktuell erlaubte Station aus.
- Die Plattform bleibt für Deutschland, Österreich und die Schweiz vorbereitet.
- Kraftstoffpreise sind kein Bestandteil dieser Suche.
- Die deutsche Pilotsuche fragt `benzinpreis-aktuell.de` ab.
- Der Suchradius ist mit `2`, `5`, `10`, `15`, `20` oder `25` Kilometern auswählbar.
- Suchtreffer erscheinen in der ersten Version als übersichtliche Liste ohne Karte.
- Dieselbe externe Stations-ID darf innerhalb eines Tenants nur einmal übernommen
  werden. Ähnliche Anschriften oder nahe Koordinaten erzeugen dagegen eine Warnung; der
  Partner darf mit einer verpflichtenden Begründung fortfahren.
- Eine bereits während der Registrierung angelegte Station kann freiwillig mit einem
  Suchtreffer verknüpft werden. Die Verknüpfung erfolgt niemals automatisch und
  überschreibt keine bestätigten Stammdaten.
- Im Suchformular erscheint der Hinweis: `Die Suche fragt benzinpreis-aktuell.de ab.
  Alternativ können Sie ohne Suche weitergehen.`
- Der während der Registrierung erfasste erste Standort erscheint ebenfalls in der
  späteren Stationsübersicht und wird nicht erneut angelegt.

## 3. Empfehlung für den ersten Umsetzungsschnitt

Der Pilot startet in Deutschland mit einem austauschbaren Adapter für
`benzinpreis-aktuell.de`. Die Referenzimplementierung zeigt, dass eine PLZ-basierte
Umkreissuche sowie ein anschließender Abruf der Stationsdetailseite technisch möglich
sind. Merlin übernimmt daraus ausschließlich Standortstammdaten und niemals Preise.

Die offizielle öffentliche JSON-API von `benzinpreis-aktuell.de` stellt laut
Dokumentation Preise einzelner Tankstellen bereit, jedoch keine dokumentierte
Stationssuche. Die geplante Suche muss daher HTML-Seiten auswerten und ist technisch
störanfällig. Zudem stammen die veröffentlichten Stations- und Preisdaten laut Anbieter
von der Markttransparenzstelle für Kraftstoffe. Eine produktive Integration benötigt
deshalb vor Aktivierung eine schriftliche Nutzungsfreigabe des Webseitenbetreibers für
Merlins konkreten Betreiber-/SaaS-Zweck. Bis dahin bleibt der Adapter per Feature Flag
deaktivierbar und darf nur im abgestimmten Pilotprofil verwendet werden.

Weitere Empfehlungen:

- Suche zuerst, manuelle Anlage immer als sichtbare Alternative;
- kein automatisches Überschreiben bestätigter Stationsdaten;
- externe Änderungen nur als prüfbarer Aktualisierungsvorschlag;
- keine Preisdaten, Mitbewerberpreise oder Preisverläufe importieren;
- keine dauerhafte Speicherung kompletter Rohantworten ohne Lizenzbedarf;
- Suchanbieter technisch austauschbar halten, damit kein Vendor-Lock-in entsteht.
- bei einer Strukturänderung der Website sofort auf manuelle Anlage zurückfallen;
- Deutschland zuerst; Österreich und Schweiz benötigen später eine andere ausdrücklich
  freigegebene Quelle.

## 4. Nicht-Ziele dieses Schnitts

- Kraftstoffpreisvergleich oder automatische Preisübernahme;
- Wettbewerber- und Umkreisanalyse;
- automatische Betreiber-, Vertrags- oder Eigentumsprüfung;
- TMS5000-Anbindung;
- Kartenrouting, Flottennavigation oder öffentliche Tankstellensuche für Verbraucher;
- automatische Aktivierung eines Suchtreffers;
- Massenimport aller DACH-Tankstellen;
- periodische Vollsynchronisation eines externen Verzeichnisses.

## 5. Nutzer, Rollen und Reichweite

### Tenant Owner und Partner-Administrator

- sehen alle Stationen des aktiven Tenants;
- dürfen suchen, einen Treffer übernehmen und eine Station manuell anlegen;
- dürfen Stationsentwürfe bearbeiten und bei erfüllten Pflichtfeldern aktivieren;
- dürfen die zugehörige Legal Entity nur aus dem aktiven Tenant wählen.

### Stationsleitung

- sieht später ausschließlich wirksam zugewiesene Stationen;
- darf im ersten Schnitt keine neue Station und keine fremde Station anlegen;
- erhält nur separat freigegebene Änderungsrechte für operative Stationsfelder.

### Zeitlich zugewiesene Vertretung

- erhält keine impliziten Anlagerechte;
- darf nur mit ausdrücklich zugewiesenem `station.create` suchen und anlegen;
- bleibt auf Zeitraum, Tenant und gegebenenfalls Legal-Entity-Scope begrenzt.

### Plattform-Super-Admin

- verwaltet Anbieter-Konfiguration, Brandkatalog und globale Feldkataloge;
- sieht Suchdiagnosen ohne mandantenbezogene Formulardaten;
- erhält durch die Plattformrolle keinen Zugriff auf Partnerstationen;
- ein Supportzugriff folgt weiterhin dem freigegebenen JIT-/Break-glass-Verfahren.

### Granulare Permissions

- `station.read`
- `station.search`
- `station.create`
- `station.update`
- `station.activate`
- `station.close`
- `station.identifier.read`
- `station.identifier.manage`
- `station.document.manage`
- `station.search_provider.manage` ausschließlich für Plattformrollen

Jede Policy kombiniert Permission, aktiven TenantContext, Legal-Entity- und bei
bestehenden Datensätzen Stations-Scope. Eine vom Browser übergebene `tenant_id` oder
`legal_entity_id` gilt nie ungeprüft als Berechtigung.

## 6. Hauptablauf: Station suchen und übernehmen

1. Der Partner öffnet `Tankstellen → Übersicht` und wählt `Tankstelle anlegen`.
2. Merlin zeigt die Optionen `Tankstelle suchen` und `Manuell anlegen`; die Suche ist
   vorausgewählt.
3. Im deutschen Pilot gibt der Partner eine fünfstellige PLZ ein und wählt einen Radius
   von 2, 5, 10, 15, 20 oder 25 km. Name/Marke können die Trefferliste lokal filtern.
4. Der Server validiert die Eingabe, prüft Permission, Trial-Schreibstatus und Rate Limit
   und ruft den konfigurierten Anbieter ausschließlich serverseitig auf.
5. Treffer zeigen Name, Brandhinweis, strukturierte Anschrift, Entfernung und
   Datenquelle. Unsichere oder fehlende Werte sind sichtbar markiert.
6. Nach Auswahl lädt Merlin verfügbare Details und führt vor der Übernahme eine
   tenantinterne Dublettenprüfung durch.
7. Der Partner vergleicht Suchtreffer und vorhandene Station, bestätigt `Neue Station`
   oder öffnet den bestehenden Datensatz.
8. Merlin erzeugt transaktional einen Stationsentwurf im aktiven Tenant. Der Partner
   wählt den rechtlichen Betreiber und bestätigt beziehungsweise korrigiert alle Werte.
9. In den Tabs werden Pflichtfelder, Warnungen und Herkunft angezeigt.
10. Erst die abschließende Prüfung kann den Entwurf mit `station.activate` aktivieren.

## 7. Alternative, Fehler- und Missbrauchsabläufe

### Kein passender Treffer

Die Seite zeigt Suchhinweise und die Aktion `Tankstelle manuell anlegen`. Bereits
eingegebene Land-/Ortswerte dürfen in den Entwurf übernommen werden.

### Anbieter nicht erreichbar oder Rate Limit erreicht

Merlin zeigt eine verständliche deutsche Meldung, protokolliert einen technischen
Fehlercode ohne API-Key und bietet die manuelle Anlage an. Es wird kein leerer oder
teilweise erzeugter Stationsdatensatz angelegt.

### Unvollständiger oder widersprüchlicher Treffer

Fehlende Felder bleiben leer und werden im Prüfschritt markiert. Eine externe Brand wird
nur über eine zentral gepflegte Aliaszuordnung einem Merlin-Brand zugeordnet. Unbekannte
Brands werden nicht automatisch neu angelegt.

### Dublette

Merlin warnt bei gleicher Provider-ID, sehr ähnlicher normalisierter Anschrift,
Koordinatennähe oder eindeutiger Stationskennung. Eine bereits im selben Tenant
verwendete Provider-ID sperrt die zweite Übernahme technisch und führt stattdessen zum
bestehenden Datensatz. Bei lediglich ähnlicher Anschrift oder räumlicher Nähe darf ein
berechtigter Partner nach einer deutlichen Warnung fortfahren; eine Begründung ist
Pflicht und wird auditiert. Zusammenführen erfolgt nie automatisch.

### Bestehende Onboarding-Station verknüpfen

In der Stationsansicht kann ein berechtigter Partner die Aktion
`Mit Tankstellenverzeichnis verknüpfen` starten. Merlin öffnet denselben Suchablauf,
zeigt einen ausgewählten Treffer neben den vorhandenen Stammdaten und markiert
Abweichungen feldweise. Erst nach ausdrücklicher Bestätigung wird ausschließlich die
Quellenreferenz gespeichert. Abweichende Name-, Brand-, Adress-, Koordinaten- oder
Öffnungszeitwerte werden nur als Änderungsvorschläge angeboten und niemals automatisch
in den bestehenden Datensatz geschrieben. Abbruch lässt die Station unverändert.

### Manipulierte externe ID oder Trefferpayload

Der Browser sendet nur eine kurzlebige, serverseitig gebundene Trefferreferenz. Merlin
lädt beziehungsweise verifiziert den Treffer erneut und übernimmt keine frei
eingespeisten Adress-, Tenant- oder Providerwerte.

### Abgelaufener Trial oder Nur-Lesen-Modus

Die Stationsliste und bestehende Datensätze bleiben lesbar. Suche, Übernahme, manuelle
Anlage, Aktualisierung und Aktivierung werden serverseitig gesperrt.

## 8. Zustände und Übergänge

```text
Entwurf → optional zur Prüfung → aktiv → temporär geschlossen → geschlossen
```

- Ein Suchtreffer ist noch kein Stationsdatensatz.
- Übernahme und manuelle Anlage beginnen immer als `draft`.
- Aktivierung verlangt vollständige Pflichtfelder und `station.activate`.
- Betreiber-, Brand-, GLN- und Schließungsänderungen werden historisiert und können
  später einen Vier-Augen-Workflow erhalten.
- Geschlossene Stationen werden nicht physisch gelöscht und bleiben in Audit, Exporten
  und historischen Modulen referenzierbar.

## 9. Datenmodell

### Erweiterung `stations`

- `tenant_id` unveränderlich und verpflichtend;
- `legal_entity_id` mit tenantgebundenem zusammengesetztem Fremdschlüssel;
- `fuel_station_brand_id` aus dem globalen aktiven Brandkatalog;
- `public_id`, `name`, `short_name`, `status`;
- strukturierte Anschrift und `country_code`;
- `latitude` und `longitude` optional, mit fachlich begrenzter Genauigkeit;
- `timezone`, `default_locale`;
- `source_type`: `external_search`, `manual`, `onboarding`, `import`;
- `source_verified_at`, `source_checked_by_user_at`;
- Aktivierungs-, Schließungs- und Gültigkeitsfelder.

Der bisherige eindeutige Schlüssel `(tenant_id, name)` reicht nicht als
Dublettenschutz, weil gleichnamige Stationen möglich sind. Er wird durch fachlich
geeignete Indizes und eine separate Dublettenprüfung ersetzt.

### `station_source_references`

- `tenant_id`, `station_id`;
- stabiler `provider_key`;
- externe Stations-ID verschlüsselt oder gehasht, wenn Anbieterbedingungen dies
  erfordern;
- normalisierter Fingerprint für Dublettenprüfung;
- `imported_at`, `last_checked_at`, `payload_checksum`;
- keine dauerhafte Rohantwort als Standard.

Eine Station kann mehrere historische Quellenreferenzen besitzen, aber je Provider und
Tenant nur eine aktive Referenz auf dieselbe externe Station.

### `station_change_suggestions`

Für einen späteren Aktualisierungsabruf werden externe Abweichungen getrennt vom
bestätigten Stammdatensatz gespeichert. Vorschläge enthalten Feld, alten Anzeigewert,
neuen Anzeigewert, Quelle, Zeitpunkt und Status `pending`, `accepted`, `rejected` oder
`expired`. Annahme und Ablehnung sind auditiert.

### Anbieterkonfiguration

Der Super-Admin verwaltet nur nicht geheime Metadaten wie Providerstatus, Länder,
Fähigkeiten und erlaubte Hosts. API-Schlüssel liegen in Umgebungs-/Secret-Konfiguration
und sind in UI, Datenbank, Logs und Exporten niemals lesbar.

## 10. Suchvertrag und technische Architektur

Ein anbieterunabhängiger Vertrag kapselt die externe Integration:

- `StationSearchProvider`
- `StationSearchQuery`
- `StationSearchResult`
- `StationDetails`
- `SearchStations`
- `CreateStationFromSearchResult`

Der Provider erhält Land, PLZ/Ort, optionale Koordinaten, Radius sowie Name/Brandfilter.
Er liefert nur normalisierte DTOs. Filament-Seiten kennen keine anbieterspezifischen
JSON- oder HTML-Strukturen.

Der erste Adapter heißt `BenzinpreisAktuellStationSearchProvider`. Er kapselt die
PLZ-Suchseite und den Detailseitenabruf vollständig. HTML-Selektoren, Slugbildung,
Timeouts und Parserfixtures bleiben im Adapter; Fachservices erhalten keine HTML-Werte.
Eine erfolgreiche Antwort muss mindestens eine externe Trefferkennung, Name und eine
prüfbare Anschrift enthalten. Preisfelder aus Such- oder Detailseiten werden bereits im
Adapter verworfen und gelangen nicht in DTO, Cache, Audit oder Datenbank.

Technische Schutzmaßnahmen:

- serverseitige HTTP-Aufrufe mit Allowlist, TLS, kurzen Timeouts und begrenzten Retries;
- keine vom Nutzer frei vorgegebene Ziel-URL und damit kein SSRF-Pfad;
- Circuit Breaker, Rate Limit und anonymisierte Metriken je Provider;
- kurze, lizenzkonforme Cachezeit ohne Tenant- oder Benutzerkennung im Cache-Key;
- Cache-Key enthält Provider, Land und normalisierte Suchparameter;
- Response-Schema, maximale Antwortgröße und Koordinatenbereiche werden validiert;
- API-Keys werden vor Log-, Exception- und Monitoringausgabe entfernt;
- Providerwechsel ohne Änderung der Fachservices und Filament-Seiten.

## 11. UX, Navigation und Formulare

### Navigation

- `Tankstellen → Übersicht`
- `Tankstellen → Tankstelle anlegen`
- später `Tankstellen → Änderungsvorschläge`

### Übersicht

Die Übersicht zeigt aktive Station, Name, Brand, Ort, Betreiber, Status und Quelle. Bei
höchstens zwei Stationen nutzt Merlin die visuell ausführlichen Standortkarten. Ab drei
Stationen wechselt dieselbe tenantgebundene Datenmenge automatisch in eine kompakte,
horizontal zugängliche Tabelle. Der während des Onboardings angelegte Standort ist sofort
sichtbar. Tenantweite Suche, Filter und Stapelaktionen bleiben durch Policies geschützt.

Jede Karte und Tabellenzeile bietet `Bearbeiten`. Der erste Bearbeitungsschnitt umfasst
Betreiber, Marke, Namen und strukturierte Anschrift im dreistufigen Wizard. Status,
Herkunft und Verzeichnisreferenzen sind dort unveränderlich. Änderungen werden mit
optimistischer Versionsprüfung gespeichert; bei parallelen Änderungen muss die Seite neu
geöffnet werden. Eine geänderte Anschrift verwirft veraltete Koordinaten und hebt die
bisherige Quellenbestätigung auf, ohne die Verzeichnisreferenz zu löschen.

### Suchtrefferliste

Jeder Treffer zeigt Tankstellenname, erkannte Marke, vollständige Anschrift, Entfernung
und – nur sofern zuverlässig ermittelbar – den aktuellen Öffnungsstatus. Die primäre
Aktion heißt `Tankstelle auswählen`. Ein möglicher vorhandener Merlin-Datensatz wird als
Duplikatwarnung direkt am Treffer angezeigt. Unterhalb der Liste bleibt die Aktion
`Tankstelle ohne Suche manuell anlegen` jederzeit sichtbar.

Eine Kartenansicht gehört nicht zum ersten Umsetzungsschnitt. Die Listenansicht muss auf
Desktop, Tablet und Mobilgerät vollständig bedienbar sein.

### Anlage-Wizard

Die Suche bildet den vorgeschalteten Auswahlbereich. Sobald eine Tankstelle gewählt oder
die manuelle Erfassung gestartet wurde, klappt dieser Bereich zu einer kompakten
Zusammenfassung ein. `Auswahl ändern` öffnet ihn ohne Verlust bereits ergänzter Werte.

Der erste freigegebene Grunddatenschnitt führt anschließend durch drei echte Schritte:

1. `Allgemein` – Betreiber, Marke, Stationsname und Kurzname;
2. `Adresse` – Straße, Hausnummer, Zusatz, PLZ, Ort und Bundesland;
3. `Prüfen` – Zusammenfassung und Speichern als Entwurf.

`Weiter` validiert ausschließlich den sichtbaren Schritt. Fehler bleiben am sichtbaren
Feld; die Enter-Taste darf den Wizard nicht überspringen. Eingabeflächen besitzen in
Ruhe-, Hover-, Fokus- und Fehlerzustand einen klar erkennbaren Rahmen und ausreichenden
Kontrast.

Direkt unter der Suchüberschrift steht dauerhaft:

> Die Suche fragt benzinpreis-aktuell.de ab. Alternativ können Sie ohne Suche weitergehen.

Die spätere vollständige Detailbearbeitung erweitert das frische Merlin-Tabdesign um:

- `Allgemein`
- `Adresse`
- `Öffnungszeiten`
- `Shop & Angebote`
- `Karten`
- `Kennungen`
- `Dokumente`
- `Prüfen`

Tabtitel zeigen Fehlerzahl, Warnungen und Vollständigkeit. Mobil wird derselbe Ablauf als
Stepper beziehungsweise Akkordeon ohne horizontales Scrollen dargestellt. Alle Aktionen
sind per Tastatur bedienbar; Status und Fehler werden nicht ausschließlich farblich
kommuniziert.

## 12. Validierung und Dublettenregeln

- Land zunächst `DE`, vorbereitet für `AT` und `CH`;
- deutsche PLZ exakt fünf Ziffern; Länderregeln bleiben getrennt konfigurierbar;
- Suchradius ausschließlich `2`, `5`, `10`, `15`, `20` oder `25` km; frei eingegebene
  beziehungsweise manipulierte Werte werden serverseitig abgewiesen;
- strukturierte Anschrift vor Aktivierung vollständig;
- Koordinaten müssen innerhalb plausibler Ländergrenzen liegen;
- Legal Entity muss aktiv sein und zum aktuellen Tenant gehören;
- Brand muss aktiv und für das Stationsland freigegeben sein;
- externe Provider-ID wird nie als öffentliche Merlin-ID verwendet;
- identische Provider-ID ist innerhalb eines Tenants hart eindeutig;
- Adress- und Koordinatenähnlichkeit ist eine weiche Warnung und darf nur mit
  dokumentierter Begründung übergangen werden;
- GLN und weitere Kennungen folgen den Regeln aus den Stationsstammdaten;
- Dublettenprüfung offenbart niemals Treffer eines anderen Tenants.

## 13. Audit, Datenschutz und Aufbewahrung

Audit-Ereignisse:

- Suche ausgelöst, nur mit grob normalisiertem Ort und Ergebnisanzahl;
- Treffer ausgewählt und als Entwurf übernommen;
- manuelle Anlage nach erfolgloser Suche;
- Dublettenwarnung bestätigt oder abgebrochen;
- Quelle geprüft, Änderungsvorschlag angenommen oder abgelehnt;
- Station aktiviert, Betreiber/Brand geändert oder Station geschlossen.

API-Key, vollständige Providerantworten, Request-Header und unnötige IP-Adressen werden
nicht auditiert. Suchbegriffe dürfen keine Personen- oder Geheimdaten enthalten. Cache-
und technische Fehlerdaten erhalten kurze, definierte Löschfristen. Stationsstammdaten
bleiben entsprechend gesetzlicher und fachmodulbezogener Aufbewahrung historisch
referenzierbar; eine konkrete Löschfrist ist vor Pilotstart extern zu prüfen.

Eine DSFA ist für die reine Standortsuche voraussichtlich nicht wegen der Suche allein
erforderlich; diese Einschätzung ist keine Rechtsberatung und wird im Gesamtprodukt mit
Beschäftigtendaten, MDE und Zeiterfassung erneut geprüft.

## 14. Benachrichtigungen, Exporte und Offlineverhalten

- Keine Nachricht bei jeder Suche.
- Optionaler interner Hinweis, wenn ein externer Aktualisierungsvorschlag vorliegt.
- Exporte kennzeichnen Quelle und letzten Partner-Prüfzeitpunkt, enthalten aber keine
  Provider-Rohantwort oder API-Zugangsdaten.
- Stationssuche und Neuanlage sind online erforderlich.
- MDE- und Mitarbeiter-PWA dürfen keine Stationen suchen oder anlegen.
- Bereits aktivierte Stationsstammdaten werden später tenant- und stationsgebunden in
  Offlinepakete übernommen.

## 15. Migration und Rollout

1. Bestehende Onboarding-Stationen erhalten `source_type = onboarding`.
2. Bestehende Stationen werden nicht gegen einen Provider automatisch gematcht.
3. Owner kann später freiwillig `Mit Verzeichnis abgleichen` starten und einen Treffer
   bestätigen. Die Verknüpfung speichert zunächst nur die Quellenreferenz; mögliche
   Stammdatenabweichungen bleiben getrennte, bewusst anzunehmende Änderungsvorschläge.
4. Pilot erfolgt mit den zwei eigenen Aral-Tankstellen.
5. Erst nach erfolgreichem Pilot werden weitere Partner und DACH-Länder freigeschaltet.
6. Providerfehlerquote, Suchlatenz, Trefferquote, manuelle Ausweichquote und erkannte
   Dubletten werden ohne personenbezogene Inhalte überwacht.

Rollback deaktiviert den Provider per Feature Flag. Stationsentwürfe und bestätigte
Stammdaten bleiben erhalten und können manuell weiterbearbeitet werden.

## 16. Testplan

### Positiv- und UI-Tests

- Suche nach gültiger PLZ liefert normalisierte Treffer;
- jede der sechs erlaubten Radiusstufen wird korrekt an den Provideradapter übergeben;
- Auswahl befüllt einen Entwurf, aktiviert ihn aber nicht automatisch;
- bestehende Onboarding-Station erscheint in der Übersicht;
- bestehende Onboarding-Station kann bewusst verknüpft werden, ohne dass ihre
  Stammdaten automatisch verändert werden;
- manuelle Anlage funktioniert ohne Provider;
- Tabfehler, mobile Darstellung und Tastaturbedienung sind nachvollziehbar.

### Fehler- und Grenztests

- ungültige PLZ, nicht erlaubte Radiuswerte, Land, leere und überlange Eingaben;
- Timeout, DNS-/TLS-Fehler, Rate Limit, ungültiges JSON und übergroße Antwort;
- fehlende Adresse, unbekannte Brand, Koordinaten außerhalb des Landes;
- Provider liefert denselben Treffer mehrfach;
- Station und Legal Entity wechseln während eines alten Formularzustands.

### Tenant-, Rollen- und Securitytests

- fremde Tenant-, Legal-Entity-, Stations- und Trefferreferenzen werden neutral
  abgewiesen;
- Stationsleitung ohne `station.create` kann UI und direkte Route nicht verwenden;
- abgelaufene Vertretung und Nur-Lesen-Tenant können nicht schreiben;
- manipulierte Provider-ID, Replay einer Trefferreferenz und Mass Assignment scheitern;
- SSRF-Ziel, Redirect auf fremden Host und Secret-Leak in Logs scheitern;
- Suche, Filter, Stapelaktionen und Exporte zeigen keine Cross-Tenant-Daten.

### Integrations- und Vertragstests

- Providerantworten werden über gespeicherte, anonymisierte Fixtures geprüft;
- ein Contract-Test gilt für jeden Provideradapter;
- Produktivtests senden keine echten Requests ohne ausdrücklich aktiviertes Profil;
- Parser-Contract-Tests erkennen geänderte HTML-Strukturen anhand versionierter,
  preisbereinigter Fixtures und lösen den manuellen Fallback aus;
- Testläufe bleiben auf der flüchtigen Testdatenbank und verändern keine lokalen Daten.

## 17. Messbare Akzeptanzkriterien

- Ein berechtigter Owner findet im Pilot eine seiner zwei Stationen über PLZ/Ort und kann
  sie nach Prüfung als Entwurf übernehmen.
- Kein Suchtreffer wird ohne bewusste Auswahl und Bestätigung gespeichert.
- Bei Nichtverfügbarkeit des Providers bleibt manuelle Anlage möglich.
- Fremde Tenant- oder Legal-Entity-IDs erzeugen weder Zugriff noch Existenzhinweis.
- Eine bestehende Provider-ID kann im selben Tenant nicht unbemerkt doppelt übernommen
  werden.
- Eine bewusst bestätigte weiche Dublettenwarnung speichert Akteur, Zeitpunkt und
  Begründung, aber keine unnötige vollständige Providerantwort.
- Extern geänderte Daten überschreiben niemals automatisch bestätigte Stammdaten.
- API-Keys und vollständige Rohantworten erscheinen nicht in Logs, Audit oder Exporten.
- Preis- und MTS-K-Daten werden weder gesucht noch gespeichert noch angezeigt.
- Alle Positiv-, Negativ-, Tenant-, Rollen- und Securitytests bestehen.
- Die schriftliche Nutzungsfreigabe von `benzinpreis-aktuell.de` ist vor produktiver
  Aktivierung dokumentiert.

## 18. Offene Entscheidungen vor produktiver Freigabe

1. Erteilt der Betreiber von `benzinpreis-aktuell.de` die schriftliche Erlaubnis zur
   HTML-basierten Standortsuche für Merlin und dessen Tankstellenpartner?
2. Welcher freigegebene Anbieter übernimmt später Österreich und die Schweiz? Diese
   Entscheidung blockiert den deutschen Pilot nicht, aber den DACH-Rollout.

Die lokale Pilotintegration bleibt bis zur schriftlichen Nutzungsfreigabe über
`STATION_SEARCH_ENABLED` abschaltbar. In produktiven Umgebungen ist sie standardmäßig
deaktiviert. Der DACH-Rollout außerhalb Deutschlands benötigt einen separat geprüften
Anbieter.

## 19. Umsetzungsnachweis: erster deutscher Pilotschnitt vom 31.08.2026

Umgesetzt:

- Partnernavigation `Tankstellen` mit tenantgebundener Übersicht;
- vorhandene Onboarding-Station bleibt erhalten und wird als Quelle `onboarding`
  angezeigt;
- Suchseite mit PLZ und den bestätigten Werten 2, 5, 10, 15, 20 und 25 km;
- sichtbarer Quellenhinweis und jederzeit verfügbare manuelle Alternative;
- preisfreier Adapter `BenzinpreisAktuellStationSearchProvider` mit Host-Allowlist,
  Timeouts, Antwortgrößenlimit, Feature Flag und kontrolliertem Ausfall;
- kurzlebig signierte Trefferreferenzen gegen manipulierte Providerkennungen;
- serverseitig erneut geladene Detaildaten vor Anlage beziehungsweise Verknüpfung;
- Stationsentwürfe über `CreateStation`, ausschließlich aus dem autoritativen
  `TenantContext` und geschützt durch den zentralen Nur-Lesen-Guard;
- zusammengesetzte Datenbank-Fremdschlüssel für Station/Gesellschaft und
  Quellenreferenz/Station;
- harte tenantinterne Eindeutigkeit externer Stationskennungen;
- weiche Adressdubletten mit Pflichtbegründung und Audit;
- freiwilliges Verknüpfen einer Onboarding-Station ohne Überschreiben bestätigter
  Stammdaten;
- verschlüsselte externe Stationskennung und tenantgebundener HMAC für Eindeutigkeit;
- robuster, objektfreier Cache für den Betrieb über mehrere PHP-Prozesse;
- deutsche und englische Oberflächentexte sowie eigenständiges responsives Merlin-Design.

Nachgewiesene Grenzen der Pilotquelle:

- `benzinpreis-aktuell.de` unterstützt nativ nicht alle gewünschten Radiuswerte;
- 2 km werden über den nächstgrößeren Anbieterbereich 3 km und 15 km über 20 km
  angenähert; die Oberfläche weist auf mögliche zusätzliche Treffer hin;
- für 25 km kann die Quelle Treffer zwischen 20 und 25 km nicht zuverlässig liefern;
  die Oberfläche warnt deshalb ausdrücklich vor möglichen fehlenden Treffern;
- die Quelle liefert keine verlässliche Entfernung je Treffer, weshalb im ersten
  Pilotschnitt keine erfundene Kilometerangabe am Ergebnis angezeigt wird;
- HTML-Strukturänderungen führen zum manuellen Fallback statt zu Teilimporten;
- eine schriftliche Nutzungsfreigabe bleibt Release-Gate für den produktiven Betrieb.

Prüfnachweis:

- 111 Laravel-Tests mit 467 Assertions einschließlich der abschließenden
  Stationsfälle vollständig grün;
- zusätzliche Provider-, Livewire-, Tenant-, Nur-Lesen-, Dubletten-, Verschlüsselungs-
  und Verknüpfungstests sind Bestandteil der Suite;
- zwei Frontendtests sowie der Vite-Produktionsbuild sind grün;
- ein echter preisfreier Pilotabruf für PLZ 36100 liefert Suchtreffer und strukturierte
  Detaildaten;
- die lokale MySQL-Migration lief vorwärtsgerichtet; die bestehende Station
  `Aral Tankstelle Christian Welle` blieb als aktive Onboarding-Station erhalten.

Bewusst nachgelagert bleiben Bearbeitung und Aktivierung vollständiger Stationsdaten,
Öffnungszeiten, Shop, Karten, Kennungen, Dokumente, Änderungsvorschläge sowie die
verbindliche Stationsauswahl für operative Module. Diese Punkte erhalten auf Grundlage
dieses Blueprints eigene, prüfbare Umsetzungsschnitte.
