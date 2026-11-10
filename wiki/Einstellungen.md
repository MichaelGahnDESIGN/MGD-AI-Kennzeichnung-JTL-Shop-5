# Einstellungen

Die Plugin-Einstellungen befinden sich unter:

**Plugins → MGD AI Kennzeichnung → Einstellungen**

## Herstellernennung im Footer

Standard: **Nein**

Bei Aktivierung erscheint der feste Hinweis **supported by: Michael Gahn DESIGN**
im Footer. Nur der Herstellername ist verlinkt und öffnet die Herstellerseite
sicher in einem neuen Tab. Die Funktion ist freiwillig und für die
Kennzeichnung selbst nicht erforderlich.

## Updates

Die frühere Einstellung „Updatehinweise über GitHub“ gibt es nicht mehr. Updates
prüfen Sie im Backend-Reiter **Updates** (MGD Update Client), nur auf Ihren Klick.

Das Plugin installiert keine Updates automatisch; verwenden Sie den manuellen
ZIP-Upload.

## Sprache der Kennzeichnung

- **Automatisch:** folgt der aktuellen Shopsprache;
- **Deutsch:** erzwingt deutsche Labeltexte;
- **Englisch:** erzwingt englische Labeltexte.

Die sichtbaren Kurztexte werden durch ausführliche Beschreibungen für assistive Technologien ergänzt.

## Darstellung und Live-Vorschau

Der eigene Darstellungstab zeigt links die globalen Einstellungen und rechts
ein lokales Beispielbild. Zahlenfeld und Schieberegler für Eckenradius,
Hintergrundunschärfe und Transparenz bleiben synchron. Die Live-Vorschau läuft
nur im Browser; erst **Speichern** ändert die globalen Shopwerte.

Position und Farbschema tragen dort den Hinweis **Nur Vorschau**. Für
verwaltete Bilder sind ausschließlich die Werte maßgeblich, die im
Kennzeichnungsdialog oder in der Stapelbearbeitung pro Bild gespeichert werden.

## Schriftgröße

- Standard: 12 px
- sicherer Bereich: 8 bis 48 px

Wählen Sie einen Wert, der auf Mobilgeräten lesbar bleibt, ohne große Teile kleiner Bilder zu verdecken.

## Außenabstand

- Standard: 8 px
- sicherer Bereich: 0 bis 64 px

Bestimmt den Abstand zwischen Label und gewählter Bildecke.

## Innenabstand

- Standard: 6 px
- sicherer Bereich: 0 bis 32 px

Bestimmt den Abstand zwischen Text und Labelrand.

## Eckenradius

- Standard: 4 px
- sicherer Bereich: 0 bis 32 px

Passt die Rundung des Labelrahmens an Ihr Template an.

## Hintergrundunschärfe

- Standard: 0 px
- sicherer Bereich: 0 bis 24 px

Erhöht bei unterstützten Browsern die Unschärfe des hinter dem Label liegenden Bildbereichs. Prüfen Sie Lesbarkeit und Leistung auf mobilen Geräten.

## Transparenz

- Standard: 8 %
- sicherer Bereich: 0 bis 90 %

**0 %** bedeutet einen vollständig deckenden Labelhintergrund. **90 %** ist
nahezu durchsichtig. Prüfen Sie besonders bei hohen Werten den Kontrast auf
hellen und dunklen Bildern.

## Sichere Werteverarbeitung

Das Plugin akzeptiert keine freien CSS-Klassen. Zahlenwerte werden als ganze Pixelwerte geprüft und auf die dokumentierten Grenzen beschränkt. Unbekannte Auswahlwerte fallen auf sichere Standards zurück.
