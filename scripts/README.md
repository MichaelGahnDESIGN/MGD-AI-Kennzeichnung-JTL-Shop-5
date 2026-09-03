# Hilfsskripte

Die Release-Prüfungen laufen standardmäßig lokal. GitHub Actions ist nur noch
manuell startbar; Push und Pull Request lösen keinen Cloud-Lauf aus. Vor einem
manuellen Lauf müssen mögliche Kosten gesondert geprüft werden.

`build-release.sh` erstellt aus einer ausdrücklich freigegebenen Top-Level-Liste
unter `plugin/MGD_AI_Kennzeichnung/` das reproduzierbare Installationspaket
`dist/MGD_AI_Kennzeichnung-1.3.9.zip`. Innerhalb dieser Pfade sind nur die für
das Plugin benötigten Dateiendungen erlaubt. Unbekannte Top-Level-Pfade,
Symlinks, versteckte Dateien, Schlüssel, Zertifikate, Dumps und Backups brechen
den Build sicher ab. Erst ein vollständig erzeugtes ZIP ersetzt ein vorhandenes
Artefakt. Rechte und Zeitstempel werden vereinheitlicht; geschrieben wird nur
nach `dist/`.

Das Paket wird zweimal gebaut und per SHA-256 auf Reproduzierbarkeit geprüft.
Anschließend wird genau dieses geprüfte ZIP zuerst auf `dev.onvis-shop.de`
abgenommen. Für `onvis-shop.de` wird dasselbe Artefakt verwendet; zwischen
Dev-Abnahme und Live-Rollout findet kein neuer Build statt. Das öffentliche
Repository stellt Releasehinweise bereit. Version 1.3.9 enthält bewusst keinen
Auto-Updater; das Update erfolgt als manueller ZIP-Upload im JTL-Plugin-Manager.

## Fertige Pakete für Michael ablegen

Seit dem 2. September 2026 liegen die fertig geprüften Installations-ZIPs im
Ordner `plugin/` des Hauptprojektordners, damit sie ohne Suche in internen
Build- oder Worktree-Verzeichnissen erreichbar sind:

`/Users/michaelgahn/AKTUELLE PROJEKTE/MGD_AI_Kennzeichnung-1.0.0/plugin/`

- Der interne, reproduzierbare Build bleibt in `dist/`.
- Nach den Prüfungen wird genau dieses ZIP unverändert in den genannten
  Hauptprojektordner übernommen – nicht in `plugin/` eines isolierten Worktrees.
- Der Dateiname behält die Version, beispielsweise
  `MGD_AI_Kennzeichnung-1.3.9.zip`; eine zugehörige Prüfsummendatei liegt bei Bedarf daneben.
- Vorhandene gleichnamige Pakete erst per SHA-256 vergleichen. Identische
  Dateien bleiben liegen; abweichende Pakete nicht ungefragt überschreiben.
- Zur Übergabe an Michael auf die Datei im Hauptprojektordner verlinken.
- Die ZIPs liegen neben dem Quellcodeordner `plugin/MGD_AI_Kennzeichnung/`,
  nicht darin. Sie gehören nicht in das Git-Repository oder ein weiteres Plugin-ZIP.

Das Verschieben oder Kopieren ändert weder Paketinhalt noch Versionsnummer und
stellt keine Installation, GitHub-Veröffentlichung oder Live-Freigabe dar.
