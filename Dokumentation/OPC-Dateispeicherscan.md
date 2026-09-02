# Rekursiver OPC-Dateispeicherscan

Stand: lokales Testpaket 1.3.6 vom 2. September 2026, noch nicht öffentlich
veröffentlicht. Diese Anleitung beschreibt die
neue Erweiterung, nicht das Verhalten des unveränderten Releases 1.3.4.

## Was sich verbessert

Bisher las das Plugin Bildverweise aus gespeicherten OPC-Seiten. Ein Bild konnte
im Uploader vorhanden sein, ohne in der Galerie aufzutauchen. Der zusätzliche
Dateiscan erfasst nun auch unbenutzte Uploads im Hauptordner und in Unterordnern.
Beispiele sind `opc/banner/2026`, `opc/bilder/2026` und weitere Verschachtelungen.

Seit 1.3.6 wird elFinders interner Vorschaubildcache `.tmb` auf jeder Ebene
ausgelassen. In 1.3.5 führte dieser Cache bei Campingteile24 zum vollständigen
Abbruch. Die echten Bilder und die Sicherheitsprüfung bleiben unverändert.

Nach Installation der Erweiterung:

1. Bildverwaltung öffnen.
2. **Sicheren Bildscan starten** wählen und Erfolgsmeldung abwarten.
3. Zur Galerie zurückkehren.
4. Quelle **OnPage Composer** auswählen und **Galerie anzeigen** klicken.
5. Bilder wie gewohnt einzeln oder im Stapel kennzeichnen.

Die bestehenden Filter, die Sortierung und 10/25/50/100 Karten pro Seite bleiben
erhalten. Ein neuer Ordnerbaum oder eine Suche nach Ordnernamen gehört nicht zu
dieser Änderung. Unter **Details** steht der relative Pfad.

## Fundstelle ist nicht gleich Veröffentlichung

Das Plugin unterscheidet intern:

- **OPC-Seitenverweis:** Bildpfad in einer gespeicherten OPC-Seite;
- **OPC-Dateispeicher:** reguläre Rasterdatei im offiziellen Uploadspeicher.

Eine Datei kann beide Fundstellen besitzen, erscheint aber nur auf einer Karte.
Die Anzahl der Fundstellen ist deshalb keine reine Zahl veröffentlichter
Platzierungen. Schon eine noch unbenutzte Datei gilt als im Speicher vorhanden.
Wird sie gelöscht, kann die Dateifundstelle veralten, während ein weiterhin
gespeicherter Seitenverweis bestehen bleibt. Die Galerie zeigt dann eventuell
einen Platzhalter, bis auch der Seiteninhalt korrigiert wurde.

Bestehende Statuswerte, Positionen und Darstellungen werden nicht überschrieben.
Der Scanner löscht weder Dateien noch Bilddatensätze. Verschieben oder
Umbenennen erzeugt eine neue pfadbasierte Zuordnung; die Kennzeichnung muss
für diesen neuen Pfad erneut geprüft werden.

## Sicherheitsgrenzen

Der feste Pfad ist `media/image/storage/opc/` relativ zur serverseitigen
Shopwurzel. Besucher und Formulare können kein anderes Startverzeichnis wählen.

| Grenze | Verhalten |
| --- | --- |
| 9.999 unterstützte Rasterbilder | Größere Bestände brechen den Lauf ab. |
| 20.000 Verzeichniseinträge | Auch nicht unterstützte Dateien und Links zählen zur Grenze. |
| 32 Unterordnerebenen | Die Speicherwurzel selbst hat Tiefe 0. |
| JPG, JPEG, PNG, WebP, GIF, AVIF | Groß-/Kleinschreibung der Endung ist unerheblich. |
| SVG, Videos, Sonderdateien | Werden nicht aufgenommen. |
| Symbolische Links | Werden nicht verfolgt; Links im festen Speicherwurzelpfad verhindern den Scan. |

Ein lesbarer, tatsächlich leerer Speicher ist gültig. Ein fehlender oder nicht
lesbarer Speicher ist dagegen kein Nachweis, dass bisherige Bilder verschwunden
sind: Der gesamte Bildabgleich wird zurückgerollt. Die Oberfläche nennt nur
feste, verständliche Fehlergründe, niemals absolute Serverpfade oder technische
Ausnahmedetails.

Der zentrale Pfadschutz bleibt maßgeblich. Dateinamen mit URL-Codierungen
(beispielsweise einem wörtlichen `%20`), führenden/abschließenden Leerzeichen
oder Punkten sowie uneindeutigen Sonderzeichen können nicht sicher zugeordnet
werden und führen zu einem erklärten Abbruch. Normale Leerzeichen innerhalb
eines Namens und Umlaute funktionieren. Nicht einfach den Pfadschutz abschalten.

