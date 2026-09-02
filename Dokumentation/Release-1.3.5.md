# Testpaket 1.3.5 – OPC-Bilder in Unterordnern

Stand: 2. September 2026. Version 1.3.5 ist für die lokale Prüfung und den
anschließenden Dev-Test vorbereitet. Sie wurde noch nicht öffentlich auf
GitHub veröffentlicht oder auf Dev beziehungsweise Live installiert.

## Was sich für Sie verbessert

Der sichere Bildscan erfasst zusätzlich zu gespeicherten OPC-Seiten nun auch
die Bilddateien im offiziellen lokalen OPC-Uploadspeicher. Dadurch finden Sie
Bilder aus dem Hauptordner, aus `opc/banner/2026` und aus weiteren verschachtelten
Ordnern gemeinsam in der Galerie – auch wenn Sie diese noch auf keiner Seite
eingesetzt haben.

Die bestehenden Filter nach Status, Quelle und Fundstelle, die Sortierung sowie
die Seitennavigation bleiben erhalten. Wählen Sie **OnPage Composer**, um die
OPC-Bilder anzuzeigen. Ein zusätzlicher Ordnerfilter gehört nicht zu diesem Update;
den vollständigen relativen Pfad sehen Sie unter **Details**.

## Ihre bisherigen Kennzeichnungen bleiben erhalten

Eine Datei erscheint nur einmal in der Galerie, auch wenn sie sowohl im Speicher
als auch auf einer OPC-Seite gefunden wird. Status, Position und Darstellung
bereits bekannter Bilder werden durch den Scan nicht geändert. Dateien werden
weder bearbeitet noch gelöscht. Das Update benötigt keine Änderung des
Datenbankschemas.

Ein Speicherfund bedeutet nicht, dass das Bild bereits veröffentlicht ist.
Verschieben oder Umbenennen erzeugt wegen des neuen Pfads eine neue Zuordnung;
deren Kennzeichnung müssen Sie erneut prüfen.

## Sicherheits- und Leistungsgrenzen

Der Scan bleibt auf `media/image/storage/opc/` innerhalb der serverseitigen
Shopwurzel begrenzt. Er folgt keinen symbolischen Links und erfasst JPG, JPEG,
PNG, WebP, GIF und AVIF. SVG, Videos und externe Adressen bleiben ausgeschlossen.
Maximal 9.999 unterstützte Bilddateien, 20.000 Verzeichniseinträge und
32 Unterordnerebenen werden verarbeitet.

Ist der Speicher nicht vollständig lesbar oder wird eine Grenze überschritten,
wird der gesamte Scan zurückgerollt. Bestehende Fundstellen werden deshalb
nicht durch einen unvollständigen Lauf versehentlich als veraltet übernommen.
Fehlerhinweise zeigen keine absoluten Serverpfade oder technischen Geheimnisse.
Es gibt keine externe Bildanalyse, keine Telemetrie und keinen kostenpflichtigen
Zusatzdienst. Die bisherigen optionalen GitHub-Updatehinweise bleiben unverändert.

## Dev-Test nach dem manuellen ZIP-Upload

1. Plugin-Dateien, Plugin-Konfiguration und Plugin-Tabellen zusammen sichern.
2. Im getrennten Dev-Shop **Plugins → Plugin-Manager → Upload** öffnen.
3. `MGD_AI_Kennzeichnung-1.3.5.zip` hochladen und **Aktualisieren** verwenden.
   Nicht deinstallieren und keine Datenlöschung auswählen.
4. Prüfen, ob JTL Version **1.3.5** und den gewünschten Aktivstatus anzeigt.
5. **Bildverwaltung → Sicheren Bildscan starten** aufrufen und die Erfolgsmeldung abwarten.
6. Quelle **OnPage Composer** auswählen und **Galerie anzeigen** klicken.
7. Je ein Bild im Hauptordner, in `banner/2026` und in einem tieferen Unterordner
   kontrollieren. Dabei auch ein bislang unbenutztes Bild prüfen.
8. Statusfilter, Sortierung und Seitennavigation prüfen. Bei einem schon
   gekennzeichneten Bild müssen Status, Position und Darstellung erhalten bleiben.
9. Frontend und Backend auf neue Fehler kontrollieren. Protokolle nur bereinigt
   weitergeben, niemals Zugangsdaten oder personenbezogene Inhalte veröffentlichen.

Die lokale Testumgebung ersetzt nicht die Abnahme in der echten JTL-Laufzeit.
Ein Live-Update ist ausdrücklich nicht Bestandteil dieses Schritts.

## Paketprüfung

Datei: `MGD_AI_Kennzeichnung-1.3.5.zip`

SHA-256: `46b6c59938f445f83de89d3f6ab9662ee1be9019f91f00eb5078eb9697d3bfbc`

Das ZIP enthält ausschließlich das installierbare Plugin, keine Tests,
Entwicklungsabhängigkeiten, Backups oder Zugangsdaten. Der erste Archiveintrag
ist `MGD_AI_Kennzeichnung/`, damit JTL den Plugin-Stamm korrekt erkennt.
Das Buildskript prüft die Integrität und erzeugt reproduzierbare Inhalte.

Zum bereitgestellten Testpaket gehört die Prüfsummendatei
`MGD_AI_Kennzeichnung-1.3.5.zip.sha256`. Prüfen Sie beide Dateien gemeinsam:

```bash
cd dist
shasum -a 256 -c MGD_AI_Kennzeichnung-1.3.5.zip.sha256
unzip -t MGD_AI_Kennzeichnung-1.3.5.zip
```

### Lokales Prüfprotokoll vom 2. September 2026

- PHP 8.5.6: 555 Tests, 14.826 Assertions, keine Fehler.
- JavaScript: 142 Tests, keine Fehler oder übersprungenen Tests.
- PHPStan auf maximaler Stufe: keine Fehler.
- PHP-CS-Fixer im reinen Prüfmodus: keine Änderungen erforderlich.
- Composer-Projektdatei und Git-Diff: erfolgreich validiert.
- Pakettests: identische Ergebnisse bei wiederholtem Build und unterschiedlichen
  Zeitzonen; ZIP-Integrität und vollständiger Unterordnerscan-Inhalt geprüft.
- Lokaler Wissensgraph ausschließlich aus dem Quellcode aktualisiert,
  ohne kostenpflichtige API oder semantische KI-Auswertung.

Die Formatprüfung weist auf die gegenüber dem Mindestvertrag PHP 8.1 neuere
lokale Laufzeit hin. Ein Lauf unter PHP 8.1 sowie ein Test mit dem echten
JTL-Dateisystem und der Shop-Datenbank stehen noch aus. Es wurde kein
kostenpflichtiger Dienst gestartet und kein Shop verändert.

## Rückfall

Bei einem Fehler das Plugin deaktivieren und die unmittelbar vorher gesicherten
Plugin-Dateien, Plugin-Konfiguration und Plugin-Tabellen als zusammengehörigen
Stand wiederherstellen. Keine Deinstallation mit Datenlöschung durchführen.
Eine Veröffentlichung auf GitHub und eine Installation auf Live erfolgen erst
in gesonderten Schritten; das bisherige veröffentlichte Release 1.3.4 bleibt
unverändert.

Weitere Details: [OPC-Dateispeicherscan](OPC-Dateispeicherscan.md).
