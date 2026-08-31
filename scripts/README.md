# Hilfsskripte

`build-release.sh` erstellt aus einer ausdrücklich freigegebenen Top-Level-Liste
unter `plugin/MGD_AI_Kennzeichnung/` das reproduzierbare Installationspaket
`dist/MGD_AI_Kennzeichnung-1.3.1.zip`. Innerhalb dieser Pfade sind nur die für
das Plugin benötigten Dateiendungen erlaubt. Unbekannte Top-Level-Pfade,
Symlinks, versteckte Dateien, Schlüssel, Zertifikate, Dumps und Backups brechen
den Build sicher ab. Erst ein vollständig erzeugtes ZIP ersetzt ein vorhandenes
Artefakt. Rechte und Zeitstempel werden vereinheitlicht; geschrieben wird nur
nach `dist/`.

Das Paket wird zweimal gebaut und per SHA-256 auf Reproduzierbarkeit geprüft.
Anschließend wird genau dieses geprüfte ZIP zuerst auf `dev.onvis-shop.de`
abgenommen. Für `onvis-shop.de` wird dasselbe Artefakt verwendet; zwischen
Dev-Abnahme und Live-Rollout findet kein neuer Build statt. Das öffentliche
Repository stellt Releasehinweise bereit. Version 1.3.1 enthält bewusst keinen
Auto-Updater; das Update erfolgt als manueller ZIP-Upload im JTL-Plugin-Manager.
