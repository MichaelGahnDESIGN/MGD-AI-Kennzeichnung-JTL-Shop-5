# OnPage Composer und Dateimanager

## Direkte Kennzeichnung im OPC

Das Plugin bindet sich über JTLs `editor_init.js`-Schnittstelle in den OnPage Composer ein. Bei einem eindeutig erkannten lokalen Bildfeld erscheint **KI-Kennzeichnung bearbeiten**.

Unterstützte Fälle sind insbesondere:

- Bild-Portlet;
- statisches Hintergrundbild eines Containers;
- eindeutig erkanntes lokales Bildfeld in Banner oder Slider.

## Bedienablauf

1. OPC-Seite zur Bearbeitung öffnen.
2. Bild, Banner, Slider oder Container bearbeiten.
3. lokales Bild auswählen beziehungsweise vorhandenes Bildfeld verwenden.
4. **KI-Kennzeichnung bearbeiten** öffnen.
5. Status, Position und Darstellung wählen.
6. Live-Vorschau prüfen.
7. **Kennzeichnung speichern** wählen.

Das Plugin liest beim Öffnen den aktuellen Bildwert erneut. So wird nicht versehentlich eine zuvor ausgewählte Datei bearbeitet.

## Getrenntes Speichern

Die Kennzeichnung und der OPC-Seiteninhalt sind getrennte Daten:

- **Kennzeichnung speichern** speichert ausschließlich die Plugin-Zuordnung.
- **OPC speichern/veröffentlichen** bleibt eine eigene JTL-Aktion.

Das Plugin veröffentlicht keine Seite automatisch.

## Frontend-Ausgabe

Normale und verlinkte OPC-Bilder werden innerhalb ihres sichtbaren Rahmens gekennzeichnet. Bei JTLs responsiver Ausgabe liegt das Label außerhalb des technischen `<picture>`-Elements, aber innerhalb des zugehörigen Links oder Blocks.

Lokale Container-Hintergründe werden erkannt, wenn sie als `background-image` oder `data-image-src` ausgegeben werden. Das Label wird direkt am vorhandenen Container positioniert.

Bildquelle, Linkziel und OPC-Inhalt bleiben unverändert.

## Dateimanager

JTL-Shop 5.7.2 verwendet im OPC einen elFinder-Dateimanager. Das Plugin kann dort einen zusätzlichen Kennzeichnungsbefehl anbieten, wenn alle Sicherheitsbedingungen erfüllt sind:

1. Fenster gehört zum gleichen JTL-Backend;
2. bekannte elFinder-Struktur wurde eindeutig erkannt;
3. genau eine Datei ist ausgewählt;
4. Auswahl ist ein lokales Rasterbild;
5. Pfad liegt in einer freigegebenen Shopwurzel;
6. bestehende Admin-Sitzung und Berechtigung sind gültig.

## Wann erscheint der Menüpunkt nicht?

- Ordner ausgewählt;
- mehrere Dateien ausgewählt;
- SVG oder Nicht-Bild ausgewählt;
- externe URL;
- leerer oder mehrdeutiger Pfad;
- JTL-Dateimanager nach einem Update nicht sicher erkannt;
- fehlende Pluginberechtigung.

Das bewusste Fehlen des Eintrags ist ein sicherer Fallback. Verwenden Sie dann die zentrale Bildverwaltung oder den OPC-Bilddialog.

## Nach einem JTL-Update

Prüfen Sie:

- öffnet sich der Kennzeichnungsdialog noch?
- wird genau die aktuelle Datei angezeigt?
- bleibt Mehrfachauswahl ohne Menüpunkt?
- funktioniert die zentrale Galerie unverändert?
- entstehen keine neuen Fehler in Browserkonsole oder Serverprotokoll?

Veröffentlichen Sie keine produktive OPC-Seite allein für einen Funktionstest. Nutzen Sie eine Testseite oder einen unveröffentlichten Entwurf.
