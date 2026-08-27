# JTL-Inline-Positionierung der KI-Kennzeichnung – Implementierungsplan

> **Für agentische Bearbeiter:** ERFORDERLICHER SUB-SKILL: Diesen Plan mit `executing-plans` Aufgabe für Aufgabe umsetzen. Alle Schritte werden über die Checkboxen nachverfolgt.

**Ziel:** KI-Kennzeichnungen liegen bei normalen, verlinkten und responsiven Bildern sowie bei OPC-Hintergrundbildern zuverlässig innerhalb der gespeicherten Bildecke.

**Architektur:** Eine neue, rein funktionale Zielsuche erzeugt nur geprüfte Selektoren. Der vorhandene Dokument-Integrator bestimmt anschließend einen semantisch gültigen Positionsrahmen: außerhalb eines `<picture>`, innerhalb eines Links beziehungsweise direkt auf einem OPC-Hintergrundcontainer. CSS normalisiert nur tatsächlich inline dargestellte Rahmen.

**Technik:** PHP 8.1, JTL-Shop-5-Outputfilter mit phpQuery, CSS, PHPUnit 10, Composer, Node-Test-Runner, Git.

---

## Dateistruktur

- Neu: `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendLabelTargetLocator.php` – sichere Selektoren für Bilder und Hintergrundbilder.
- Ändern: `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php` – Zielwahl und genau einmalige Label-Integration.
- Ändern: `plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css` – stabiler Inline-Rahmen ohne Layoutsprung.
- Neu: `tests/Unit/Presentation/FrontendLabelTargetLocatorTest.php` – Selektorverträge und Eingabegrenzen.
- Ändern: `tests/Unit/Presentation/FrontendDocumentIntegratorTest.php` – responsive Bilder, Links, Hintergründe und Doppelschutz.
- Ändern: `tests/Structure/FrontendAssetContractTest.php` – CSS-Vertrag für den neuen Rahmen.
- Ändern: `plugin/MGD_AI_Kennzeichnung/info.xml` und `README.md` – Patchversion und verständliche Änderungserklärung.
- Neu: `Dokumentation/Dev-Abnahme-2026-08-27.md` – Testergebnisse, Sicherung und Dev-Abnahme.

### Aufgabe 1: Sichere Zielselektoren

**Dateien:**
- Erstellen: `tests/Unit/Presentation/FrontendLabelTargetLocatorTest.php`
- Erstellen: `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendLabelTargetLocator.php`

- [ ] **Schritt 1: Fehlende Selektorlogik testgetrieben beschreiben**

```php
#[Test]
public function erzeugt_exakte_bild_und_hintergrundselektoren(): void
{
    $locator = new FrontendLabelTargetLocator();

    self::assertSame(
        'img[src="bild.webp"], img[src$="/bild.webp"], '
        . 'img[src*="/bild.webp?"], img[src*="/bild.webp#"]',
        $locator->imageSelector('bild.webp'),
    );
    self::assertStringContainsString('[style*="/bild.webp"]', $locator->backgroundSelector('bild.webp'));
    self::assertStringContainsString('[data-image-src$="/bild.webp"]', $locator->backgroundSelector('bild.webp'));
}

#[Test]
public function weist_unsafe_dateinamen_zurueck(): void
{
    $this->expectException(InvalidArgumentException::class);
    (new FrontendLabelTargetLocator())->imageSelector('bild.webp"] script');
}
```

- [ ] **Schritt 2: Den roten Test ausführen**

Ausführen: `vendor/bin/phpunit tests/Unit/Presentation/FrontendLabelTargetLocatorTest.php`

Erwartung: Fehler, weil `FrontendLabelTargetLocator` noch nicht existiert.

- [ ] **Schritt 3: Minimale, geschlossene Selektorlogik implementieren**

