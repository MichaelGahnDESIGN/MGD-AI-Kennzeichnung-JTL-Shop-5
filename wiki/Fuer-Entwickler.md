# Für Entwickler

## Architektur

Das Plugin trennt Verantwortlichkeiten in kleine, nachvollziehbare Bereiche:

- `Domain/` – geschlossene fachliche Werte;
- `Scanner/` – lokale JTL-Quellen und Pfadnormalisierung;
- `Service/` – Scan, Darstellung, Philosophie und Kompatibilität;
- `Infrastructure/Database/` – eigene Plugin-Repositorys;
- `Admin/` – Aktionen, Berechtigung, CSRF, Bestätigungen und Views;
- `Presentation/` – sichere Frontend-Ausgabe;
- `Portlets/` – AI-Philosophie und OPC-Editorintegration;
- `Migrations/` und `Setup/` – Tabellen und Lebenszyklus;
- `adminmenu/` – Galerie und Adminoberfläche;
- `frontend/` – lokale CSS-/JavaScript-Ausgabe.

## Entwicklungsumgebung

Voraussetzungen:

- PHP 8.1 oder neuer;
- Composer;
- Node.js für die JavaScript-Vertragstests;
- Git.

```bash
composer install
composer validate --strict
composer test
composer test:js
composer analyse
composer style
```

## Release bauen

```bash
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.1.1.zip
shasum -a 256 dist/MGD_AI_Kennzeichnung-1.1.1.zip
```

Das Buildskript erzeugt ein symlinkfreies JTL-Installationspaket. Abhängigkeiten und Entwicklungsartefakte gehören nicht in das Release-ZIP.

## Sicherheitsverträge

- keine Secrets im Repository;
- keine Kundendaten in Tests;
- keine freien CSS-Klassen aus Eingaben;
- nur parametrisierte Datenbankzugriffe;
- schreibende Admin-Aktionen mit Berechtigung und CSRF;
- Stapeländerungen nur nach gebundener Einmalbestätigung;
- lokale Bildpfade über Positivlisten normalisieren;
- keine externen Bilddownloads;
- HTML der AI-Philosophie bereinigen;
- Shop-Frontend bei unerwartetem Pluginfehler nicht unterbrechen.

## Frontend-Integration

JTL-Shop 5.7.2 übergibt im Outputfilter ein phpQuery-Dokument. Die verwendete phpQuery-Version unterstützt nicht jeden modernen jQuery-Selektor in jedem Kontext.

Für responsive Bilder wird deshalb zuerst der direkte Elternknoten des `<img>` bestimmt. Ist dieser ein `<picture>`, wird dessen äußerer Link oder Block als Positionsrahmen verwendet. Ein Label innerhalb von `<picture>` wäre ungültiges HTML.

Hintergrundbilder werden nur über sichere, aus einem geprüften Dateinamen erzeugte Selektoren für `style` und `data-image-src` gesucht.

## Tests

Die Suite deckt unter anderem ab:

- Domainwerte und Eingabenormalisierung;
- Scantransaktionen und harte Grenzen;
- Repositoryzugriffe und Eigentumsmarker;
- CSRF-, Berechtigungs- und Bestätigungsreihenfolge;
- Galerie- und Admin-Verträge;
- OPC- und Dateimanager-Erkennung;
- Labelvorschau und Dialogverhalten;
- Frontend-Selektoren, `picture`, Links und Hintergründe;
- Paketstruktur und Dokumentationsverträge;
- PHPStan und Coding Style.

## Beiträge

Vor größeren Änderungen sollten Ziel, Datenschutzfolgen, Rückfallstrategie und Testplan beschrieben werden. Quellcode, Kommentare und technische Dokumentation werden auf Deutsch, verständlich und als UTF-8 gepflegt.

Führen Sie vor jedem Pull Request die vollständige Qualitätskette aus. Umgehen Sie fehlgeschlagene Hooks nicht mit `--no-verify`.
