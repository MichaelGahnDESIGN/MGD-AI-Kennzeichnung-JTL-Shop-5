# Entwurf: Darstellung, Live-Vorschau und Release 1.2.1

## Ziel

Version **1.2.1** verbessert die Bedienbarkeit der globalen KI-Kennzeichnung im JTL-Administrationsbereich. Shop-Betreiber sollen die sichtbare Kennzeichnung verständlich konfigurieren und ihre Wirkung vor dem Speichern unmittelbar an einem lokalen Beispielbild prüfen können.

Zusätzlich wird die freiwillige Herstellernennung sprachlich angepasst und die datensparsame GitHub-Versionsprüfung bei Neuinstallationen standardmäßig aktiviert.

## Bedienkonzept

Das Plugin erhält zwischen **AI-Philosophie** und **Impressum** den neuen geschützten Tab **Darstellung**. Eine eigene Plugin-Seite ist gegenüber einem Eingriff in die generische JTL-Einstellungsansicht vorzuziehen: Sie benötigt keine Änderung am Shop-Kern, bleibt über JTL-Updates hinweg nachvollziehbar und kann die gewünschte Live-Vorschau barrierearm abbilden.

Die Seite besteht auf ausreichend breiten Bildschirmen aus zwei Spalten:

- links stehen die Einstellungen mit klaren Bezeichnungen, Einheiten, Wertebereichen und einem bewussten Speichern-Button;
- rechts erscheint eine feststehende Vorschau mit einem lokal mitgelieferten Produktbild „Michael Gahn DESIGN Schuh“ und dem Kennzeichnungsstatus **KI-generiert**.

Auf schmalen Bildschirmen werden die Bereiche untereinander angeordnet. Die Vorschau folgt den Einstellungen, damit Formulareingaben in einer natürlichen Reihenfolge erreichbar bleiben.

## Darstellungsoptionen

Die Seite übernimmt alle bereits vorhandenen globalen Darstellungswerte:

- Sprache der Kennzeichnung;
- Schriftgröße;
- Außenabstand;
- Innenabstand;
- Eckenradius;
- Hintergrundunschärfe.

Position und Farbschema bleiben Eigenschaften der jeweiligen Bildkennzeichnung und werden weiterhin in der Bildverwaltung gespeichert. Die derzeit zusätzlich vorhandenen, aber im Frontend wirkungslosen globalen Felder werden aus der allgemeinen Einstellungsansicht entfernt. In der Vorschau dürfen Position und Farbschema weiterhin ausprobiert werden; die Oberfläche kennzeichnet diese beiden Bedienelemente eindeutig als **Vorschau**, damit kein wirkungsloser globaler Wert vorgetäuscht wird.

Neu hinzu kommt die **Transparenz des Label-Hintergrunds**. Sie wird in Prozent angegeben. `0 %` bedeutet einen vollständig deckenden Hintergrund; `90 %` ist die zugelassene Obergrenze. Text und Rahmen bleiben unabhängig davon sichtbar. Der Standardwert beträgt `8 %` und entspricht annähernd der bisherigen dunklen Darstellung.

Eckenradius, Hintergrundunschärfe und Transparenz besitzen jeweils:

1. ein beschriftetes Zahlenfeld für eine genaue Eingabe;
2. einen synchronisierten Schieberegler für eine schnelle visuelle Anpassung;
3. eine sichtbare Einheit und einen erklärten sicheren Wertebereich.

Die bestehenden Grenzen bleiben erhalten:

- Eckenradius: 0 bis 32 Pixel;
- Hintergrundunschärfe: 0 bis 24 Pixel;
- Transparenz: 0 bis 90 Prozent.

Alle übrigen Zahlenwerte behalten ebenfalls ihre bereits implementierten sicheren Grenzen.

## Live-Vorschau

Jede gültige Änderung wird sofort ausschließlich in der lokalen Vorschau dargestellt. Die Vorschau verändert weder den öffentlichen Shop noch gespeicherte Daten. Erst der Button **Speichern** überträgt die geprüften Werte an den Server.

Das Beispielbild wird als optimierte lokale Bilddatei Bestandteil des Plugins. Es lädt keine externe Ressource, enthält keine personenbezogenen Daten und löst keine Netzwerkanfrage aus. Bildtitel und Alternativtext benennen das fiktive Produkt verständlich als „Michael Gahn DESIGN Schuh“.

Die JavaScript-Logik wird in kleine Module aufgeteilt:

- Synchronisierung von Zahlenfeld und Schieberegler;
- Berechnung und Anwendung der Vorschauvariablen;
- Formularstatus und Rücksetzen auf gespeicherte Werte.

