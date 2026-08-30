# DACH-Markenkatalog und Seeder-Spezifikation

## Entscheidung

Merlin startet mit einem zentralen, versionierten Katalog öffentlich dokumentierter
Tankstellenmarken aus Deutschland, Österreich und der Schweiz. Der Quelldatensatz liegt
unter [`docs/data/fuel-station-brands-dach.json`](data/fuel-station-brands-dach.json).

Der Datensatz enthält die in den gewählten Branchenstatistiken einzeln benannten Marken.
Verbände, reine Betreibergruppen und Sammelkategorien werden nicht als Marke modelliert.
Da keine öffentliche Quelle jede einzelne freie Kleinstmarke vollständig erfasst, gibt es
zusätzlich den kontrollierten Auffangwert `Freie Tankstelle / sonstige Eigenmarke`.
Berechtigte Plattformrollen können fehlende reale Marken zentral ergänzen.

## Datenfelder

- `slug`: unveränderlicher technischer Schlüssel;
- `name`: sichtbarer Markenname;
- `countries`: ISO-3166-1-Alpha-2-Ländergültigkeit;
- `aliases`: frühere Namen, Schreibvarianten oder bekannte Standortauftritte;
- `status`: fachlicher Lebenszyklus, zunächst `active`;
- `classification`: `consumer_brand` oder kontrollierter `generic_fallback`;
- `description_de`: deutsche fachliche Erläuterung;
- `source_ids`: nachvollziehbare Referenzen auf die Quellen im Dateikopf.

Betreibergesellschaft, Eigentümer, Vertriebskanal und Marke bleiben getrennte Konzepte.
Eine spätere Markenhierarchie oder Betreiberzuordnung darf daher nicht über den
Anzeigenamen improvisiert werden, sondern benötigt ein eigenes freigegebenes Modell.

## Laravel-Seeder nach Gate 0

Beim Laravel-Scaffold entsteht `Database\Seeders\FuelStationBrandSeeder`. Er liest die
JSON-Datei ein, validiert Schema und referenzierte Quellen und führt einen idempotenten
Upsert anhand von `slug` aus.

Verbindliche Regeln:

1. Der Seeder darf bei wiederholter Ausführung keine Duplikate erzeugen.
2. `slug` wird nach erstmaliger Verwendung niemals automatisch geändert.
3. Name, Beschreibung, Status, Ländergültigkeit, Aliase und Quellen dürfen aktualisiert
   werden.
4. Nicht mehr gelistete oder umbenannte Marken werden deaktiviert beziehungsweise als
   Alias fortgeführt, aber nicht gelöscht, wenn Stationen darauf verweisen.
5. Partnerrollen dürfen Katalogwerte nur auswählen. Änderungen erfordern eine getrennte
   Plattform-Permission und erzeugen einen Audit-Eintrag.
6. Der generische Auffangwert erlaubt die Stationsanlage, löst aber einen Prüfhinweis an
   die Katalogverwaltung aus. Freitext ersetzt keine dauerhafte Marken-ID.
7. Produktionsdaten und konkrete Stationen werden durch diesen Seeder nicht angelegt.
8. Ein Test prüft eindeutige Slugs, erlaubte Länder, vorhandene Quellen, gültige
   Klassifikationen und mindestens eine Marke je DACH-Land.

## Aktualisierung

Der Katalog wird vor jedem Release und mindestens jährlich gegen die drei hinterlegten
Quellen geprüft. Jede Änderung erhält ein Review-Datum und eine nachvollziehbare
Änderungshistorie. Neu gefundene Regionalmarken können zwischen den Jahresprüfungen über
den auditierten Plattformprozess ergänzt werden.

## Abgrenzung

Der Datensatz beansprucht Vollständigkeit für die namentlich aufgeführten Marken der
verwendeten Marktstatistiken, nicht für jede einzelne unabhängige Tankstelle im DACH-Raum.
Das ist fachlich belastbarer als eine nicht überprüfbare Liste aus Suchmaschinen oder
Preisvergleichsportalen.
