# Hilfsskripte

`build-release.sh` erstellt ausschließlich aus `plugin/MGD_AI_Kennzeichnung/` das reproduzierbare Installationspaket. Das Skript verweigert Symlinks und typische Entwicklungs- oder Geheimnisdateien, vereinheitlicht Rechte und Zeitstempel und schreibt nach `dist/`.
