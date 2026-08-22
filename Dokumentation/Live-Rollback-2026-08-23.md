# Live-Rollback vom 23. August 2026

## Ziel

Das Plugin sollte vorerst ausschließlich auf `dev.onvis-shop.de` weiterentwickelt und verbessert werden. Deshalb wurde die Installation auf `onvis-shop.de` am 23. August 2026 vollständig zurückgenommen. Dev blieb dabei unangetastet.

## Sicherheitsprüfung vor dem Rückbau

- Live verwendete Version 1.1.0 mit Status aktiv.
- Alle vier Plugin-Tabellen enthielten null Datensätze.
- Es existierten insbesondere keine gescannten Bilder oder gespeicherten Fundstellen auf Live.
- Dev enthielt weiterhin 714 Bilder und 1.704 Fundstellen.

Vor der Deinstallation wurde lokal und außerhalb des Repositorys eine datensparsame Sicherung unter `BACKUPS/MGD_AI_Kennzeichnung/live-rollback-20260823-014557` erstellt. Sie enthält ausschließlich Plugin-Dateien, technische JTL-Plugin-Metadaten und die Schemata der leeren Plugin-Tabellen. Kunden-, Bestell- und Zahlungsdaten wurden nicht gesichert.

## Ausführung

Die Deinstallation erfolgte über JTLs offiziellen `Uninstaller` mit aktivierter Entfernung der Plugin-Daten und Plugin-Dateien. JTL meldete Erfolgscode `1`.

Danach waren auf Live nicht mehr vorhanden:

- der Datensatz in `tplugin`;
- die drei Plugin-Admin-Menüpunkte;
- das OPC-Portlet „AI-Philosophie“;
- die vier Plugin-Tabellen;
- das Verzeichnis `plugins/MGD_AI_Kennzeichnung`.

## Abschlussprüfung

- Startseite, Warenkorb und Admin-Einstieg von `onvis-shop.de` antworteten weiterhin mit HTTP 200.
- Die zuvor bereitgestellte Plugin-CSS-Datei antwortete erwartungsgemäß mit HTTP 404.
- Im Live-Shopverzeichnis lagen in den letzten 15 Minuten keine aktuellen Logdateien vor.
- Dev blieb aktiv auf Version 1.1.0.
- Dev enthielt unverändert 714 Bilder und 1.704 Fundstellen.
- Alle 158 von JTL inventarisierten Dev-Plugin-Dateien bestanden weiterhin die Prüfsummenprüfung.

## Aktueller Betriebszustand

- `dev.onvis-shop.de`: Plugin 1.1.0 aktiv; Entwicklungs- und Testumgebung.
- `onvis-shop.de`: Plugin nicht installiert.
