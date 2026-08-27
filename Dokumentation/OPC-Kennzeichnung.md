# Kennzeichnung im OnPage Composer und Dateimanager

## OnPage Composer

Version 1.1.1 lädt über JTLs offizielle `editor_init.js`-Schnittstelle eine lokale Erweiterung. Bei einem eindeutig erkannten lokalen Bildfeld erscheint **„KI-Kennzeichnung bearbeiten“**. Unterstützt werden:

- Bild-Portlet;
- statisches Container-Hintergrundbild;
- eindeutig erkennbare Banner- und Bilderslider-Felder.

Beim Öffnen liest das Plugin den aktuellen Bildwert erneut. So wird nie versehentlich das zuvor gewählte Bild gespeichert. Der Dialog zeigt eine Live-Vorschau; erst **„Kennzeichnung speichern“** schreibt die Kennzeichnung. Die OPC-Seite wird dadurch nicht automatisch veröffentlicht.

Im Frontend wird das Label innerhalb der sichtbaren Bildfläche ausgegeben. Das gilt auch für verlinkte, responsive `picture`-Bilder sowie statische oder per `data-image-src` geladene lokale Hintergrundbilder. Linkziele, Bilddateien und bestehende OPC-Inhalte bleiben unverändert.

Externe URLs, leere oder versteckte Felder, mehrdeutige Werte, Videos und SVG-Dateien werden nicht angeboten. Die endgültige Prüfung erfolgt zusätzlich auf dem Server.

## JTL-Dateimanager

Der aus dem OPC geöffnete Dateimanager basiert in JTL-Shop 5.7.2 auf elFinder. Für dessen Kontextmenü existiert kein eigener stabiler Plugin-Hook. Deshalb ist die Ergänzung optional und fehlertolerant.

Die **Kompatibilitätsgrenze** verlangt gleichzeitig:

1. ein `same-origin`-Fenster des eigenen JTL-Backends;
2. eine eindeutig erkannte elFinder-Instanz;
3. genau eine ausgewählte Datei;
4. eine lokale Rasterbilddatei aus einer freigegebenen Shopwurzel.

Nur dann erscheint der zusätzliche Menüpunkt. Ordner, Mehrfachauswahl, Nicht-Bilder und externe URLs erhalten keinen Eintrag. Wird die erwartete Struktur nach einem JTL-Update nicht erkannt, bleibt der Dateimanager vollständig unverändert; die zentrale Bildgalerie funktioniert weiterhin.

## Sicherheit

Beide Bereiche verwenden die bestehende angemeldete JTL-Admin-Sitzung, JTLs CSRF-Prüfung und die Pluginberechtigung. Es entsteht kein eigener öffentlicher Endpunkt. Tokens, Bildinhalte oder personenbezogene Daten werden weder protokolliert noch an Dritte übertragen.
