# Rekursiver OPC-Dateispeicherscan

## Status und Freigabe

Michael hat am 1. September 2026 den ergänzenden, sicheren Dateisystemscanner
freigegeben. Sein Beispiel zeigt Bilder unter `opc/banner/2026`; auch
`opc/bilder/2026`, Ordner mit Leerzeichen und weitere Verschachtelungen müssen
berücksichtigt werden. Diese Spezifikation konkretisiert das bestätigte Design.
Sie beschreibt geplantes Verhalten, noch keine fertiggestellte Funktion.

## Ziel aus Sicht der Nutzer

Nach einem Klick auf **Sicheren Bildscan starten** zeigt die bestehende Galerie
sämtliche unterstützten Rasterbilddateien im offiziellen OPC-Dateispeicher an:
sowohl bereits in OPC-Seiten verwendete als auch nur hochgeladene Bilder, im
Hauptverzeichnis und in Unterordnern innerhalb der unten beschriebenen Grenzen.

Die bestehenden Filter **Status**, **Quelle** und **Fundstelle**, die Sortierung
und die Seitengröße funktionieren unverändert. Die Quelle dieser Dateien ist
**OnPage Composer**. Ein zusätzlicher Ordnerbaum oder ein neuer Ordnerfilter ist
nicht Bestandteil dieser Erweiterung.

## Ausgangslage

Ausgangsstand ist Git-Commit `efb7f2c`, Pluginversion 1.3.4. Der bisherige
`Scanner/Adapter/OpcSourceAdapter.php` liest Bildreferenzen aus gespeicherten
OPC-Seiten in `topcpage.cAreasJson`. Er durchsucht deren Struktur bereits
rekursiv, listet aber keine Dateien des Uploadverzeichnisses auf. Deshalb fehlen
hochgeladene Bilder ohne erkannte OPC-Seitenreferenz unabhängig von ihrer
Ordnertiefe.

`Service/ImageScanService.php` führt den gesamten Abgleich über
`AssetRepository` und `UsageRepository` in einer Transaktion aus. Erst ein
vollständiger erfolgreicher Lauf markiert nicht mehr gefundene Fundstellen als
veraltet. Dieses Sicherheitsverhalten bleibt erhalten.

## Architekturentscheidung

Ein eigener Dateisystemscanner ergänzt den bestehenden OPC-Datenbankscanner.
Die Alternativen, beide Aufgaben in einer Sammelklasse zu vermischen oder
elFinder im Browser auszulesen, wurden zugunsten klarer Zuständigkeiten und
unabhängiger Tests verworfen.

Die Implementierung trennt mindestens diese Aufgaben in eigene Dateien:

- Auflösen und Prüfen des festen OPC-Speicherpfads aus dem JTL-Shopverzeichnis;
- begrenztes rekursives Auflisten zulässiger lokaler Dateipfade;
- Übertragen dieser Pfade in seitenweise verarbeitbare OPC-Fundstellen;
- Einbinden in die bestehende Admin-Factory und atomare Scan-Orchestrierung.

Die relativen Bildpfade werden weiterhin durch `LocalPathNormalizer` geprüft.
Der Dateiscanner erhält seinen Shopwurzelpfad aus dem serverseitigen
JTL-Kontext, nicht aus Formularfeldern oder URL-Parametern.

## Umfang und Pfadsicherheit

- Einziger Ausgangspunkt ist `<Shopwurzel>/media/image/storage/opc/`.
- Die im Dateimanager sichtbaren Ordnernamen sind relativ zu diesem Speicher.
- Zulässig sind reguläre Dateien mit den Endungen JPG, JPEG, PNG, WebP, GIF
  oder AVIF, unabhängig von der Groß-/Kleinschreibung der Endung.
- SVG, Videos, Nicht-Bilder und Sonderdateien werden nicht aufgenommen.
- Symlink-Dateien und Symlink-Unterverzeichnisse werden übersprungen und
  niemals verfolgt. Auch der feste Speicherpfad selbst darf nicht über einen
  Symlink aus der freigegebenen Shopwurzel herausführen.
- Die kanonische Pfadzuordnung muss innerhalb der freigegebenen Wurzel bleiben;
  ein einfacher Zeichenkettenpräfix ohne Verzeichnisgrenze genügt nicht.
- Absolute Serverpfade, Zugangsdaten und Dateiinhalte werden weder in
  Fehlermeldungen noch in Logs oder der Galerie ausgegeben.
- Es werden ausschließlich Namen und notwendige Dateisystem-Metadaten gelesen.
  Keine Bildanalyse, keine Volltextsuche und kein externer Netzwerkabruf.

