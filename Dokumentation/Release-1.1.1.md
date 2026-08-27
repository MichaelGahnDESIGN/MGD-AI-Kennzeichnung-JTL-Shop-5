# Release 1.1.1

## Ziel des Releases

Version 1.1.1 stabilisiert die sichtbare Kennzeichnung im JTL-Frontend. Der
Schwerpunkt liegt auf realen JTL- und OPC-Ausgaben, bei denen ein Bild nicht
immer als einfaches `<img>` vorliegt.

## Verbesserungen

- Kennzeichnung normaler lokaler Bilder;
- Kennzeichnung verlinkter Bilder, ohne das Linkziel zu verändern;
- gültige Einbindung bei responsiven `<picture>`-Ausgaben;
- Kennzeichnung statischer OPC-Hintergrundbilder;
- Kennzeichnung verzögert geladener OPC-Hintergrundbilder über
  `data-image-src`;
- stabiler Inline-Rahmen, damit das Label innerhalb der sichtbaren Bildfläche
  bleibt;
- Schutz vor doppelter Ausgabe;
- Kompatibilität mit phpQuery 0.9.5 aus JTL-Shop 5.7.2.

## Unveränderte Daten

Das Update verändert keine Originalbilder, Linkziele, OPC-Inhalte oder
vorhandenen Kennzeichnungsentscheidungen. Status, Position und Darstellung
bleiben pro Bild erhalten.

## Installierbares Paket

Dateiname: `MGD_AI_Kennzeichnung-1.1.1.zip`

SHA-256:
`6628ac33d2437273ddd1548375c71eaaa58810f805a27e4dac6c1588f3235cce`

Der Hash wird beim finalen Release-Build erneut geprüft. Falls sich der
reproduzierbare Build durch dokumentationsunabhängige Paketänderungen ändern
sollte, wird ausschließlich der final verifizierte Hash im GitHub-Release
veröffentlicht.

## Qualitätssicherung

- 427 PHP-Tests mit 13.239 Assertions;
- 13 JavaScript-Tests;
- PHPStan über 185 Dateien;
- PHP-CS-Fixer-Prüfung über 185 Dateien;
- Composer-Validierung;
- ZIP-Integritätsprüfung;
- echter JTL/phpQuery-Laufzeittest;
- visuelle Dev-Prüfung im OnvisTheme.

## Freigabegrenze

Die Version wurde auf einer getrennten Dev-Installation mit eigener Datenbank,
Wartungsmodus und eigener Sicherung geprüft. Ein produktiver Betreiber muss
vor der Installation trotzdem ein eigenes vollständiges Backup anlegen und das
Paket zuerst in seiner eigenen Testumgebung prüfen.

## Rechtlicher Hinweis

Das Plugin unterstützt organisatorische Transparenz, ersetzt aber keine
rechtliche Prüfung. Ob und in welcher Form ein konkreter Inhalt gekennzeichnet
werden muss, entscheidet der jeweilige Betreiber in eigener Verantwortung.
