# Kompakte Werkzeugleiste im AI-Philosophie-Editor

## Änderung und Ursache

Die Werkzeuge hatten dieselbe Mindesthöhe wie große Formularaktionen:
44 px mit 8 px Abstand. Im geprüften Dev-Backend brach die Leiste auf zwei
Zeilen um und belegte insgesamt 96 px Höhe. Die Sprachüberschrift übernahm
außerdem die helle Schriftfarbe des dunklen JTL-Backends auf einer weißen Karte.

Nur die Editor-Werkzeuge sind jetzt kompakter: mindestens 32 × 32 px,
4 px Abstand, skalierbare Schrift. Textflächen und Speichern-Button bleiben
unverändert. Der Umbruch auf schmalen Ansichten und der Tastaturfokus bleiben
erhalten. Sprachüberschriften erhalten eine explizite dunkle Farbe.

Da ein normales Neuladen zunächst das alte CSS aus dem Browsercache verwendete,
trägt die lokale CSS-Adresse im Template zusätzlich die Inhaltskennung
`1dcc59280deb`. Ein Test prüft diese Kennung gegen den SHA-256-Präfix der CSS-Datei.
Bei künftigen Änderungen an dieser CSS-Datei muss auch die Kennung erneuert werden.

## Prüfung

- Neue Regressionstests zunächst mit der alten Darstellung fehlgeschlagen,
  danach erfolgreich; insgesamt 146 JavaScript-Tests bestanden.
- 565 PHP-Tests mit 14.904 Assertions bestanden, lokal unter PHP 8.5.6.
- Dev-Backend nach Neuladen tatsächlich gemessen und per Bildschirmaufnahme geprüft:
  beide Sprachleisten mit jeweils 13 Buttons, jeweils 32 px Höhe;
  bei 603 px verfügbarer Breite eine Zeile ohne horizontalen Überlauf.
- Beide Textflächen weiterhin mindestens 360 px hoch.
- Deutsch-Überschrift dunkel: `rgb(23, 32, 42)`.
- Wechsel zur HTML-Ansicht und zurück ohne Speichern erfolgreich.
- Kein Inhalt, keine Kennzeichnung, kein Designwert und keine Datenbank geändert.

## Auslieferung und Rückweg

Nur auf `dev.onvis-shop.de` eingespielt: `adminmenu/philosophy.css` und
`adminmenu/templates/philosophy.tpl`. Vor jeder Änderung wurde die vorhandene
Datei gegen den Git-Ausgangsstand geprüft und lokal gesichert. Übertragung,
atomarer Austausch und anschließend gelesene Serverdatei wurden bytegenau geprüft.

Die Sicherungen liegen im lokalen Backup-Verzeichnis für MGD AI Kennzeichnung:
`20260903-011455-dev-toolbar-css` und `20260903-011617-dev-toolbar-template`.
Für eine Rücknahme reichen diese beiden Dateien; keine Datenbank zurücksetzen.

Dies ist eine Dev-Korrektur auf Basis von 1.3.7. Die angezeigte Pluginversion
bleibt 1.3.7. Das öffentliche Release und dessen ZIP wurden nicht ersetzt.
`onvis-shop.de` und `campingteile24.de` wurden für diese Korrektur nicht geändert.
Keine kostenpflichtigen Dienste, Cloud-Tests oder zusätzlichen Assets verwendet.