Die Endungsprüfung ist keine inhaltliche MIME- oder Malwareprüfung. Der
Scanner verändert daher nicht die bestehenden Sicherheitsanforderungen an
JTLs Uploadfunktion und führt keine gefundenen Dateien aus.

## Grenzen und vollständige Verarbeitung

Ein Lauf erlaubt höchstens 9.999 unterstützte Bilddateien, 20.000 untersuchte
Verzeichniseinträge insgesamt und 32 Unterverzeichnisebenen unterhalb des
OPC-Speichers. Die Speicherwurzel hat Tiefe 0. Die Bildgrenze passt zum
bestehenden Seitenvertrag der Scan-Orchestrierung; auch bei genau 9.999 Bildern
muss ein vollständiges Ende nachweisbar sein.

Eine Überschreitung führt zu einer verständlichen Fehlermeldung und zum
Rückrollen des gesamten Abgleichs. Es darf niemals kommentarlos nur die erste
Teilmenge als vollständiges Ergebnis gelten. Benutzer erhalten einen Hinweis,
welche feste Grenze erreicht wurde, aber keinen absoluten Serverpfad.

Die Auflistung hält nur eine begrenzte Pfadmenge im Speicher, liest Ordner
eintragsweise und sortiert anschließend die normalisierten relativen Pfade
deterministisch. Dieselben Verzeichnisse werden nicht für jede Ergebnisseite
erneut vollständig durchsucht. Ein weiterer Scan baut eine neue Auflistung auf;
ein alter Lauf darf keine veraltete interne Dateiliste wiederverwenden.

Ein unlesbares Verzeichnis, ein nicht eindeutig prüfbarer Speicherpfad oder
ein Fehler während des Durchlaufens beendet den Scan. Ein fehlendes
OPC-Speicherverzeichnis wird ebenfalls als nicht vollständig prüfbare Quelle
gemeldet, nicht stillschweigend als leerer Speicher interpretiert. Ein
vorhandenes, lesbares, tatsächlich leeres Verzeichnis ist dagegen ein gültiges
leeres Ergebnis.

Das Dateisystem ist kein transaktionaler Schnappschuss: Bei parallelen Uploads,
Verschiebungen oder Löschungen kann ein erneuter Scan nötig sein. Erkannte
Lese- oder Konsistenzfehler dürfen nicht in eine erfolgreiche Teilauflistung
umgewandelt werden. Es wird keine lückenlose Erkennung beliebiger gleichzeitiger
Dateisystemänderungen versprochen.

## Bildidentität und Fundstellen

Die vorhandene Bildidentität bleibt der Hash des normalisierten relativen
Bildpfads. Ein Bild, das sowohl auf einer OPC-Seite referenziert als auch im
Speicher gefunden wird, bleibt deshalb eine einzige Galerie-Karte.

Die zusätzliche Dateifundstelle erhält den Quellentyp `opc`, einen stabilen
technischen Schlüssel mit eigenem Präfix `opc-datei:` und dem Hash des
normalisierten Pfads sowie den lesbaren Kontext **OPC-Dateispeicher**.
Bestehende `opc-seite:`-Fundstellen bleiben getrennt erhalten.

Die Orchestrierung muss beide OPC-Beiträge innerhalb desselben vollständigen
Abgleichs berücksichtigen. Die bisherige Sperre gegen doppelte Quellentypen
darf nicht unkontrolliert entfernt werden: Die Kombination wird ausdrücklich
für die getrennten OPC-Seiten- und Dateispeicherbeiträge abgedeckt und getestet.
Ein unbekannter Quellentyp, ein fremder Fundstellentyp oder eine versehentlich
doppelte Registrierung desselben Beitrags bleibt ein Fehler.

Eine neue Dateifundstelle steht auf **Vorhanden**, auch wenn die Datei noch
keiner OPC-Seite zugewiesen ist. Die Fundstellenzahl beschreibt damit nicht
mehr ausschließlich die Anzahl der Verwendungen auf Seiten. Galerie-Hilfe,
Details und Dokumentation müssen diesen Unterschied verständlich erklären.

Nach einer Dateilöschung kann nur die Dateifundstelle veralten; eine weiterhin
gespeicherte OPC-Seitenreferenz bleibt eine eigenständige Fundstelle. Der Scan
löscht keine Originaldateien, Bilddatensätze oder Kennzeichnungen. Vorhandene
Kennzeichnungsstatus, Positionen und Darstellungen bleiben unverändert.

Ein Umbenennen oder Verschieben erzeugt nach bestehender Pfadidentität ein
neues Bild; eine automatische Übertragung der bisherigen Kennzeichnung anhand
von Dateiinhalten ist ausdrücklich nicht vorgesehen.

