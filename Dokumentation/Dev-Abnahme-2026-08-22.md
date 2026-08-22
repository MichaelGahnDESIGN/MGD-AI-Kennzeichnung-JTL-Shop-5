# Dev-Abnahme vom 22. August 2026

## Freigabestand 1.1.0

Version 1.1.0 wurde zuerst lokal und anschließend auf der vollständig getrennten
Testumgebung `dev.onvis-shop.de` geprüft. Der Dev-Shop verwendet eine eigene
Datenbank, einen eigenen Root-Pfad und eine eigene Shop-URL. Der Wartungsmodus
blieb während der gesamten Abnahme aktiv. Der Live-Shop wurde in dieser Phase
nicht verändert.

Vor dem Update wurden ausschließlich folgende pluginbezogenen Daten lokal
gesichert:

- das vollständige Pluginverzeichnis der Version 1.0.0;
- die vier Plugin-Datenbanktabellen;
- die zugehörigen JTL-Plugin-Metadaten.

Kunden-, Bestell- und Zahlungsdaten wurden weder exportiert noch in das
Repository übernommen. Das Backup liegt außerhalb des Repositorys unter
`BACKUPS/MGD_AI_Kennzeichnung/` und ist durch Dateirechte auf den lokalen
Benutzer beschränkt.

## Installation

Das exakt lokal geprüfte ZIP wurde per SHA-256 verifiziert und anschließend
mit JTLs eigenem Extraktor, Validator und Updater installiert. Dadurch wurden
derselbe Lebenszyklus und dieselben Datenbankaktionen verwendet wie im
Plugin-Manager. Es gab keine manuelle Änderung an JTL-Core, NOVA oder
OnvisTheme.

- JTL-Shop: `5.7.2`
- Server-PHP: `8.5.3-nmm1`
- Plugin: `MGD AI Kennzeichnung 1.1.0`
- interne JTL-Plugin-ID auf Dev: `47`
- Status: installiert und aktiviert
- Release: `dist/MGD_AI_Kennzeichnung-1.1.0.zip`
- SHA-256: `3d5719f45e2c5b661f46d63d1dfe5152e33ed2ff23d49d02d80e325260d2b95c`

## Geprüfte Funktionen

- JTL-Pluginvalidierung: erfolgreich;
- PHP-Syntax auf dem Dev-Server: 135 Dateien fehlerfrei;
- JTL-Dateiintegrität: 158 Release-Dateien unverändert;
- Bildgalerie: reale Datenbankabfrage und Galeriekarte erfolgreich;
- Einzelkennzeichnung: Speichern und erneutes Laden erfolgreich;
- sichere Bildvorschau: same-origin Vorschau erfolgreich ausgeliefert;
- Frontend-Repository: sichtbare Testkennzeichnung korrekt ausgeliefert;
- OPC: Portlet `AI-Philosophie` aktiv in der Gruppe `Custom Portlets`;
- öffentliche Plugin-CSS- und JavaScript-Dateien: HTTP 200;
- Dev-Startseite: erwartetes HTTP 503 wegen des aktiven Wartungsmodus;
- neue Plugin-, PHP-Fatal- oder Parse-Fehler in aktuellen Logs: keine.

Für den Laufzeittest wurde genau ein zufällig benanntes Testbild angelegt und
gekennzeichnet. Danach wurden Bild und Testdatensatz wieder entfernt. Der
Datenbestand entsprach anschließend exakt dem Ausgangsstand von 714 Assets und
1.704 Fundstellen.

## Ergebnis und verbleibende Sichtprüfung

Die technische Dev-Abnahme ist **grün**. Die Galerie-, Speicher-, Vorschau-,
Frontend- und OPC-Datenwege funktionieren im echten JTL-System. Safari hat den
Inhalt der bereits angemeldeten Adminseite für die automatisierte
Bildschirmaufnahme nicht sichtbar freigegeben; deshalb bleibt die rein
optische Kontrolle der Galerie im Backend ein zusätzlicher, nicht technischer
Abnahmeschritt. Sie verändert keine Daten und ist kein Hinweis auf einen
Pluginfehler.

## Historie: Installationsfehler 421 bei Version 1.0.0

Der erste Upload von Version 1.0.0 war vollständig, wurde von JTL jedoch mit
Fehlercode 421 abgelehnt. Ursache war der Hosterzusatz `-nmm1` in der
PHP-Version `8.5.3-nmm1`. Die damalige Prüfung akzeptierte nur eine rein
dreiteilige PHP-Version.

Die Kompatibilitätsprüfung erkennt seitdem den eindeutigen numerischen
Versionskern und erlaubt dahinter ausschließlich einen begrenzten technischen
Hosterzusatz. Zu alte PHP-Versionen bleiben weiterhin gesperrt. Regressionstests
decken beide Fälle ab. Version 1.0.0 wurde danach auf Dev erfolgreich
installiert und bildete den gesicherten Ausgangsstand für das Update auf 1.1.0.
