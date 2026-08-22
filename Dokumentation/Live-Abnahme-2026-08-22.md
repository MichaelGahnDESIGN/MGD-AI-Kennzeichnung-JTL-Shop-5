# Live-Abnahme vom 22. August 2026

## Ergebnis

Version 1.1.0 wurde am 22. August 2026 auf `onvis-shop.de` über den offiziellen Installationsablauf von JTL-Shop installiert und aktiviert. Der Shop war vor und nach der Installation öffentlich erreichbar.

## Ausgangszustand und Rückfall

- JTL-Shop: 5.7.2
- PHP: 8.5.3-nmm1
- Das Plugin war vor der Installation weder im Dateisystem noch in der Datenbank vorhanden.
- Die vier Plugin-Tabellen waren vor der Installation ebenfalls nicht vorhanden.
- Das lokale, datensparsame Ausgangsbackup liegt außerhalb des Repositorys unter `BACKUPS/MGD_AI_Kennzeichnung/live-20260822-222423`.
- Es wurde bewusst kein vollständiger Datenbankexport mit Kunden-, Bestell- oder Zahlungsdaten angelegt.
- Bei einem Rückfall wird das Plugin über den JTL-Lifecycle deinstalliert und anschließend der ursprüngliche Zustand „nicht installiert“ geprüft.

## Geprüftes Release

- Paket: `MGD_AI_Kennzeichnung-1.1.0.zip`
- SHA-256: `3d5719f45e2c5b661f46d63d1dfe5152e33ed2ff23d49d02d80e325260d2b95c`
- Git-Commit bei Installation: `0a1e583d9673c401ac94137bece7157ce2572641`
- Das private GitHub-Repository verwendete `main` als Standardbranch.

## Installations- und Integritätsprüfung

- Der JTL-Extractor übernahm 159 Paketdateien.
- Der JTL-Installer meldete Erfolgscode `1`.
- Das Plugin wurde als Version 1.1.0 mit Status `2` (aktiv) und Plugin-ID `47` registriert.
- Alle vier erwarteten Plugin-Tabellen wurden angelegt.
- Die drei Admin-Menüpunkte „Bildverwaltung“, „AI-Philosophie“ und „Einstellungen“ wurden registriert.
- Das Portlet „AI-Philosophie“ wurde aktiv in der Gruppe „Custom Portlets“ registriert.
- 158 von JTL inventarisierte Paketdateien stimmten mit ihren Prüfsummen überein.
- Alle 135 PHP-Dateien bestanden die Syntaxprüfung.
- Der JTL-Pluginvalidator akzeptierte das installierte Plugin; der gemeldete Code `90` steht bei einem bereits installierten Plugin für dessen erwartete vorhandene Plugin-ID.

## Laufzeitprüfung auf Live

Ein zufällig benanntes, nicht verlinktes Ein-Pixel-Testbild wurde kurzzeitig innerhalb der erlaubten lokalen Bildwurzel angelegt. Damit wurden folgende Wege geprüft:

1. Kennzeichnung als „KI-generiert“ speichern;
2. Kennzeichnung aus der Datenbank erneut laden;
3. Kennzeichnung über das Frontend-Repository finden;
4. ausschließlich eine Same-Origin-Vorschau unter `onvis-shop.de` erzeugen;
5. Vorschaubild per HTTP 200 abrufen.

Anschließend wurden Testbild, Asset- und Fundstellen-Datensatz vollständig entfernt. Die Tabellen enthielten danach wieder exakt so viele Datensätze wie vor dem Test: jeweils null. Es wurden keine Kunden-, Bestell- oder Zahlungsdaten verwendet.

## Öffentliche Smoke-Tests

Nach der Installation antworteten Startseite, Warenkorb, Admin-Einstieg sowie die Plugin-CSS- und JavaScript-Datei jeweils mit HTTP 200. Startseite und Warenkorb blieben damit erreichbar. In den letzten 30 Minuten lagen keine aktuellen Logdateien im Shopverzeichnis vor.

## Sichtprüfung

Der authentifizierte Admin-Pfad der Bildverwaltung ließ sich in Safari laden. Die lokale Bildschirmaufnahme des geschützten Safari-Inhalts blieb – wie bereits bei der Dev-Abnahme – leer; dadurch war keine belastbare visuelle Galerieaufnahme möglich. Datenbankregistrierung, JTL-Validator, Dateiintegrität, Admin-Menüs, Speichern/Laden und Vorschau wurden unabhängig davon erfolgreich geprüft.
