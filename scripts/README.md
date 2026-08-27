# Hilfsskripte

`build-release.sh` erstellt ausschließlich aus `plugin/MGD_AI_Kennzeichnung/` das reproduzierbare Installationspaket `dist/MGD_AI_Kennzeichnung-1.1.1.zip`. Das Skript verweigert Symlinks und typische Entwicklungs- oder sensible lokale Dateien, vereinheitlicht Rechte und Zeitstempel und schreibt nur nach `dist/`.

Das Paket wird einmal gebaut, per SHA-256 geprüft und zuerst auf `dev.onvis-shop.de` abgenommen. Für `onvis-shop.de` wird exakt dieses geprüfte ZIP verwendet; zwischen Dev-Abnahme und Live-Rollout findet kein neuer Build statt.
