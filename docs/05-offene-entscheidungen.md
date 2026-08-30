# Offene Entscheidungen

Diese Fragen müssen vor Gate 0 beantwortet werden. Antworten werden anschließend als
Product Decisions oder Architecture Decision Records festgehalten.

## Geschäft und Mandant

1. **Entschieden:** Ein Mandant kann ein einzelner Betreiber oder eine Unternehmensgruppe sein.
2. **Entschieden:** Ein Mandant kann mehrere rechtlich selbstständige Gesellschaften enthalten.
3. **Entschieden:** Ja. Eine Person kann ein gemeinsames Benutzerkonto mit mehreren
   getrennten TenantMemberships verwenden. Jeder Betreiber führt einen eigenen
   Mitarbeiter- und Beschäftigungsdatensatz und sieht keine Mitgliedschaften oder Daten
   anderer Betreiber. Die Verknüpfung erfolgt nur durch Annahme einer Einladung oder
   bestätigte Anmeldung, nie automatisch anhand von Name oder Kontaktdaten.
4. **Teilweise entschieden:** Zum Start werden die namentlich ausgewiesenen Marken der
   festgelegten DACH-Branchenstatistiken plus ein kontrollierter Auffangwert für freie
   Eigenmarken zentral und versioniert bereitgestellt. Fehlende reale Regionalmarken
   können Plattformrollen auditiert ergänzen. Betreibergesellschaft, Eigentümer und
   Marke bleiben getrennt. Eine weitergehende Marken-/Betreiberhierarchie ist noch zu
   entscheiden.
5. **Entschieden:** Trial startet automatisch nach E-Mail-Bestätigung. Nach Tag 14 folgt
   automatisch der Nur-Lese-Modus; vorhandene Daten bleiben sichtbar und exportierbar,
   neue Eingaben und Änderungen werden gesperrt. Ein Super-Admin darf den Trial genau
   einmal um weitere 14 Tage verlängern. Die Verlängerung muss begründet und auditiert
   werden.
6. **Teilweise entschieden:** Deutschland mit allen Bundesländern zuerst. Merlin wird
   technisch von Beginn an mehrsprachig; Deutsch ist die System- und Fallbacksprache.
   Welche weiteren Sprachen beim ersten Release vollständig ausgeliefert werden, bleibt
   noch festzulegen.

## Pilot und Arbeitsabläufe

7. **Entschieden:** Pilot sind zwei eigene Aral-Tankstellen mit ca. 25 Mitarbeitenden.
8. **Entschieden:** Personalplanung, MHD und Dokumentation sind die größten Problemfelder.
9. **Entschieden:** Zuerst Partner-, Tankstellen- und Mitarbeiterverwaltung sowie Zeiterfassung; danach MHD.
10. Was umfasst der MHD-Prozess genau: Artikel, Chargen, Datum, Reduzierung, Aussortierung, Abschreibung?
11. Welche HACCP-Kontrollen, Grenzwerte, Korrekturmaßnahmen und Nachweise sind erforderlich?
12. Soll Zeiterfassung später Lohnabrechnung nur vorbereiten oder vollständig integrieren?

## Geräte und Integration

13. **Teilweise entschieden:** Kassensystem ist TMS5000; verfügbare Schnittstellen sind noch zu klären.
14. **Teilweise entschieden:** Android-MDEs von Zebra, Nerugged und Netum mit Scanner/NFC;
    Pilotstandard ist Personalnummer+PIN. QR+PIN und NFC+PIN sind optionale, je Gerät
    aktivierbare Komfortverfahren. Exakte Modelle, Android-Versionen und MDM noch klären.
15. Welche Prozesse müssen bei Netzausfall vollständig funktionieren und wie lange?
16. **Entschieden:** Standard und Fallback ist Personalnummer+PIN; optional QR+PIN und
    NFC+PIN. QR oder NFC ohne persönliche PIN sind nicht zulässig.
