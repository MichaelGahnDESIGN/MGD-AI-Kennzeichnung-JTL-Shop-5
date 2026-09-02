# Testpaket 1.3.6 – OPC-Unterordner ohne Vorschaubildcache

Version 1.3.6 korrigiert den bei Campingteile24 nachgewiesenen Scan-Abbruch:
Der Dateimanager elFinder legt automatisch erzeugte Vorschaubilder in `.tmb`
ab. Dieser interne Ordner wird jetzt auf jeder Ebene vor dem Betreten
übersprungen. Er gehört nicht zu Ihren kennzeichenbaren Originalbildern.

Alle unterstützten Originalbilder in `media/image/storage/opc/` und dessen
Unterordnern werden weiterhin rekursiv erfasst. Ordner mit Leerzeichen und
Umlauten sowie tiefe Unterordner bleiben unterstützt. Die bestehenden
Sicherheitsgrenzen, Pfadprüfungen und der vollständige Transaktions-Rollback
bei echten Fehlern bleiben erhalten. Andere versteckte Bildordner werden
nicht pauschal freigegeben.

## So verwenden Sie die Korrektur

1. Plugin-Dateien, Konfiguration und eigene Plugin-Tabellen sichern.
2. Das geprüfte Paket `MGD_AI_Kennzeichnung-1.3.6.zip` zuerst auf Dev als
   **manueller ZIP-Upload** installieren und anschließend **Aktualisieren** wählen.
3. In **Bildverwaltung** den **Sicheren Bildscan starten**.
4. **Alle Status**, Quelle **OnPage Composer** und **Alle Fundstellen** wählen.
5. **Galerie anzeigen** anklicken. Über **Details** können Sie den vollständigen
   Pfad eines Bildes prüfen und es anschließend gezielt kennzeichnen.

Bereits gespeicherte Kennzeichnungen, Positionen und Designs werden durch den
Scan nicht geändert. Es gibt keine neue Datenbankmigration. Originaldateien
werden weder verändert noch gelöscht. Der Cache `.tmb` bleibt im Shop bestehen;
er wird lediglich vom Scan ausgeschlossen.

## Prüfung, Freigabe und Rückfall

Die Regressionstests prüfen Cacheordner im Haupt- und Unterordner, tiefe
Cacheinhalte, echte ähnlich benannte Ordner, bestehende Kennzeichnungen und
die unveränderte Sicherheitsprüfung. Der konkrete Prüf- und Installationsstand
wird im Abnahmebericht ergänzt; diese Datei allein bestätigt keine Installation.

Vor einem Live-Update müssen die Dev-Abnahme und eine geprüfte Sicherung
vorliegen. Bei Fehlern zum gesicherten Pluginstand zurückkehren; keine
Deinstallation mit Datenlöschung durchführen. Diese Korrektur benötigt keine
neuen externen Bibliotheken oder Dienste. Es entstehen keine neuen Dienstkosten.
GitHub-Veröffentlichung ist nicht Teil dieses Testpakets.

Fertige ZIPs liegen im Hauptprojekt unter `plugin/`, nicht nur im internen
Buildordner `dist/`. Den SHA-256-Wert aus der zugehörigen `.zip.sha256`-Datei
und die ZIP-Integrität vor einer Installation prüfen.

## Zwischenstand der Prüfung am 2. September 2026

- Lokal: 557 PHP-Tests mit 14.828 Assertions, 142 JavaScript-Tests,
  maximale statische Analyse und Formatprüfung erfolgreich.
- Unabhängiges Code-Review: keine Beanstandungen.
- SHA-256 des geprüften Pakets:
  `d745abd5776409a03ea270073107b307648fd8fb9f942bfa5e24e7380d5f061b`.
- Dev: verschlüsselte, zurückgelesene Sicherung von 187 Plugin-Dateien,
  vier eigenen Tabellen und zugehörigen JTL-Plugin-Metadaten vorhanden.
- Dev: ZIP-Upload erfolgreich. Der neue Scanner liest in der tatsächlichen
  Server-CLI (PHP 8.5.3) 37 Originalbilder, davon 25 in Unterordnern; keine
  `.tmb`-Bilder. Dieser Lesetest allein ersetzt nicht den noch ausstehenden
  vollständigen Backend-Abgleich und die JTL-Update-Bestätigung.
- Campingteile24: 194 Plugin-Dateien verschlüsselt gesichert und zurückgelesen.
  Die Datenbanksicherung benötigt noch den Login in die Datenbankverwaltung.
  Bis dahin keine Installation auf diesem Kunden-Liveshop.
- Onvis-Live wird nicht aktualisiert. Kein GitHub-Release veröffentlicht.

Die Tests mit künstlichem Cache scheiterten vor der Korrektur und bestanden
danach. Die bestehenden Kennzeichnungen werden zusätzlich vor/nach dem echten
Dev-Abgleich verglichen, sobald die Backend-Aktualisierung abgeschlossen ist.