Ohne JavaScript bleibt das Formular vollständig speicherbar; lediglich die unmittelbare Vorschauaktualisierung und die Regler-Synchronisierung entfallen.

## Speicherung und Abwärtskompatibilität

Die Darstellungswerte bleiben bewusst in JTLs vorhandener Plugin-Konfiguration. JTL-Shop unterstützt seit Version 5 den Einstellungstyp `none`: Der Wert bleibt Bestandteil der Plugininstanz, wird aber nicht zusätzlich in der generischen Einstellungsansicht ausgegeben. Genau dieser vorgesehene Weg wird für Sprache, Schriftgröße, Außenabstand, Innenabstand, Eckenradius, Hintergrundunschärfe und Transparenz verwendet.

Der neue Darstellungstab liest und schreibt damit dieselben kanonischen Werte wie das Frontend. Es entstehen weder eine zweite Einstellungsquelle noch eine zusätzliche Datenbanktabelle. Nach dem Speichern wird der zugehörige JTL-Plugin-Cache gezielt invalidiert, damit die neuen Werte ohne manuellen Cache-Eingriff gelten.

Beim Update werden die unveränderten `ValueName`-Schlüssel weiterverwendet. Dadurch bleiben die bisherigen wirksamen Werte erhalten. Die neuen Transparenzwerte erhalten nur dann den Standardwert, wenn noch kein Wert gespeichert ist. Die bisher sichtbaren globalen Positionierungs- und Farbschemafelder werden nicht übernommen, weil diese Werte im Frontend nie ausgewertet wurden; die tatsächlich je Bild gespeicherten Werte bleiben unverändert erhalten.

## Validierung und Sicherheit

Der Darstellungstab ist ausschließlich über den authentifizierten JTL-Plugin-Kontext erreichbar. Für Lesen und Speichern gelten dieselben Admin-Berechtigungen wie für die Bildverwaltung.

Beim Speichern gelten folgende Regeln:

- ausschließlich POST für Änderungen;
- gültiges JTL-CSRF-Token;
- feste Liste erlaubter Formularfelder;
- feste Auswahlwerte für Sprache sowie getrennte, nicht gespeicherte Vorschauwerte für Position und Farbschema;
- streng geprüfte Ganzzahlen innerhalb der dokumentierten Grenzen;
- Speicherung ausschließlich über die vorhandene JTL-Plugin-Konfiguration mit anschließender Cache-Invalidierung;
- keine Ausgabe roher Eingabewerte in Fehlermeldungen oder Logs.

Freie CSS-Klassen, HTML, URLs oder beliebige Labeltexte werden nicht angenommen. Dadurch kann die neue Seite weder Skriptcode noch fremde Ressourcen in den Shop einschleusen.

## Herstellernennung

Wenn die freiwillige Herstellernennung aktiviert ist, lautet die sichtbare Ausgabe künftig:

> supported by: Michael Gahn DESIGN

Nur **Michael Gahn DESIGN** ist verlinkt. Das Ziel ist `https://Michael-Gahn.de` und öffnet sich mit `target="_blank"` sowie `rel="noopener noreferrer"` in einem neuen Tab. Der zugängliche Linktext erklärt das neue Fenster. Es gibt weiterhin kein Tracking und keine externe Einbindung.

## GitHub-Updatehinweise

`Updatehinweise über GitHub abrufen` erhält für **Neuinstallationen** den Standardwert **Ja**. Bestehende Installationen behalten ihren bereits gespeicherten Wert und werden durch das Update nicht ungefragt überschrieben.

Die Prüfung wird erstmals vollständig an den geschützten Plugin-Adminbereich angebunden. Ein Aufruf durch Shop-Kunden oder normale Frontend-Seiten darf niemals eine GitHub-Abfrage auslösen. Die vorhandenen Datenschutz- und Lastschutzregeln bleiben bestehen: Es werden höchstens einmal innerhalb von zwölf Stunden Release-Metadaten abgefragt, ohne Bilder, Kunden- oder Bestelldaten zu übertragen. GitHub erhält technisch die Server-IP, den Zeitpunkt und einen auf Version 1.2.1 aktualisierten Plugin-User-Agent; die Dokumentation weist verständlich darauf hin.

Das GitHub-Repository ist zum Entwurfszeitpunkt privat. Eine anonyme GitHub-API-Abfrage kann deshalb keinen Release erkennen. Das Plugin speichert aus Sicherheitsgründen keinen persönlichen GitHub-Token und umgeht diese Einschränkung nicht. Der Schalter und der sichere Checker werden vorbereitet; ein sichtbarer automatischer Hinweis funktioniert erst, wenn der Release-Endpunkt öffentlich erreichbar ist.

