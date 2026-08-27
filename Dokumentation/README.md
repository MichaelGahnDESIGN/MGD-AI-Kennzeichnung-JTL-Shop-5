# Technische Dokumentation

Diese Übersicht verweist auf die verständlichen technischen Erläuterungen des Plugins. Sicherheits- und Datenschutzentscheidungen werden jeweils dort beschrieben, wo Betreiberinnen, Betreiber und Entwickler sie leicht wiederfinden können.

Die JTL-Mindestversion 5.7.2 steht im Element `MinShopVersion` der `info.xml`. JTL-Shop 5.7.2 wertet dort kein Element `PHPVersion` aus. Deshalb wird PHP 8.1 ehrlich als Projekt- und Buildvertrag über `require.php` sowie `config.platform.php` in der `composer.json` und über die CI-Matrix abgesichert.

## Sicherheit und Datenschutz

- [Admin-Sicherheitsbestätigungen](Admin-Sicherheitsbestaetigungen.md): Datenminimierung, automatische Löschung und sicherer Rückbau der Einmalbestätigungen.
- [Installation und Livetest](Installation-und-Livetest.md): Pflichtbackup, Plugin-Manager, Onvis-Prüfliste und Rollback.
- [Admin-Bildverwaltung](Admin-Bildverwaltung.md): Bildgalerie, Filter, Einzel- und Stapelkennzeichnung.
- [OPC-Kennzeichnung](OPC-Kennzeichnung.md): direkte Bildfelder und fehlertolerante Dateimanager-Kompatibilität.
- [Plugin-Impressum](Impressum.md): geschützte Herstellerangaben ohne Datenbank, Formular oder öffentliche Shopänderung.
- [Release 1.2.0](Release-1.2.0.md): Impressum-Funktion, Paketprüfung und Freigabegrenzen der aktuellen Version.
- [Dev-Abnahme vom 27. August 2026](Dev-Abnahme-2026-08-27.md): verifizierter Stand 1.1.1 mit stabilen Inline-Labels und OPC-Hintergrundbildern.
- [Release 1.1.1](Release-1.1.1.md): Funktionsumfang, Paketprüfung und historische Freigabehinweise.
- [Entscheidungen](Entscheidungen.md): nachvollziehbare Produkt- und Sicherheitsentscheidungen.
- [Risiken und Grenzen](Risiken.md): bekannte Kompatibilitätsgrenzen und empfohlene Gegenmaßnahmen.
- [Versionen](Versionen.md): freigegebene und historisch dokumentierte Versionsstände.
- [Rollback 1.1.0](Rollback-1.1.0.md): sicherer Rückfall ohne Löschen der Plugin-Daten.
- [Dev-Abnahme vom 22. August 2026](Dev-Abnahme-2026-08-22.md): verifizierter Dev-Stand 1.1.0, sichere Laufzeittests und Historie des JTL-Installationsfehlers 421.
- [Live-Abnahme vom 22. August 2026](Live-Abnahme-2026-08-22.md): Installation von 1.1.0, Integritätsprüfung, datensparsamer Laufzeittest und Rückfallzustand.
- [Live-Rollback vom 23. August 2026](Live-Rollback-2026-08-23.md): vollständiger Rückbau auf Live und unveränderter Dev-Teststand.
- [Datenschutz und Sicherheit](Datenschutz-und-Sicherheit.md): gespeicherte Daten, optionale Netzwerkzugriffe und Schutzgrenzen.
- [OPC-CSS-Klassen](OPC-CSS-Klassen.md): dokumentierte Kombinationen für bewusst markierte Elemente.
- [Release 1.0.0](Release-1.0.0.md): freizugebender Funktionsstand und Betriebsbedingungen.