```php
final class FrontendLabelTargetLocator
{
    public function imageSelector(string $filename): string
    {
        $this->assertSafeFilename($filename);

        return implode(', ', [
            'img[src="' . $filename . '"]',
            'img[src$="/' . $filename . '"]',
            'img[src*="/' . $filename . '?"]',
            'img[src*="/' . $filename . '#"]',
        ]);
    }

    public function backgroundSelector(string $filename): string
    {
        $this->assertSafeFilename($filename);

        return implode(', ', [
            '[style*="/' . $filename . '"]',
            '[data-image-src="' . $filename . '"]',
            '[data-image-src$="/' . $filename . '"]',
            '[data-image-src*="/' . $filename . '?"]',
            '[data-image-src*="/' . $filename . '#"]',
        ]);
    }

    private function assertSafeFilename(string $filename): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/D', $filename) !== 1) {
            throw new InvalidArgumentException('Der Bilddateiname ist nicht zulässig.');
        }
    }
}
```

- [ ] **Schritt 4: Den fokussierten Test erneut ausführen**

Ausführen: `vendor/bin/phpunit tests/Unit/Presentation/FrontendLabelTargetLocatorTest.php`

Erwartung: alle Tests in der Datei bestehen.

- [ ] **Schritt 5: Selektorbaustein committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/Presentation/FrontendLabelTargetLocator.php \
  tests/Unit/Presentation/FrontendLabelTargetLocatorTest.php
git commit -m "feat: erkennt sichere Bild- und Hintergrundziele"
```

### Aufgabe 2: Gültige Positionierungsrahmen im Frontend

**Dateien:**
- Ändern: `tests/Unit/Presentation/FrontendDocumentIntegratorTest.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php`

- [ ] **Schritt 1: Fehlende Bild- und Hintergrundfälle als rote Tests ergänzen**

Die Testdokumente bilden getrennte Zielmengen für `<picture>`, direkte Bilder,
Links und Hintergrundcontainer ab. Die zentralen Erwartungen lauten:

```php
self::assertContains('picture > img', $dokument->images->filters);
self::assertContains('a', $dokument->imageHosts->filters);
self::assertContains('mgd-ai-label-host--inline', $dokument->linkHosts->classes);
self::assertContains('mgd-ai-label-host', $dokument->backgrounds->classes);
self::assertStringContainsString('KI-GENERIERT', $dokument->backgrounds->markup[0]);
```

Ein zusätzlicher Test ruft `integrateLabels()` zweimal auf und erwartet auf
jedem Ziel weiterhin genau einen Markup-Eintrag.

- [ ] **Schritt 2: Die neuen Integrator-Tests rot ausführen**

Ausführen: `vendor/bin/phpunit tests/Unit/Presentation/FrontendDocumentIntegratorTest.php`

Erwartung: Die Hintergrund- und Inline-Rahmen-Erwartungen schlagen fehl.

- [ ] **Schritt 3: Bildziele semantisch aufteilen**

`integrateLabels()` verwendet den Locator und teilt die Bildmenge auf:

```php
$bilder = $this->find($dokument, $locator->imageSelector($dateiname));
$pictureBilder = $this->callObjectMethod($bilder, 'filter', 'picture > img');
$direkteBilder = $this->callObjectMethod($bilder, 'not', 'picture > img');

$pictureRahmen = $this->parentOf($this->parentOf($pictureBilder));
$direkteRahmen = $this->parentOf($direkteBilder);

