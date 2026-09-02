# Bildverwaltung

Die Bildverwaltung ist der zentrale und dauerhaft unterstützte Arbeitsbereich des Plugins.

## Sicherer Bildscan

Der Scan sucht lokale Bildreferenzen in diesen Bereichen:

- Artikel;
- Kategorien;
- Hersteller;
- Banner;
- OnPage Composer.

Er arbeitet seitenweise und mit festen Obergrenzen. Jeder Lauf gleicht Fundstellen nachvollziehbar ab. Ein Bild wird nicht gelöscht, wenn seine Fundstelle verschwindet.

### Wann sollte gescannt werden?

- direkt nach der Installation;
- nach einem größeren Wawi- oder Shopabgleich;
- nach dem Austausch vieler Produktbilder;
- nach umfangreichen OPC-Änderungen;
- regelmäßig als Teil des redaktionellen Prüfprozesses.

## OPC-Unterordner und unbenutzte Uploads

In Version 1.3.7 ergänzt der Scan die gespeicherten OPC-Seiten
um den vollständigen lokalen Uploadspeicher innerhalb fester Sicherheitsgrenzen.
Damit erscheinen auch Bilder unter `opc/banner/2026` oder
`opc/bilder/2026/weitere/Unterordner`, die Sie noch auf keiner Seite eingesetzt haben.

1. Öffnen Sie die Bildverwaltung und wählen Sie **Sicheren Bildscan starten**.
2. Warten Sie auf die Bestätigung des vollständigen Scans.
3. Wählen Sie als Quelle **OnPage Composer** und klicken Sie auf **Galerie anzeigen**.
4. Nutzen Sie bei Bedarf **Ungeprüft**, die Sortierung und die Seitennavigation.
5. Öffnen Sie **Details**, wenn Sie Bilder mit gleichem Dateinamen anhand ihres Ordners unterscheiden möchten.

Die Quelle bleibt OPC. Ein eigener Ordnerfilter wird nicht hinzugefügt.
Eine Datei im Speicher und ihr Verweis auf einer OPC-Seite sind zwei Fundstellen,
aber nur ein Bild in der Galerie. Ein Speicherfund beweist keine Veröffentlichung.
Neue Bilder beginnen als **Ungeprüft**; bestehende Kennzeichnungen bleiben erhalten.

Pro Scan sind 9.999 JPG-/JPEG-/PNG-/WebP-/GIF-/AVIF-Dateien, insgesamt 20.000
Verzeichniseinträge und 32 Unterordnerebenen zugelassen. Symlinks werden nicht
verfolgt, SVG und Videos nicht aufgenommen. Fehlt der Speicher, ist ein Ordner
unlesbar oder wird eine Grenze überschritten, übernimmt das Plugin keine
Scanänderungen. Nach Behebung können Sie erneut scannen. Keine Freigabe ganzer
Ordner für jedermann (`777`) als pauschale Lösung!

Verschieben oder Umbenennen erzeugt wegen des geänderten Pfads eine neue
Bildzuordnung. Die alte Kennzeichnung wird nicht automatisch auf den neuen Pfad
übertragen. Die Originaldateien werden durch den Scanner nicht verändert.

## Filter

### Status

Zeigt alle Bilder oder nur einen konkreten Prüfstatus. Für tägliche Arbeit sind **Ungeprüft** und die vier sichtbaren KI-Status besonders relevant.

### Quelle

Grenzt nach Artikel, Kategorie, Hersteller, Banner/Slider, OPC, manueller lokaler Auswahl oder unbekannter Quelle ein.

### Fundstelle

- **Vorhanden:** Das Bild wurde beim letzten Scan an mindestens einer aktuellen Stelle gefunden.
- **Veraltet:** Eine früher gespeicherte Fundstelle wurde beim Abgleich nicht mehr gefunden.

### Sortierung und Richtung

Sortiert nach ID, Status oder Änderungsdatum – jeweils auf- oder absteigend.

### Einträge pro Seite

Verfügbar sind 10, 25, 50 oder 100 Karten. Eine kleinere Auswahl ist auf mobilen Geräten übersichtlicher.

## Aufbau einer Bildkarte

Eine Karte enthält:

- lokale Vorschau oder sicheren Platzhalter;
- Dateiname;
- Status;
- Quelle;
- Anzahl der Fundstellen;
- letztes Änderungsdatum;
- Auswahlbox;
- **Details** für technische Informationen;
- **Kennzeichnen** für den Bearbeitungsdialog.

Lange lokale Pfade werden nicht als dominanter Galerietext angezeigt. Die technische Detailansicht bleibt für Support und Prüfung verfügbar.

## Einzelbearbeitung

1. **Kennzeichnen** wählen.
2. Status auswählen.
3. Position auswählen.
4. Darstellung auswählen.
5. Live-Vorschau beurteilen.
6. **Kennzeichnung speichern** wählen.

Der Speichern-Button wird während der Anfrage gegen Doppelklick geschützt. Bei einem Fehler bleibt die vorherige Karte unverändert.

## Stapelbearbeitung

Die Stapelbearbeitung eignet sich für Bilder mit einer gemeinsamen, bereits geprüften Eigenschaft.

1. Bilder über die Auswahlbox markieren.
2. Nur die Felder aktivieren, die geändert werden sollen.
3. Zielwert festlegen.
4. **Änderung prüfen** wählen.
5. Anzahl und Zielwerte in der Zusammenfassung kontrollieren.
6. Änderung verbindlich bestätigen.

Es können bis zu 500 eindeutige Einträge verarbeitet werden. Doppelte, ungültige oder nicht mehr vorhandene IDs werden abgelehnt. Die Bestätigung ist kurzlebig, sitzungsgebunden und nur einmal verwendbar.

## Veraltete Fundstellen bereinigen

Die Bereinigung entfernt nur ausgewählte Plugin-Fundstellen, die nachweislich veraltet sind. Sie löscht:

- keine Bilddatei;
- keinen Artikel;
- keine Kategorie;
- keinen OPC-Inhalt;
- keinen zentralen Bilddatensatz.

Auch hier wird vor der endgültigen Aktion eine Zusammenfassung erzeugt.

## Wenn keine Vorschau erscheint

Ein Platzhalter kann folgende Gründe haben:

- Pfad liegt außerhalb der erlaubten lokalen Wurzeln;
- Dateiendung ist nicht als sichere Rastervorschau zugelassen;
- Datei ist nicht mehr vorhanden;
- Quelle ist technisch nicht eindeutig;
- SVG wurde aus Sicherheitsgründen ausgeschlossen.

Öffnen Sie **Details**, prüfen Sie die Fundstelle und starten Sie bei Bedarf einen neuen Scan.
