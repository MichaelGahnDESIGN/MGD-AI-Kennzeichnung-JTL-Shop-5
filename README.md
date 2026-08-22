# MGD AI Kennzeichnung für JTL-Shop 5

Dieses Plugin verwaltet und zeigt transparente Kennzeichnungen für KI-generierte oder KI-bearbeitete Bilder in JTL-Shop 5. Es führt **keine automatische KI-Erkennung** durch: Ein berechtigter Mensch prüft und setzt den Status bewusst.

## Funktionsumfang – Version 1.1.0

- responsive **Bildgalerie** statt technischer Pfadliste, mit Vorschau, Filtern, Sortierung und Pagination;
- direkter Bearbeitungsdialog mit Live-Vorschau und explizitem Button **„Kennzeichnung speichern“**;
- lokale Erfassung von Produkt-, Kategorie-, Hersteller-, Banner- und OPC-Bildern;
- Status `ungeprüft`, `keine Kennzeichnung`, `KI-generiert`, `teilweise KI-generiert`, `KI-bearbeitet` und `Deepfake`;
- deutsche und englische, barrierearme Labels mit vier Positionen und drei Darstellungen;
- geschützte Einzel- und Stapelbearbeitung über JTL-Berechtigung und CSRF-Prüfung;
- direkte Kennzeichnung an eindeutig erkannten lokalen Bildfeldern im OnPage Composer;
- optionale, fehlertolerante Kennzeichnung einer einzelnen Bildauswahl im JTL-Dateimanager;
- zweisprachige AI-Philosophie als OPC-Portlet unter **Custom Portlets**;
- optionale Herstellernennung und optionale, datensparsame Updatehinweise – beide standardmäßig aus.

Die Originalbilder werden nicht verändert. Das Plugin speichert nur die Kennzeichnung und deren Anzeigeeinstellungen.

## Voraussetzungen und getestete Zielumgebung

- JTL-Shop 5.7.2 oder neuer;
- PHP 8.1 oder neuer;
- Standardtemplate NOVA sowie OnvisTheme auf Basis NOVA 1.7.1;
- moderne Browser mit aktiviertem HTTPS.

Die verständliche Bedienung steht in [Admin-Bildverwaltung](Dokumentation/Admin-Bildverwaltung.md) und [OPC-Kennzeichnung](Dokumentation/OPC-Kennzeichnung.md). Backup, Dev-Test, Installation auf `onvis-shop.de` und sicherer Rückfall sind in [Installation und Livetest](Dokumentation/Installation-und-Livetest.md) beschrieben.

## Datenschutz und Sicherheit

Kennzeichnungen, lokale Pfade und Philosophie-Texte bleiben in eigenen Shop-Datenbanktabellen. Es werden keine Bilder an KI-Dienste gesendet. Nur wenn Updatehinweise bewusst aktiviert werden, fragt das Plugin höchstens alle zwölf Stunden öffentliche Release-Metadaten von GitHub ab. Details: [Datenschutz und Sicherheit](Dokumentation/Datenschutz-und-Sicherheit.md).

## Entwicklung

```bash
composer install
composer test
composer test:js
composer analyse
composer style
bash scripts/build-release.sh
```

Das installierbare Paket entsteht als `dist/MGD_AI_Kennzeichnung-1.1.0.zip`. Quellcode und Dokumentation stehen unter `GPL-3.0-or-later`.