$this->decorateImageHosts($pictureRahmen, $markup);
$this->decorateImageHosts($direkteRahmen, $markup);
```

`decorateImageHosts()` trennt Links von vorhandenen Blockrahmen. Nur Links
erhalten zusätzlich `mgd-ai-label-host--inline`. Vor dem Einfügen wird jeweils
mit `not('.mgd-ai-label-host')` ausgeschlossen, dass ein zweites Label entsteht.

- [ ] **Schritt 4: OPC-Hintergrundcontainer integrieren**

```php
$hintergruende = $this->find($dokument, $locator->backgroundSelector($dateiname));
$neueHintergruende = $this->callObjectMethod($hintergruende, 'not', '.mgd-ai-label-host');
if ($neueHintergruende !== null) {
    $this->callVoidMethod($neueHintergruende, 'addClass', 'mgd-ai-label-host');
    $this->callVoidMethod($neueHintergruende, 'append', $markup);
}
```

- [ ] **Schritt 5: Integrator-Tests grün ausführen**

Ausführen: `vendor/bin/phpunit tests/Unit/Presentation/FrontendDocumentIntegratorTest.php`

Erwartung: alle Integrator-Tests bestehen, ohne Warnungen.

- [ ] **Schritt 6: DOM-Integration committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php \
  tests/Unit/Presentation/FrontendDocumentIntegratorTest.php
git commit -m "fix: verankert KI-Labels an der sichtbaren Bildfläche"
```

### Aufgabe 3: Inline-Rahmen ohne Layoutverschiebung

**Dateien:**
- Ändern: `tests/Structure/FrontendAssetContractTest.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css`

- [ ] **Schritt 1: CSS-Vertrag zuerst erweitern**

```php
self::assertStringContainsString('.mgd-ai-label-host--inline', $css);
self::assertStringContainsString('display: inline-block', $css);
self::assertStringContainsString('max-width: 100%', $css);
self::assertStringContainsString('vertical-align: top', $css);
```

- [ ] **Schritt 2: Strukturtest rot ausführen**

Ausführen: `vendor/bin/phpunit tests/Structure/FrontendAssetContractTest.php`

Erwartung: Der neue Inline-Rahmen fehlt noch.

- [ ] **Schritt 3: Eng begrenzte CSS-Regel ergänzen**

```css
.mgd-ai-label-host--inline {
    display: inline-block;
    max-width: 100%;
    vertical-align: top;
}

.mgd-ai-label-host--inline > picture,
.mgd-ai-label-host--inline > img {
    display: block;
}
```

Die vorhandene Regel `.mgd-ai-label-host { position: relative; }` bleibt der
einzige allgemeine Eingriff. Block-Container erhalten ausdrücklich kein neues
`display` und behalten daher ihr JTL-Layout.

- [ ] **Schritt 4: CSS- und Integrator-Tests ausführen**

Ausführen:

```bash
vendor/bin/phpunit tests/Structure/FrontendAssetContractTest.php \
  tests/Unit/Presentation/FrontendDocumentIntegratorTest.php
```

Erwartung: beide Testsuiten bestehen.

- [ ] **Schritt 5: CSS-Korrektur committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css \
  tests/Structure/FrontendAssetContractTest.php
git commit -m "fix: stabilisiert Inline-Rahmen für verlinkte Bilder"
```

### Aufgabe 4: Version, Dokumentation und vollständige Qualitätsprüfung

**Dateien:**
- Ändern: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Ändern: `README.md`
- Erstellen: `Dokumentation/Dev-Abnahme-2026-08-27.md`

- [ ] **Schritt 1: Patchversion auf 1.1.1 setzen**

In `info.xml` wird ausschließlich die Pluginversion von `1.1.0` auf `1.1.1`
erhöht. Die unterstützte JTL-Version und Installations-/Update-SQL bleiben
unverändert.

- [ ] **Schritt 2: Bedienwirkung verständlich dokumentieren**

README und Dev-Abnahme erklären:

```text
Kennzeichnungen werden als Overlay innerhalb der sichtbaren Bildfläche
ausgegeben. Unterstützt werden normale und verlinkte Bilder, responsive
picture-Ausgaben sowie lokale OPC-Hintergrundbilder. Bilddateien werden nicht
verändert.
```

- [ ] **Schritt 3: Vollständige lokale Qualitätsprüfung ausführen**

```bash
composer validate --strict
composer test
composer test:js
composer analyse
composer style
```

Erwartung: alle Befehle enden mit Exitcode 0; PHPUnit meldet keine Fehler oder
Fehlschläge, Node keine fehlgeschlagenen Tests, PHPStan und CS Fixer keine
Beanstandungen.

- [ ] **Schritt 4: Installationspaket reproduzierbar bauen**

```bash
./scripts/build-release.sh
shasum -a 256 dist/MGD_AI_Kennzeichnung-1.1.1.zip
```

Erwartung: ZIP wird erzeugt und ein SHA-256-Prüfwert ausgegeben. Der Wert wird
in der Dev-Abnahme dokumentiert.

- [ ] **Schritt 5: Version und Dokumentation committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/info.xml README.md \
  Dokumentation/Dev-Abnahme-2026-08-27.md
git commit -m "chore: bereitet Dev-Abnahme von Version 1.1.1 vor"
```

