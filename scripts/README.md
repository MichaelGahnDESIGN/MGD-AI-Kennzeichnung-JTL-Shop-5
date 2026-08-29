# Hilfsskripte

`build-release.sh` erstellt aus einer ausdrücklich freigegebenen Top-Level-Liste
unter `plugin/MGD_AI_Kennzeichnung/` das reproduzierbare Installationspaket
`dist/MGD_AI_Kennzeichnung-1.2.1.zip`. Innerhalb dieser Pfade sind nur die für
das Plugin benötigten Dateiendungen erlaubt. Unbekannte Top-Level-Pfade,
Symlinks, versteckte Dateien, Schlüssel, Zertifikate, Dumps und Backups brechen
den Build sicher ab. Erst ein vollständig erzeugtes ZIP ersetzt ein vorhandenes
Artefakt. Rechte und Zeitstempel werden vereinheitlicht; geschrieben wird nur
nach `dist/`.

Das Paket wird einmal gebaut, per SHA-256 geprüft und zuerst auf `dev.onvis-shop.de` abgenommen. Für `onvis-shop.de` wird exakt dieses geprüfte ZIP verwendet; zwischen Dev-Abnahme und Live-Rollout findet kein neuer Build statt. Da das Repository privat ist und Version 1.2.1 keinen Auto-Updater enthält, erfolgt das Update als manueller ZIP-Upload im JTL-Plugin-Manager.
