# Umsetzungsplan: Rekursiver OPC-Dateispeicherscan

> **For agentic workers:** REQUIRED SUB-SKILL: executing-plans. Ausführung in diesem freigegebenen Arbeitsstand, mit test-driven-development und verification-before-completion.

**Goal:** OPC-Uploads einschließlich verschachtelter und unbenutzter Bilder sicher in der bestehenden Galerie anzeigen.

**Architecture:** Ein geprüfter Speicherpfad, ein begrenzter rekursiver Dateilister und ein eigener paginierter OPC-Adapter ergänzen den bisherigen Seitenadapter. Beide Beiträge laufen im gleichen atomaren Datenbankabgleich. Kein Netzwerkzugriff, keine Änderungen an Originalbildern oder Kennzeichnungen.

**Tech Stack:** PHP 8.1+, JTL-Shop 5, PHPUnit 10, PHPStan, vorhandene Smarty-Galerie.

---

## Aufgabe 1: Dateisystemvertrag testgetrieben umsetzen

- [x] In `tests/Support/OpcStorageFixture.php` ausschließlich temporäre Testordner verwalten.
- [x] In `tests/Unit/Scanner/OpcStorageSourceAdapterTest.php` zuerst fehlschlagende Tests für Root, tiefe Ordner, Umlaute/Leerzeichen, Dateitypen, Pagination und erneuten Scan schreiben.
- [x] Sicherheitsfälle ergänzen: Symlinks, fehlende/unlesbare Wurzel, 32 Ebenen, 20.000 Einträge, 9.999 Bilder, keine stillen Teilresultate.
- [x] Rot prüfen: `vendor/bin/phpunit tests/Unit/Scanner/OpcStorageSourceAdapterTest.php`.
- [x] `Scanner/Filesystem/OpcStorageRoot.php` prüft den festen Speicherpfad unter der serverseitigen Shopwurzel.
- [x] `Scanner/Filesystem/OpcStorageFileLister.php` sammelt einmalig begrenzt reguläre Rasterbildpfade und sortiert sie deterministisch; unverändert normalisierbare Dateinamen sind Pflicht.
- [x] `Scanner/Filesystem/OpcStorageScanException.php` und `OpcStorageScanFailure.php` kapseln ausschließlich feste, pfadfreie Fehlermeldungen.
- [x] `Scanner/Adapter/OpcStorageSourceAdapter.php` übersetzt die Liste in Seiten und stabile `opc-datei:`-Fundstellen; Offset 0 baut die Liste neu auf.
- [x] Dieselben Tests grün ausführen.

## Aufgabe 2: Atomare Integration

- [x] `tests/Integration/Scanner/OpcStorageScanTest.php` prüft zuerst fehlschlagend gemeinsame OPC-Beiträge, eine Karte pro Pfad, getrennte Fundstellen, Bestandsschutz, Rollback und doppelte Registrierungen.
- [x] `Service/ImageScanService.php` erlaubt nur die ausdrücklich bekannte Kombination der zwei finalen OPC-Adapter; alle anderen doppelten Quellen bleiben Fehler.
- [x] `Admin/Factory/AdminRuntimeFactory.php` und `adminmenu/assets.php` geben PFAD_ROOT ausschließlich serverseitig weiter. Kein Verzeichniszugriff beim bloßen Anzeigen.
- [x] `Scanner/SourceScanPage.php` und `SourceAdapterPageInterface.php` erläutern die gemeinsame Bedeutung der Datensatzzahl.
- [x] `Admin/Action/ScanAction.php` zeigt ausschließlich die sicheren Dateiscanfehler, generische Ausnahmebehandlung bleibt bestehen.
- [x] Gezielte Integrations- und vorhandene Admin-/Scan-Tests ausführen; Filter, Sortierung und Pagination mit neu gefundenen Bildern prüfen.

## Aufgabe 3: Verständliche Hilfe und Abschlussprüfung

- [x] Galerie/Details erklären den Unterschied zwischen OPC-Dateispeicher und Verwendung auf OPC-Seiten.
- [x] README, Changelog und passende Wiki-Seite aktualisieren: Bedienung, Grenzen, Fehlerfälle und Umbenennungen erklären.
- [x] `composer test`, `composer test:js`, `composer analyse`, `composer style` und vorhandene Paketprüfung ausführen.
- [x] Diff auf unbeabsichtigte Daten-/Netzwerk-/Livezugriffe prüfen; nur eigene Änderungen committen.
- [x] `graphify update .` ausschließlich lokal/AST ausführen, sofern installiert; keine kostenpflichtige Extraktion.
- [x] Ergebnis mit tatsächlichen Testnachweisen berichten. Dev-/Live-Installation und öffentliche Veröffentlichung nicht als erledigt behaupten.

## Freigabe und Abgrenzung

Michael hat die Spezifikation mit „leg los“ freigegeben. Die vorhandene isolierte Branch `codex/opc-storage-scan` wird weiterverwendet. Kein erneuter Layoutentwurf nötig. Kein Live-Deployment, kein neuer Dienst und keine automatische Kennzeichnung von Bildern.
