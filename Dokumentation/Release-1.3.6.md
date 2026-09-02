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
ist im folgenden Abnahmeabschnitt festgehalten.

Vor einem Live-Update müssen die Dev-Abnahme und eine geprüfte Sicherung
vorliegen. Bei Fehlern zum gesicherten Pluginstand zurückkehren; keine
Deinstallation mit Datenlöschung durchführen. Diese Korrektur benötigt keine
neuen externen Bibliotheken oder Dienste. Es entstehen keine neuen Dienstkosten.
GitHub-Veröffentlichung ist nicht Teil dieses Testpakets.

Fertige ZIPs liegen im Hauptprojekt unter `plugin/`, nicht nur im internen
Buildordner `dist/`. Den SHA-256-Wert aus der zugehörigen `.zip.sha256`-Datei
und die ZIP-Integrität vor einer Installation prüfen.

## Abnahme am 2. September 2026

- Lokal: 557 PHP-Tests mit 14.828 Assertions, 142 JavaScript-Tests,
  maximale statische Analyse und Formatprüfung erfolgreich.
- Unabhängiges Code-Review: keine Beanstandungen.
- SHA-256 des geprüften Pakets:
  `d745abd5776409a03ea270073107b307648fd8fb9f942bfa5e24e7380d5f061b`.
- Dev: verschlüsselte, zurückgelesene Sicherung von 187 Plugin-Dateien,
  vier eigenen Tabellen und zugehörigen JTL-Plugin-Metadaten vorhanden.
- Dev: JTL bestätigt die erfolgreiche Aktualisierung auf 1.3.6. Der anschließende
  sichere Bildscan im Backend ist erfolgreich; die OPC-Galerie zeigt 37 Bilder.
  Der ergänzende Lesetest auf dem Server (PHP 8.5.3) bestätigt 25 Bilder in
  Unterordnern und keine `.tmb`-Bilder. Alle 714 bisherigen Bilddatensätze sind
  abgesehen von Scan-Zeitstempeln unverändert; insgesamt gibt es danach 744.
  Auch alle elf gespeicherten Plugin-Einstellungen stimmen mit der Sicherung
  vor dem Update überein.
- Campingteile24: 194 Plugin-Dateien, Struktur und Inhalt der vier eigenen
  Plugin-Tabellen sowie neun Plugin-Einstellungen und die Versionsmetadaten
  wurden verschlüsselt gesichert und durch Zurücklesen geprüft.
- Campingteile24: Dasselbe geprüfte ZIP wurde nach erfolgreichem Dev-Test
  installiert. JTL bestätigt Version 1.3.6 und den erfolgreichen Bildscan.
  Die OPC-Galerie zeigt jetzt **371 statt 2 Bilder**. Diese Anzahl entspricht
  dem unabhängig gelesenen Bestand an Originalbildern außerhalb des Caches.
  Auch die drei zuvor vermissten Bilder für Campingbeleuchtung, Campingmöbel
  und Druckwasserpumpen unter `opc/banner/2026/` sind vorhanden.
- Campingteile24: Status, Position und Design aller 825 zuvor vorhandenen
  Bilddatensätze sowie sämtliche neun gespeicherten Plugin-Einstellungen
  stimmen vor und nach Update und Scan überein. Es wurden keine Originalbilder
  geändert oder gelöscht und keine Einstufungen zu Testzwecken gesetzt.
- Onvis-Live bleibt unverändert auf Version 1.2.0. Es wurde weder ein GitHub-Push
  noch ein GitHub-Release für 1.3.6 durchgeführt.

Die Tests mit künstlichem Cache scheiterten vor der Korrektur und bestanden
danach. Die Abnahme umfasst die Aktualisierung, den echten OPC-Bildscan,
die filterbare Galerie und den Erhalt bestehender Kennzeichnungen. Sie ist
keine erneute vollständige Prüfung aller Plugin-Funktionen, Endgeräte oder
Shop-Konfigurationen. Ein Lauf unter PHP 8.1 wurde nicht durchgeführt.