## Oberfläche und Bedienung

Es bleibt bei der bisherigen Galerie und dem vorhandenen Scan-Button. Neue
Bilder erscheinen mit **Ungeprüft** und können einzeln oder über die bestehenden
Stapelaktionen bearbeitet werden. Alle Bilder aus unterschiedlichen
Unterordnern bleiben über die bestehende Quelle **OnPage Composer** filterbar.

Filter, Sortierung, Seitenwechsel und erneuter Scan dürfen weder Bilder mit
gleichem Dateinamen in verschiedenen Ordnern zusammenlegen noch gleiche
Dateipfade doppelt anzeigen. Die Dateipfade bleiben in der Detailansicht
nachvollziehbar.

## Tests und Abnahme

Vor Produktionscode werden die fehlenden Fälle als fehlschlagende Tests
geschrieben. Die Abnahme deckt mindestens ab:

1. Bilder im Speicherhauptverzeichnis sowie in `banner/2026` und drei oder
   mehr weiteren Verschachtelungen werden gefunden.
2. Leerzeichen, Umlaute und alle freigegebenen Endungen funktionieren im
   Rahmen des bestehenden Pfadnormalisierers.
3. Gleiche Dateinamen in unterschiedlichen Ordnern bleiben unterschiedliche
   Bilder; dieselbe Datei aus Datenbank und Dateiscan bleibt ein Bild.
4. Nicht unterstützte Dateien und Symlinks, auch Schleifen und Links nach
   außerhalb, werden nicht verfolgt oder aufgenommen.
5. Leerer Speicher, fehlender Speicher und Lesefehler liefern jeweils das
   definierte Ergebnis; Grenzüberschreitungen brechen atomar ab.
6. Seitengrenzen, genau 9.999 Bilder und ein weiterer Scan mit veränderten
   Dateien werden korrekt verarbeitet.
7. Dateifundstellen und OPC-Seitenfundstellen bleiben getrennt; vorhandene
   Kennzeichnungen werden nicht zurückgesetzt.
8. Fehler lassen bisherige Daten und Vorhanden-/Veraltet-Zustände unverändert.
9. Galerie-Filter, Sortierung, Ergebniszahl und Seitennavigation erfassen die
   neu gefundenen Bilder ohne doppelte Karten.
10. Bestehende PHP-/JavaScript-Tests, statische Analyse, Formatprüfung und
    installierbares Release-Paket werden nach der Umsetzung erneut geprüft.

Eine Prüfung des zweiten Testshops erfordert dessen tatsächliche Laufzeit und
Ordnerstruktur; das bereitgestellte Bildschirmfoto allein ersetzt diesen
Integrationstest nicht. Die erste Shop-Abnahme erfolgt ausschließlich in der
freigegebenen Dev-Umgebung, niemals durch einen unangekündigten Live-Eingriff.

## Auslieferung und Rückweg

Diese Spezifikation verändert weder Pluginversion noch Shopdaten. Es ist keine
neue Datenbanktabelle oder Schemainstallation für den Entwurf vorgesehen.
Geplante Änderungen betreffen nur Plugin-Code, Tests und verständliche
Dokumentation.

Ein späterer Release muss klar zwischen lokal bestandenen Tests und tatsächlich
auf Dev geprüftem Verhalten unterscheiden. Vor einer Installation werden der
bisherige Pluginstand und die Plugin-Tabellen gesichert. Ein Rückweg verwendet
diese zusammengehörige Sicherung; neu entstandene Dateifundstellen dürfen beim
Zurückgehen auf einen älteren Scanner nicht versehentlich als unverändertes
Verhalten dargestellt werden. Eine Deinstallation mit Datenlöschung ist kein
Rückweg.

Ein automatisches Live-Deployment, neue kostenpflichtige Dienste und externe
Bild-, Schrift-, Icon- oder Analysequellen sind ausgeschlossen.

## Arbeitscheckliste

- [x] Ausgangslage und Projektkontext untersucht.
- [x] Umfang inklusive unbenutzter Dateien bestätigt.
- [x] Ansätze verglichen und getrennten Dateisystemscanner freigegeben.
- [x] Design schriftlich konkretisiert.
- [x] Spezifikation auf Widersprüche, Platzhalter und Umfang geprüft.
- [ ] Schriftliche Spezifikation von Michael geprüft.
- [ ] Implementierungsplan erstellt.
- [ ] Testgetrieben implementiert und vollständig verifiziert.

Eine visuelle Entwurfsbegleitung ist nicht erforderlich: Das Galerielayout
bleibt unverändert, und das Bildschirmfoto dient als konkretes Ordnerbeispiel.
