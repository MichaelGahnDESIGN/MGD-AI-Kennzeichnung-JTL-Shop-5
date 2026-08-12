# Sichere Admin-Laufzeitkomposition – Umsetzungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Ziel:** Die Bildverwaltung wird im JTL-5.7.2-Adminbereich sicher komponiert, streng normalisiert und vollständig bedienbar.

**Architektur:** Ein dünner Einstieg übergibt an einen Controller, der ausschließlich DTOs eines begrenzenden Request-Normalizers verarbeitet. Eine Factory erstellt Actions und offizielle JTL-Adapter; Vorschauoperationen werden vollständig serverseitig und einmalig gespeichert. Datenbankmutationen bleiben ownership-geprüft und atomar.

**Technik:** PHP 8.1, JTL-Shop 5.7.2, PHPUnit 10, PHPStan Level max, PHP-CS-Fixer.

---

### Aufgabe 1: Strikte HTTP-Grenze

**Dateien:**
- Tests: `tests/Unit/Admin/AdminRequestNormalizerTest.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Http/AdminRequestNormalizer.php`
- Neu: einzelne DTOs unter `plugin/MGD_AI_Kennzeichnung/Admin/Request/`

- [ ] Tests für kanonische Dezimal-IDs, exakte Checkboxwerte, begrenzte Tokens sowie unbekannte, tiefe oder übergroße Arrays schreiben.
- [ ] Test ausführen und das erwartete Fehlen des Normalizers bestätigen.
- [ ] Minimalen Normalizer ohne Coercion, Trim oder Case-Folding implementieren.
- [ ] Fokussierten Test erneut ausführen.

### Aufgabe 2: Unveränderliche serverseitige Vorschauoperationen

**Dateien:**
- Tests: `tests/Unit/Admin/StoredOperationConfirmationTest.php`, `tests/Unit/Admin/BulkUpdateActionTest.php`
- Ändern: Confirmation-Ports, Adapter, Preview-/Execute-Actions und Results

- [ ] Tests schreiben, nach denen `consume()` die gespeicherte Operation zurückgibt und nur Token sowie CSRF aus dem Browser benötigt.
- [ ] Erwartetes Rot für alte Signaturen bestätigen.
- [ ] Zufälligen Token, Sitzungsbindung, Ablauf, Einmalverbrauch und serverseitige Payload implementieren.
- [ ] Replay-, Fremdsitzungs- und manipulierte Payload-Tests grün ausführen.

### Aufgabe 3: Controller und JTL-Komposition

**Dateien:**
- Tests: `tests/Integration/Admin/AdminControllerCompositionTest.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Controller/AdminAssetController.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php`
- Neu: getrennte Adapter unter `plugin/MGD_AI_Kennzeichnung/Admin/Adapter/`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/assets.php`, `plugin/MGD_AI_Kennzeichnung/info.xml`

- [ ] Composition-Test mit gefälschtem Request und gefälschten Ports schreiben.
- [ ] Erwartetes Rot für fehlenden Controller bestätigen.
- [ ] Allowlist-Dispatch implementieren: GET nur Anzeige, POST nur definierte Mutationen.
- [ ] JTL-Adapter ausschließlich an belegte 5.7.2-Dienste koppeln und Direktzugriff abweisen.
- [ ] Include- und Controller-Test grün ausführen.

### Aufgabe 4: Vollständige getrennte Oberfläche

**Dateien:**
- Tests: `tests/Structure/AdminTemplateContractTest.php`
- Ändern/Neu: Templates unter `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/`

- [ ] Rote Strukturtests für Scan, vollständige Feldmasken, Bereinigungsvorschau und Pagination schreiben.
- [ ] Templates mit Escaping, POST/CSRF, Labels, ARIA und ohne Inline-Code vervollständigen.
- [ ] Strukturtests grün ausführen.

### Aufgabe 5: Geschlossene Mutation und robuste Transaktionen

**Dateien:**
- Tests: `tests/Unit/Admin/BulkUpdateActionTest.php`, `tests/Integration/Infrastructure/AdminAssetRepositoryTest.php`, neuer Cleanup-Repository-Test
- Ändern: `AdminInputValidator.php`, `AssetRepository.php`, `UsageRepository.php`

- [ ] Tests für exakte Enums, unbekannte/zusätzliche Werte sowie Rollback false/throw schreiben.
- [ ] Erwartetes Rot bestätigen.
- [ ] Validierung schließen und Rollbackfehler mit ursprünglicher Ausnahme als `previous` eskalieren.
- [ ] Atomaritäts- und Regressionstests grün ausführen.

### Aufgabe 6: Abschlussnachweise

- [ ] Gesamte PHPUnit-Suite ausführen.
- [ ] PHPStan Level max, Style, Composer strict/platform und PHP-8.1-Lint ausführen.
- [ ] Diff-, Secret-, PII-, Token-, Pfad-, SQL- und Dateilöschungsprüfung durchführen.
- [ ] Graph mit `graphify update .` aktualisieren, generierte Cache-Artefakte nicht ungeprüft veröffentlichen.
- [ ] Exakt einen Folge-Commit erstellen und nicht pushen.
