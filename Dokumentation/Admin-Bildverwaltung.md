# Admin-Bildverwaltung in Version 1.1.1

## Wofür ist die Bildgalerie gedacht?

Die Bildgalerie zeigt gefundene Shopbilder als Vorschaubilder. Lange technische Pfade werden nicht mehr in jeder Karte angezeigt. Ein Status wird immer als Text ausgegeben und nicht nur über eine Farbe.

Das Plugin erkennt nicht automatisch, ob ein Bild mit KI erstellt wurde. Die Entscheidung trifft immer ein berechtigter Mensch. Die Bilddatei selbst bleibt unverändert.

## Bild neu scannen

1. Im JTL-Backend **Plugins → MGD AI Kennzeichnung → Bildverwaltung** öffnen.
2. Auf **„Sicheren Bildscan starten“** klicken.
3. Nach dem Scan wieder zur Galerie zurückkehren.

Der Scan liest nur freigegebene lokale Bildquellen. Er löscht keine Bilder und verändert keine Produkt-, Kategorie- oder OPC-Daten.

## Filter und Galerie

Oberhalb der Bilder stehen die Filter für Status, Quelle und Fundstelle. Zusätzlich lassen sich Sortierung, Richtung und Einträge pro Seite wählen. Erst mit **„Galerie anzeigen“** werden die gewählten Filter angewendet.

Jede Karte enthält Vorschau, Dateiname, Status, Quelle, Zahl der Fundstellen und Änderungsdatum. **„Details“** zeigt den technischen Datensatz. **„Kennzeichnen“** öffnet die direkte Bearbeitung.

## Ein Bild kennzeichnen

1. Auf der gewünschten Karte **„Kennzeichnen“** wählen.
2. Status, Position und Darstellung einstellen.
3. Die Live-Vorschau kontrollieren.
4. Mit **„Kennzeichnung speichern“** bestätigen.

**Abbrechen**, die Escape-Taste und das Schließen des Dialogs speichern nichts. Ein Doppelklick auf Speichern löst nicht zwei Anfragen aus. Bei einer Fehlermeldung bleibt die Karte unverändert.

## Stapelbearbeitung

1. Die Auswahlboxen der gewünschten Bilder aktivieren.
2. Unter **Stapelbearbeitung** nur die Felder ankreuzen, die geändert werden sollen.
3. Zielwerte auswählen und **„Änderung prüfen“** anklicken.
4. Die Zusammenfassung mit Anzahl und Zielwerten kontrollieren.
5. Erst danach verbindlich speichern.

Die Bestätigung ist kurzlebig und nur einmal verwendbar. Manipulierte, doppelte oder übergroße Auswahlen werden abgelehnt.

## Wenn keine Vorschau erscheint

Eine neutrale Platzhalterfläche bedeutet, dass der lokale Pfad nicht sicher als unterstütztes Rasterbild aufgelöst werden konnte. SVG wird wegen möglicher aktiver Inhalte bewusst nicht als Vorschau zugelassen. Der technische Datensatz kann weiterhin über **„Details“** geprüft werden.
