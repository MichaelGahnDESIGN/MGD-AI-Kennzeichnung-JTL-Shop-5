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
