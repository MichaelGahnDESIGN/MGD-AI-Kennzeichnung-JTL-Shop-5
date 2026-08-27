# Einstellungen

Die Plugin-Einstellungen befinden sich unter:

**Plugins → MGD AI Kennzeichnung → Einstellungen**

## Herstellernennung im Footer

Standard: **Nein**

Bei Aktivierung erscheint der feste Hinweis **Plugin von Michael Gahn DESIGN** im Footer. Die Funktion ist freiwillig und für die Kennzeichnung selbst nicht erforderlich.

## Updatehinweise über GitHub

Standard: **Nein**

Bei Aktivierung fragt das Plugin höchstens alle zwölf Stunden öffentliche Metadaten des neuesten GitHub-Releases ab.

Dabei gilt:

- keine Bildübertragung;
- keine Kundendaten;
- keine Zugangsdaten;
- kein automatischer Download;
- keine automatische Installation;
- TLS-Prüfung erforderlich;
- Weiterleitungen gesperrt;
- begrenzte Antwortgröße;
- lokaler Cache zur Vermeidung unnötiger Anfragen.

Wenn Ihre Organisation ausgehende Verbindungen streng beschränkt, lassen Sie die Funktion deaktiviert und prüfen Sie Releases manuell.

## Sprache der Kennzeichnung

- **Automatisch:** folgt der aktuellen Shopsprache;
- **Deutsch:** erzwingt deutsche Labeltexte;
- **Englisch:** erzwingt englische Labeltexte.

Die sichtbaren Kurztexte werden durch ausführliche Beschreibungen für assistive Technologien ergänzt.

## Position und Farbschema

Die Einstellungsseite enthält sichere Basiswerte. Für verwaltete Bilder sind jedoch die Werte maßgeblich, die im Kennzeichnungsdialog oder in der Stapelbearbeitung **pro Bild** gespeichert werden.

Wenn Sie eine sichtbare Position ändern möchten, bearbeiten Sie daher das betreffende Bild in der Galerie.

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

## Sichere Werteverarbeitung

Das Plugin akzeptiert keine freien CSS-Klassen. Zahlenwerte werden als ganze Pixelwerte geprüft und auf die dokumentierten Grenzen beschränkt. Unbekannte Auswahlwerte fallen auf sichere Standards zurück.
