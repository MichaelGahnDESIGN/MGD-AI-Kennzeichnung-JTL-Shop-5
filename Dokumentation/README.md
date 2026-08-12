# Technische Dokumentation

Hier entsteht die verständliche technische Dokumentation des Plugins. Der aktuelle Stand umfasst ausschließlich Projektstruktur, Pluginmetadaten und Qualitätswerkzeuge; eine Kennzeichnungslogik ist noch nicht vorhanden.

Die JTL-Mindestversion 5.7.2 steht im Element `MinShopVersion` der `info.xml`. JTL-Shop 5.7.2 wertet dort kein Element `PHPVersion` aus. Deshalb wird PHP 8.1 ehrlich als Projekt- und Buildvertrag über `require.php` sowie `config.platform.php` in der `composer.json` und über die CI-Matrix abgesichert.
