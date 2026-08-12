# Technische Dokumentation

Diese Übersicht verweist auf die verständlichen technischen Erläuterungen des Plugins. Sicherheits- und Datenschutzentscheidungen werden jeweils dort beschrieben, wo Betreiberinnen, Betreiber und Entwickler sie leicht wiederfinden können.

Die JTL-Mindestversion 5.7.2 steht im Element `MinShopVersion` der `info.xml`. JTL-Shop 5.7.2 wertet dort kein Element `PHPVersion` aus. Deshalb wird PHP 8.1 ehrlich als Projekt- und Buildvertrag über `require.php` sowie `config.platform.php` in der `composer.json` und über die CI-Matrix abgesichert.

## Sicherheit und Datenschutz

- [Admin-Sicherheitsbestätigungen](Admin-Sicherheitsbestaetigungen.md): Datenminimierung, automatische Löschung und sicherer Rückbau der Einmalbestätigungen.