17. **Entschieden:** Ja. Private E-Mail-Adresse, private Mobilnummer und eigenes
    Smartphone sind vollständig optional. Onboarding, Zugang, Zeiterfassung,
    Pflichtnachrichten und Recovery müssen über betriebliche Wege wie MDE, persönlichen
    Einmalcode, NFC-Ausgabe oder geschützten Ausdruck funktionieren.

## Rechte, Datenschutz und Betrieb

18. **Entschieden:** Partner und Stationsleitungen dürfen eigene Rollen erstellen und
    Rechte vergeben. Partner handeln innerhalb ihres Mandanten. Stationsleitungen dürfen
    ausschließlich Rechte für ihre zugeordneten Stationen und nur innerhalb ihres eigenen
    Berechtigungsumfangs vergeben. Plattform-, Eigentums-, Mandantenlöschungs- und
    mandantenweite Vollzugriffsrechte sind für Stationsleitungen nicht delegierbar.
    Partner oder Stationsleitung können zusätzlich eine Person als zeitlich befristete
    Vertretung zuweisen. Zeitraum, Stationen und Rechte sind verpflichtend. Innerhalb des
    aktiven Zeitraums darf die Vertretung die ausdrücklich zugewiesenen Rechte ausüben und
    nur bei gesonderter Freigabe Rollenrechte zuweisen. Sie darf ihre Vertretung weder
    verlängern noch weiterdelegieren. Nach Fristende enden alle Vertretungsrechte automatisch.
19. **Teilweise entschieden:** Zentrale Plattform-Stammdaten wie Brands,
    Kraftstoffsorten, Rechtsformen und Moduldefinitionen dürfen berechtigte
    Plattformrollen regulär und auditiert verwalten, ohne Mandantendaten zu öffnen.
    Zugriff auf Daten eines konkreten Mandanten ist nur zeitlich begrenzt,
    zweckgebunden, mit engem Scope und vollständigem Audit zulässig. Regulärer Support
    benötigt immer die vorherige Partnerfreigabe. Break-glass ohne Freigabe ist nur bei
    einem schweren Sicherheits- oder Systemvorfall zulässig, wenn eine Verzögerung den
    Schaden voraussichtlich vergrößert; der Partner wird sofort informiert und der Zugriff
    unabhängig nachkontrolliert. Regulärer Support endet spätestens nach acht Stunden,
    Break-glass spätestens nach 60 Minuten. Eine Verlängerung ist nicht möglich; weiterer
    Zugriff benötigt einen neuen Vorgang und bei regulärem Support eine neue
    Partnerfreigabe.
20. Sind GPS-Prüfung, Betriebsrat oder Tarifverträge bei der Zeiterfassung relevant?
21. Welche Aufbewahrungsfristen gelten tatsächlich je Datenkategorie?
22. Welche Verfügbarkeitsziele, Wiederanlaufzeit (RTO) und maximaler Datenverlust (RPO) werden benötigt?
23. **Entschieden:** Laravel 13.x, Filament 5.x und MySQL 8.4 LTS; Patchstände beim Projektstart festschreiben.
24. Soll der Betrieb vollständig gemanagt, selbst betrieben oder hybrid erfolgen?
25. Welcher Budgetrahmen gilt für MVP, monatlichen Betrieb und externe Prüfungen?

**Entschieden zur Anmeldung:** MFA/Passkeys werden technisch unterstützt und pro
Plattform- beziehungsweise Partnerrolle als `disabled`, `optional` oder `required`
konfigurierbar. Der Pilot startet für Plattform-Admins, Partner und Stationsleitungen
ohne verpflichtende MFA beim normalen Login. Aktionsbezogene Step-up-MFA für
Mandantensupport, Break-glass und sensible Exporte bleibt verpflichtend und nicht durch
Partnerrollen abschaltbar. Identity Provider, konkrete Faktoren und Recovery bleiben
festzulegen.

## Externe Portale