Version 1.2.1 implementiert **keinen automatischen Download und keine automatische Installation**. Der vorgesehene Updateweg ist der kontrollierte Upload des signierten beziehungsweise per SHA-256 geprüften Plugin-ZIPs im JTL-Plugin-Manager. Der anschließende Shop-Test prüft diesen Updateweg von 1.2.0 auf 1.2.1 und nicht einen unbeaufsichtigten Auto-Updater.

## Technische Struktur

Die Umsetzung wird in klar getrennte Dateien gegliedert:

- Menü-Einstieg und Template für den Darstellungstab;
- Admin-Service für Berechtigung, CSRF-Prüfung, Validierung und JTL-Konfigurationsspeicherung;
- unveränderliches Wertemodell für geprüfte Einstellungen;
- getrennte CSS-Datei für das zweispaltige Layout und die Vorschau;
- getrennte JavaScript-Module für Feldkopplung und Live-Vorschau;
- lokale Bilddatei für den fiktiven Schuh;
- kleine Unit-, Integrations-, Struktur- und JavaScript-Tests.

Der Frontend-Bootstrap lädt ausschließlich geprüfte Werte aus der JTL-Plugin-Konfiguration. Bei fehlenden oder ungültigen Werten gelten sichere interne Standards; die öffentliche Shop-Seite darf deshalb nicht ausfallen.

Die Transparenz wird vollständig durch alle Ausgabewege geführt: Konfigurationsmodell, Resolver, unveränderliches Label-Modell, PHP-Renderer, Smarty-Template, JavaScript-Erweiterung und CSS. Tests decken dunkles, helles und automatisches Farbschema sowie native, JavaScript-erweiterte und JavaScript-freie Labels ab.

## Dokumentation und Veröffentlichung

Folgende Artefakte werden für **1.2.1** aktualisiert:

- Plugin-Version in `info.xml`;
- README und Changelog;
- versioniertes Wiki beziehungsweise Benutzerhandbuch;
- Release-Prüfungen und Paketmetadaten;
- Installations-ZIP mit Prüfsumme;
- Git-Commit auf `main`, Tag `v1.2.1` und GitHub-Release mit dem ZIP als Asset.

Eine Installation auf `dev.onvis-shop.de` oder `onvis-shop.de` ist nicht Bestandteil dieses Schritts. Anschließend kann das veröffentlichte ZIP im JTL-Plugin-Manager als Update von 1.2.0 auf 1.2.1 getestet werden. Wegen des privaten Repositorys wird ausdrücklich nicht behauptet, dass JTL oder die anonyme GitHub-Prüfung das Release automatisch erkennen kann.

Die Releaseprüfung stellt zusätzlich sicher, dass ZIP-Dateiname, interne `info.xml`-Version, Buildskript, Git-Tag, GitHub-Release-Asset und SHA-256-Datei exakt Version 1.2.1 nennen. Der aktuell veraltete ZIP-Pfad im CI-Workflow wird korrigiert.

## Abnahme

Die Umsetzung ist abgenommen, wenn:

1. der neue Darstellungstab ohne Änderung am JTL-Shop-Kern erreichbar ist;
2. die wirksamen gespeicherten Werte eines Updates auf 1.2.1 erhalten bleiben und nur die tatsächlich pro Bild gespeicherten Werte Position und Farbschema bestimmen;
3. Eckenradius, Unschärfe und Transparenz Zahlenfeld und Regler synchron anbieten;
4. alle Designwerte die lokale Vorschau unmittelbar und korrekt verändern;
5. ungültige, fremde oder nicht autorisierte Anfragen sicher abgewiesen werden;
6. Transparenz in allen Theme- und Ausgabewegen wirkt und der öffentliche Shop bei fehlenden Darstellungswerten mit sicheren Rückfallwerten weiterläuft;
7. die Herstellernennung exakt wie freigegeben ausgegeben und sicher verlinkt wird;
8. Updatehinweise bei Neuinstallationen standardmäßig aktiviert sind, ausschließlich im authentifizierten Adminbereich prüfen und bestehende Entscheidungen nicht überschreiben;
9. alle automatisierten Tests, statischen Analysen und Release-Paketprüfungen erfolgreich sind;
10. ein Updateversuch mit dem 1.2.1-ZIP vorhandene Plugin-Daten erhält und bei Fehlern nachvollziehbar abbricht;
11. GitHub `v1.2.1` mit dokumentiertem ZIP und SHA-256-Prüfsumme veröffentlicht und alle Versionsangaben exakt übereinstimmen.