Es werden ausschließlich Namen und erforderliche Metadaten gelesen, keine
Bildinhalte analysiert. Die Endungsprüfung ist kein Malware- oder MIME-Scanner.
Die Sicherheit von JTLs Uploadfunktion bleibt notwendig. Es gibt keinen
Netzwerkabruf, keine Telemetrie und keinen kostenpflichtigen Zusatzdienst.

## Technischer Aufbau

- `Scanner/Filesystem/OpcStorageRoot.php`: feste Shopwurzel prüfen;
- `OpcStorageFileLister.php`: begrenzt auflisten, kanonische Pfade prüfen und sortieren;
- `OpcStorageScanFailure.php` / `OpcStorageScanException.php`: geschlossene Fehlertexte;
- `Scanner/Adapter/OpcStorageSourceAdapter.php`: einmalige Liste je Lauf paginieren;
- `Service/ImageScanService.php`: beide bekannten OPC-Beiträge gemeinsam abgleichen;
- `AdminRuntimeFactory.php`: ausschließlich serverseitige Wurzel übergeben.

Die bestehende Datenbankstruktur reicht aus. Neue Dateifundstellen verwenden
die Quelle `opc`, `opc-datei:` plus Pfadhash und den Kontext
**OPC-Dateispeicher**. Vorhandene `opc-seite:`-Fundstellen bleiben eigenständig.
Nur die zwei bekannten finalen OPC-Adapter dürfen denselben Quellentyp ergänzen.
Doppelte oder fremde Adapter werden weiterhin abgewiesen.

Eine vollständige, begrenzte Dateiliste entsteht beim ersten Seitenaufruf.
Weitere Seiten lesen diese Liste, statt das Dateisystem immer wieder zu
durchlaufen. Ein erneuter Scan baut sie neu auf. Erkannte Änderungen oder
Lesefehler brechen ab. Das Dateisystem liefert allerdings keinen transaktionalen
Schnappschuss: Während Uploads oder Verschiebungen kann ein erneuter Lauf nötig sein.

## Prüfung und Auslieferung

Tests verwenden ausschließlich temporäre Testordner und künstliche Daten.
Sie prüfen tiefe Ordner, Dateitypen, Symlinks, Leserechte, harte Grenzen,
Pagination, erneute Scans, Deduplizierung, Bestandsschutz und Rollback.
Die Integration nutzt echte Plugin-Services mit JTL-Stubs und einer
transaktionalen Testdatenbank; sie ersetzt keinen Abnahmetest im echten Shop.

Die erste Shop-Abnahme erfolgt auf Dev. Dabei müssen Bilder im Root, in
`banner/2026` und in einem tieferen Unterordner sichtbar sein. Anschließend
Quelle/Status/Seitennavigation und bestehende Labels prüfen. Vor einer
Installation Plugin-Dateien und Plugin-Tabellen zusammen sichern. Für einen
Rollback diese zusammengehörige Sicherung verwenden; keine Deinstallation mit
Datenlöschung. Dev-Abnahme und Live-Freigabe sind separate Schritte.

### Lokale Prüfergebnisse

- Gesamtsuite für 1.3.6: 557 PHP-Tests mit 14.828 Assertions erfolgreich,
  einschließlich Filter-/Zählungsprüfung und vollständigem Paketinhalt.
- JavaScript: 142 Tests erfolgreich, keine Änderungen am JavaScript notwendig.
- PHPStan: maximale Analysestufe ohne Fehler.
- Formatprüfung und `git diff --check`: ohne Beanstandungen.
- Reproduzierbarer Paketbau einschließlich Integritäts- und Ausschlussprüfungen
  in der Gesamtsuite erfolgreich; neue Scannerdateien und Hilfe im ZIP enthalten.
- Unabhängiges Code-Review: kein neuer Sicherheits- oder Funktionsbefund.
- Wissensgraph lokal mit AST aktualisiert; keine LLM-/API-Extraktion.

Die Tests liefen mit PHP 8.5.6. Eine zusätzliche Ausführung unter PHP 8.1 und
die Abnahme in der echten JTL-Laufzeit stehen noch aus. Das eindeutig
versionierte ZIP `MGD_AI_Kennzeichnung-1.3.6.zip` ist für diesen Dev-Test
vorbereitet. Es ersetzt oder überschreibt das veröffentlichte 1.3.4-Release
nicht. [Update und Prüfliste](Release-1.3.6.md) beschreiben den nächsten Schritt.