26. Wer ist „Kunde“: Endkunde, B2B-/Flottenkunde oder Shop-Besteller?
27. Welchen konkreten Vorgang erledigt ein Lieferant im Portal?
28. Müssen externe Benutzer für mehrere Betreiber arbeiten können?

## Bestätigte Partner-/Stationsentscheidungen

- Super-Admins dürfen Partner neben der öffentlichen Selbstregistrierung manuell anlegen
  und den vorgesehenen Owner sicher einladen.
- Pro Mandant existiert genau ein aktiver Owner; weitere Verantwortliche werden als
  Administrator oder eigene Partnerrolle geführt.
- Die öffentliche Partnerregistrierung bleibt datenarm; Unternehmensdetails folgen im
  geschützten Onboarding.
- Ein Schließungsantrag setzt zunächst `closure_requested` und löst keine Sofortlöschung aus.
- Trial-Erinnerungen werden 7, 3 und 1 Tag vor Ablauf versendet.
- Nach E-Mail-Bestätigung entstehen automatisch Mandant und 14-tägige Testphase.
- Partner, Leitungen, Vertretungen und Mitarbeitende mit mehreren aktuell berechtigten
  Stationen wählen vor operativer Arbeit bewusst eine Station. Bei genau einer Station
  darf Merlin automatisch auswählen. Befristete Zuordnungen sind nur innerhalb ihrer
  Gültigkeit sichtbar und wirksam; das registrierte MDE bleibt fest an eine Station
  gebunden.
- Nach Trial-Ablauf wird der Mandant automatisch auf Nur-Lesen gesetzt. Lesen und Export
  bleiben erlaubt; fachliche Schreibvorgänge bleiben bis zu einer Verlängerung oder einem
  späteren Abonnement gesperrt.
- Eine Trial-Verlängerung ist nur einmal, nur durch einen Super-Admin und nur mit Grund,
  um genau 14 weitere Tage und mit Auditprotokoll zulässig.
- Partner dürfen ihre Gesellschaft und Tankstellen selbst anlegen.
- Brands bleiben zentrale, nur von Plattformrollen änderbare Stammdaten.
- Das Stationsmodell muss allgemeine, Adress-/Kontakt-, Öffnungszeiten-, Shop-, Karten-,
  Partner-/Abrechnungs- sowie Steuer-/Behördenkennungen abbilden.
- Reale Produktionswerte werden nicht in Planungsdateien oder Testdaten übernommen.
- Passwörter oder Zugangscodes werden niemals als normale Stationsstammdaten gespeichert.
- Das geschützte Onboarding erfasst optional eine Bankverbindung der rechtlichen
  Gesellschaft. Ein globales, versioniertes Bankverzeichnis wird aus einer durch
  Plattformrollen verwalteten Bundesbank-Quelle importiert; Änderungen, Gültigkeit und
  Aktivierung werden auditiert. Die öffentliche Registrierung bleibt datenarm.
- **Entschieden:** Merlin importiert ausschließlich die öffentliche Bundesbank-CSV mit den
  Bankdaten. Ein NExt-Zugang und institutsspezifische IBAN-Regeln sind nicht vorgesehen.
  Der IBAN-Rechner bildet deutsche IBANs nach der Standardformel aus BLZ und Kontonummer,
  prüft Modulo 97 und weist sichtbar darauf hin, dass das Ergebnis weder Kontoexistenz
  noch Kontoinhaberschaft bestätigt und Sonderregeln nicht berücksichtigt.

## Bestätigte Pilotdetails Zeiterfassung

- Beide Stationen gehören demselben Betreiber; die genaue juristische Gesellschaft wird
  als Einzelunternehmen in Hessen geführt.
