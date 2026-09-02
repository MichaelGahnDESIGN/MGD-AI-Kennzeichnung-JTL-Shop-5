# Release 1.3.7 – OPC-Unterordner und verlässlicher Design-Editor

## Was sich für Sie verbessert

Version 1.3.7 vereint die seit 1.3.4 entwickelten Korrekturen:

- Der sichere Bildscan findet unterstützte Rasterbilder im lokalen
  OPC-Uploadspeicher auch in Unterordnern und tieferen Verzeichnissen.
  Interne `.tmb`-Vorschaubildordner bleiben bewusst ausgeschlossen.
- Bestehende Kennzeichnungen und Galerie-Filter bleiben erhalten. Starten
  Sie nach dem Update einen neuen Scan und wählen Sie **Alle Status** und
  **OnPage Composer**, um sämtliche erfassten OPC-Bilder zu sehen.
- Design-Einstellungen werden an die richtige Plugin-Tab-Adresse gesendet.
  Veraltete Galerieparameter führen nicht mehr zur weißen Adminseite.
- Ein lokales Schachbrettmuster hinter dem Vorschaulabel macht Transparenz
  und Hintergrundunschärfe sichtbar. Der Schuh bleibt unverändert; die
  Vorschau funktioniert in allen vier Ecken und auf schmalen Bildschirmen.
- Überschrift und Erklärung bleiben auch im dunklen JTL-Backend lesbar.

## Paket und Datenschutz

Verwenden Sie `MGD_AI_Kennzeichnung-1.3.7.zip`, nicht **Source code (zip)**.
Die SHA-256-Datei gehört zum selben Release. Fertige lokale Pakete werden
im Hauptprojekt im Ordner `plugin/` abgelegt. Das Paket enthält ausschließlich
Plugin-Dateien, keine Tests, Zugangsdaten, Backups oder Entwicklungswerkzeuge.

Keine neue Datenbankmigration, keine neuen Drittanbieter-Ressourcen und
keine zusätzlichen Dienste. Bildklassifizierungen, bildbezogene Positionen,
Farbschemata und vorhandene Designeinstellungen werden nicht zurückgesetzt.
Der optionale, bereits vorhandene GitHub-Updatehinweis bleibt unverändert;
das Plugin installiert keine Updates automatisch.

## Prüfung und Auslieferung

Der rekursive Scan wurde im vorherigen Testpaket auf Dev und Campingteile24
bestätigt. Der Speicher-Hotfix wurde dort ebenfalls bereits geprüft. Die
Muster-Vorschau wurde lokal im Browser einschließlich 360-Pixel-Ansicht geprüft.
Diese früheren Abnahmen ersetzen keine Kontrolle nach dem aktuellen Update.

Am 3. September erneut lokal bestanden: 565 PHP-Tests mit 14.904 Prüfungen,
142 JavaScript-Tests, PHPStan ohne Fehler, Formatprüfung ohne Änderungen und
Composer-Validierung. Die PHP-Prüfung lief unter PHP 8.5.6; ein zusätzlicher
Lauf unter PHP 8.1 wurde nicht ausgeführt. Die Pakettests prüfen den ZIP-Inhalt
und mehrfach identische Builds einschließlich unterschiedlicher Zeitzonen.

SHA-256 des Installationspakets:
`c5bb5a9e5fc367609dc030ae6f68951829b5bf047aca7cc9241241e5830eb4be`

GitHub Actions startet bei Push oder Pull Request nicht automatisch. Ein
manueller Cloud-Test wird ohne gesonderte Kostenprüfung nicht angestoßen.

**Installationsstatus:** Das Paketupdate auf den drei Zielshops ist noch nicht
bestätigt. Vorabprüfung im JTL-Plugin-Manager am 3. September: Dev 1.3.6,
Campingteile24 1.3.6, Onvis live 1.2.0. Vollständige Datei- und
Datenbanksicherungen müssen vor dem jeweiligen Update nachgewiesen sein.

## Sicher aktualisieren und zurückkehren

1. Vollständige Shop- und Datenbanksicherung auf Lesbarkeit prüfen, zusätzlich
   die Plugin-Dateien und eigenen Plugin-Daten separat sichern.
2. Dasselbe geprüfte ZIP zuerst auf Dev über den JTL-Plugin-Manager hochladen
   und **Aktualisieren** wählen. Nicht deinstallieren.
3. Aktivstatus und Version, Galerie/OPC-Filter, Philosophie-Editor und
   Design-Speichern aus einer zuvor gefilterten Galerie prüfen.
4. Vorschau und vorhandene Labels auf der Shopseite kontrollieren. Keine
   Bestellungen, Zahlungen oder OPC-Veröffentlichungen sind dafür nötig.
5. Erst nach Dev-Abnahme das unveränderte Paket im vereinbarten Wartungsfenster
   auf die Live-Shops übertragen und dieselben Kontrollen wiederholen.

Bei einem Fehler keine weiteren Einstellungen ändern. Vorherige Plugin-Dateien
und die dazugehörigen Plugin-Daten/JTL-Metadaten gezielt aus der Sicherung
wiederherstellen. Eine vollständige Live-Datenbank nicht unbesehen zurückspielen:
Inzwischen eingegangene Bestellungen dürfen nicht verloren gehen. Die genaue
Rückfallentscheidung trifft die zuständige Shopbetreuung.
