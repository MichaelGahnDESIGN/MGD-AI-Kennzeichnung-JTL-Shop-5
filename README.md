# MGD AI Kennzeichnung für JTL-Shop 5

Dieses Plugin verwaltet und zeigt transparente Kennzeichnungen für KI-generierte oder KI-bearbeitete Bilder in JTL-Shop 5. Es führt **keine automatische KI-Erkennung** durch: Ein berechtigter Mensch prüft und setzt den Status bewusst.

## Funktionsumfang 1.0.0

- lokale Erfassung von Produkt-, Kategorie-, Hersteller-, Banner- und OPC-Bildern;
- Status `ungeprüft`, `keine Kennzeichnung`, `KI-generiert`, `teilweise KI-generiert`, `KI-bearbeitet` und `Deepfake`;
- deutsche und englische, barrierearme Labels mit vier Positionen und drei Farbschemata;
- sicherer CSS-/JavaScript-Fallback für bewusst mit `.mgd-ai-label` markierte OPC-Elemente;
- zweisprachige AI-Philosophie als eigenes OPC-Portlet;
- transaktionale Stapelbearbeitung mit Rechten, CSRF-Schutz und einmaliger Bestätigung;
- optionale Herstellernennung und optionale, datensparsame Updatehinweise – beide standardmäßig aus.

## Voraussetzungen und getestete Zielumgebung

- JTL-Shop 5.7.2 oder neuer;
- PHP 8.1 oder neuer;
- Standardtemplate NOVA sowie OnvisTheme auf Basis NOVA 1.7.1;
- moderne Browser mit aktiviertem HTTPS.

Die verbindliche Anleitung für Backup, Wartungsfenster, Installation im Plugin-Manager, Prüfung auf `https://onvis-shop.de` und Rollback steht in [Installation und Livetest](Dokumentation/Installation-und-Livetest.md).

## Datenschutz

Kennzeichnungen, lokale Pfade und Philosophie-Texte bleiben in eigenen Shop-Datenbanktabellen. Es werden keine Bilder an KI-Dienste gesendet. Nur wenn Updatehinweise bewusst aktiviert werden, fragt das Plugin höchstens alle zwölf Stunden öffentliche Release-Metadaten von GitHub ab. Details: [Datenschutz und Sicherheit](Dokumentation/Datenschutz-und-Sicherheit.md).

## Entwicklung

```bash
composer install
composer test
composer analyse
composer style
bash scripts/build-release.sh
```

Das installierbare Paket entsteht als `dist/MGD_AI_Kennzeichnung-1.0.0.zip`. Quellcode und Dokumentation stehen unter `GPL-3.0-or-later`.