- Im Pilot kommen Vollzeit, Teilzeit, Minijob, Ausbildung und minderjährige Beschäftigte vor.
- Eine Station ist 24/7 geöffnet; Nacht- und Mitternachtsfälle sind Pflichtfälle.
- Es existiert aktuell kein digitaler Pausenprozess; Pausenregeln und Bedienablauf werden neu eingeführt.
- Bezahlte Pausen und Alleinarbeit kommen vor und benötigen explizite Regeln.
- Exporte werden mindestens als CSV und menschenlesbares PDF benötigt; Zielsysteme sind
  DATEV, ADDISON und eurodata, deren genaue Importvarianten noch abzustimmen sind.
- Offline-Anmeldung und Stempeln müssen am registrierten Android-MDE auch bei vollständigem Netzausfall funktionieren.
- Verbindlicher Offlinezeitraum im Pilot: 48 Stunden.
- **Risiko akzeptiert:** Ein zentral gesperrter Mitarbeiter kann auf einem bereits
  synchronisierten, weiterhin offline betriebenen MDE bis zum Ablauf des 48-Stunden-
  Pakets noch stempeln. Beim nächsten Sync werden solche Ereignisse anhand von
  Credential-/Widerrufsversion und Zeitbezug als Prüffall markiert, auditiert und der
  zuständigen Leitung angezeigt. Sie werden nicht still verworfen.
- MDE-Pilotstandard ist Personalnummer+PIN. QR+PIN und NFC+PIN können je registriertem
  Gerät optional aktiviert werden. Personalnummer+PIN bleibt immer der Fallback.
- Während bezahlter Unterbrechungen müssen Mitarbeitende weiterhin Kunden/Kasse betreuen;
  dies ist keine automatisch als Ruhepause anzurechnende Freistellung.
- Pausenabzüge sind je Partner beziehungsweise Arbeitgeber als versionierte Regel
  einstellbar. Unterstützt werden manuelle Pausen, reine Warnungen und automatische
  Abzüge mit konfigurierbaren Schwellen, Dauern und Beschäftigtengruppen. Automatische
  Abzüge verändern keine Rohbuchungen, gelten nicht rückwirkend und müssen für
  Mitarbeitende sichtbar und per Korrekturantrag anfechtbar sein. Für beide
  Pilot-Tankstellen startet das Regelprofil im Modus `warning`: Fehlende oder zu kurze
  Pausen erzeugen Hinweise, aber keinen automatischen Abzug. Die konkreten Warnschwellen
  und die arbeitsrechtliche Freigabe bleiben offen.
- Zeitkorrekturen dürfen durch den Partner, die für die betroffene Station zuständige
  Stationsleitung oder eine zeitlich aktive Vertretung mit ausdrücklichem
  Korrekturprüfungsrecht genehmigt oder abgelehnt werden. Antragsteller, betroffene Person
  und Ersteller einer manuellen Änderung dürfen dieselbe Korrektur nicht genehmigen.
- Mitarbeiterdaten existieren noch nicht digital; ein Ersterfassungs-/Importprozess ist erforderlich.
- Private E-Mail, Mobilnummer und Smartphone sind keine Voraussetzung. Pflichtnachrichten
  werden nach persönlicher Anmeldung auch in einem geschützten MDE-Postfach bereitgestellt.
- Onboarding: minimaler Voreintrag, Einladungslink und Selbsterfassung durch den
  Mitarbeiter. Danach genügt die Freigabe entweder durch den Partner oder durch die
  zuständige Stationsleitung. Der Partner darf alle zugewiesenen Stationen des eigenen
  Mandanten freigeben; die Stationsleitung ausschließlich ihre zugeordneten Stationen.
  Bei mehreren Stationen wird jede Stationszuordnung separat freigegeben, sofern der
  Partner nicht alle gemeinsam bestätigt.
- Steuerberater erhalten kein Plattformkonto. Payroll-Exporte werden geschützt per E-Mail
  an bestätigte Empfänger gesendet; Partner können zusätzlich manuell herunterladen.
- Im Pilot gibt es keinen Betriebsrat; das Produkt berücksichtigt Betriebsrat, Tarifvertrag
  und Betriebsvereinbarungen dennoch je Partner/Arbeitgeber.
