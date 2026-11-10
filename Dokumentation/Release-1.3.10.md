# Release 1.3.10 – Bereich Updates (MGD Update Client)

## Was sich für Sie verbessert

Im Plugin-Backend gibt es den neuen Menüpunkt **Updates**. Dort sehen Sie,
welche Version installiert ist und ob es eine neuere gibt.

So läuft es ab:

1. Sie klicken auf **Jetzt prüfen**. Erst dann fragt das Plugin nach.
2. Gibt es eine neue Version, bestätigen Sie den Download mit einem Haken.
3. Das Plugin lädt das ZIP von GitHub und rechnet die SHA-256-Prüfsumme nach.
4. Sie laden das geprüfte ZIP herunter und installieren es von Hand im
   JTL-Plugin-Manager.

Das Plugin installiert keine Updates automatisch. Es gibt keinen
Hintergrund-Job und keinen Abruf ohne Ihren Klick oder Ihr Einschalten.

## So arbeitet der Update-Kanal

Der Kanal arbeitet im einfachen Modus: Eine kleine Textdatei auf
theme.michael-gahn.de (`plugin-updates/mgd-ai-kennzeichnung.json`) nennt
Version und SHA-256-Prüfsumme. Das ZIP selbst kommt von GitHub. Das ist eine
Prüfsumme, kein kryptografischer Herkunftsnachweis. Eine Variante mit
zusätzlichen Schlüsseln ist vorbereitet, aber noch nicht aktiv.

Die alte GitHub-Abfrage (Einstellung „Updatehinweise über GitHub“) entfällt.
Der Darstellungstab ruft nichts mehr bei GitHub ab.

## Paket und Sicherheit

Verwenden Sie **MGD_AI_Kennzeichnung-1.3.10.zip** aus den Release-Anhängen,
nicht den automatisch erzeugten Download **Source code (zip)**. Vergleichen
Sie die SHA-256-Prüfsumme mit der beigefügten Datei.

Die Änderung benötigt keine neue Datenbankmigration und verändert keine
Shopbilder, Kennzeichnungen oder gespeicherten Designwerte. Bei einem Abruf
sieht der Server wie bei jedem Webaufruf die Server-IP und den User-Agent
Ihres Shops. Mehr dazu steht in
[Datenschutz und Sicherheit](Datenschutz-und-Sicherheit.md).

## Prüf- und Installationsstand

Geprüft wurde lokal am 10. November 2026:

- alle PHP-Tests (PHPUnit) ohne Fehler;
- Smoke-Test des Bereichs Updates (`php tests/update-smoke.php`) ohne Fehler;
- Paketbau mit `scripts/build-release.sh` und ZIP-Integrität.

Noch **nicht** geprüft:

- kein Dev-Test im echten JTL-Shop;
- keine Installation in einem Live-Shop;
- kein zusätzlicher Lauf unter PHP 8.1 (lokal lief PHP 8.5).

Bitte zuerst im Dev-Shop testen, danach erst im Live-Shop aktualisieren.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen. Ein
Downgrade allein durch Upload eines älteren ZIPs ist kein bestätigter
Rollback. Frühere Release-Pakete bleiben auf GitHub erhalten.