### Aufgabe 5: Kontrolliertes Deployment und visuelle Dev-Abnahme

**Ziel:** ausschließlich `dev.onvis-shop.de`

- [ ] **Schritt 1: Dev-Zustand und Trennung von Live erneut prüfen**

Per SSH werden Shopwurzel, eigene Dev-Datenbank, Wartungsmodus, Pluginversion
und fehlende Wawi-Anbindung geprüft. Es werden keine Zugangsdaten ausgegeben.

Erwartung: Dev ist weiterhin isoliert; `onvis-shop.de` wird nicht verändert.

- [ ] **Schritt 2: Rückfallkopie der Dev-Plugin-Dateien und Tabellen erstellen**

Die Sicherung erhält einen datierten Namen unter
`dev.onvis-shop.de/BACKUPS/`. Gesichert werden ausschließlich
`plugins/MGD_AI_Kennzeichnung` und die vier `xplugin_mgd_ai_*`-Tabellen.

- [ ] **Schritt 3: Exakt das geprüfte ZIP über den JTL-Updateweg einspielen**

Vor und nach dem Upload wird der SHA-256-Wert verglichen. Das Update erfolgt
über den offiziellen Plugin-Manager beziehungsweise denselben bereits
abgenommenen JTL-Aktualisierungsweg; kein Core- oder Template-Override wird
geschrieben.

- [ ] **Schritt 4: Daten- und Funktionskontrolle durchführen**

Zu prüfen sind:

```text
- Plugin 1.1.1 aktiv
- vorhandene vier markierte Assets unverändert gespeichert
- Position top-right und Darstellung dark unverändert
- Bild- und Hintergrundselektoren im ausgelieferten HTML vorhanden
- keine neuen PHP-/JTL-Fehler im relevanten Zeitfenster
```

- [ ] **Schritt 5: Visuelle Abnahme auf der Dev-Startseite**

Desktop und schmale Ansicht prüfen:

```text
- Airlineschienen-Bild: Label innerhalb der oberen rechten Bildecke
- Werbemittel-Kachel: Label innerhalb der oberen rechten Kachelecke
- Link und „Jetzt shoppen“-Interaktion bleiben bedienbar
- kein Überlauf, kein Layoutsprung und keine doppelte Kennzeichnung
```

- [ ] **Schritt 6: Dev-Abnahme vervollständigen und committen**

```bash
git add Dokumentation/Dev-Abnahme-2026-08-27.md
git commit -m "docs: dokumentiert Dev-Abnahme von Version 1.1.1"
```

- [ ] **Schritt 7: Git-Stand sichern**

```bash
git status --short --branch
git log --oneline --decorate -6
```

Erwartung: Arbeitsbaum sauber, alle beschriebenen Änderungen nachvollziehbar.
Ein Push oder Merge nach `main` erfolgt erst nach erfolgreicher Dev-Abnahme;
eine Installation auf `onvis-shop.de` ist ausdrücklich nicht Bestandteil
dieses Plans.
