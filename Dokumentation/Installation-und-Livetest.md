# Installation, Livetest und Rollback

## 1. Pflichtbackup und Wartungsfenster

Vor jeder Installation ist ein **Pflichtbackup** erforderlich:

1. vollständiger Export der JTL-Shop-Datenbank;
2. Sicherung des gesamten Shop-Dateisystems, mindestens von `plugins/` und der aktiven Templates;
3. Prüfung, dass beide Sicherungen lesbar und zeitlich eindeutig zugeordnet sind;
4. dokumentiertes Wartungsfenster mit einer verantwortlichen Person und genügend Zeit für den Rollback.

Die Installation darf nicht unmittelbar vor einer Kampagne, einem Import oder einem Shop-Update erfolgen.

## 2. Technischer Preflight

- JTL-Shop-Version im Backend prüfen: mindestens 5.7.2;
- PHP-Version prüfen: mindestens 8.1;
- aktives Template und Child-Theme notieren; Ziel ist NOVA beziehungsweise OnvisTheme auf NOVA 1.7.1;
- freien Datenbankspeicher, HTTPS und fehlerfreie Shop-Startseite prüfen;
- SHA-256 des ZIP mit dem freigegebenen Wert vergleichen.

## 3. Zuerst auf dev.onvis-shop.de testen

Version 1.3.2 wird **vor** jeder Änderung an `onvis-shop.de` vollständig auf `dev.onvis-shop.de` geprüft. Der Dev-Shop muss im Wartungsmodus bleiben, eine eigene Datenbank verwenden und darf keine aktive Wawi-Anbindung besitzen.

Vor dem Dev-Update werden das vorhandene Pluginverzeichnis und die vier Plugin-Datenbanktabellen datiert gesichert. Anschließend wird exakt das später für Live vorgesehene ZIP verwendet. Galerie, Speichern, Stapelbearbeitung, OPC, Dateimanager-Fallback und Frontend-Ausgabe müssen fehlerfrei sein. Bei einem Fehler endet die Freigabe; Live bleibt unverändert.

## 4. Installation oder Update im Plugin-Manager

1. Im JTL-Backend **Plugins → Plugin-Manager → Upload** öffnen.
2. Das Releasepaket `MGD_AI_Kennzeichnung-1.3.2.zip` als **manuellen ZIP-Upload** auswählen. Nicht den automatisch erzeugten GitHub-Quellcode verwenden.
3. Bei einer bestehenden Version die von JTL angebotene Updatefunktion verwenden; sonst nach erfolgreicher Validierung installieren und aktivieren.
4. Keine Dateien in JTL-Core, NOVA oder OnvisTheme manuell ersetzen.
5. Die bei Neuinstallationen standardmäßig aktivierten Updatehinweise bewusst anhand der eigenen Datenschutz- und Netzwerkvorgaben prüfen; die Footer-Nennung bleibt freiwillig.

Version 1.3.2 besitzt keinen Auto-Updater. Das öffentliche Repository stellt
Release-Hinweise bereit; eine Aktualisierung erfolgt trotzdem immer
kontrolliert über das geprüfte ZIP im Plugin-Manager.

## 5. Kontrollierter Livetest auf https://onvis-shop.de

1. Erst nach dokumentierter Dev-Abnahme ein neues Live-Backup von Dateien und Plugin-Tabellen anlegen.
2. SHA-256 des auf Dev getesteten ZIP vergleichen und exakt dasselbe Paket verwenden.
3. Startseite, Kategorie, Produktdetail, Herstellerseite und eine OPC-Seite als Gast öffnen.
4. Browser-Konsole und Server-Fehlerprotokoll auf neue Fehler prüfen; keine personenbezogenen Daten in Tickets kopieren.
5. Im Backend die Bildgalerie lesend öffnen und Filter sowie Vorschauen prüfen.
6. Den Darstellungstab öffnen, Live-Vorschau, Transparenz und **Nur Vorschau** für Position und Farbschema prüfen.
7. AI-Philosophie in beiden Sprachen öffnen, visuellen und HTML-Modus prüfen und nur dokumentierte Testinhalte speichern.
8. Ein unkritisches Testbild kennzeichnen, die Frontend-Ausgabe prüfen und anschließend auf den dokumentierten Ausgangswert zurücksetzen.
9. OPC- und Dateimanager-Schaltflächen nur auf Vorhandensein und Öffnen prüfen; keine produktive Seite veröffentlichen.
10. Warenkorb, Suche, Login und Checkout höchstens bis vor den verbindlichen Bestellbutton prüfen.

## 6. Deaktivierung und Rollback

Bei Fehlern das Plugin zuerst im Plugin-Manager deaktivieren und das vor dem Update gesicherte Pluginverzeichnis wiederherstellen. Plugin-Datenbanktabellen bleiben beim normalen Rollback erhalten. Danach Plugin- und Template-Caches leeren und den alten Stand prüfen. Für den historischen Rückfall von 1.1.0 steht zusätzlich [Rollback 1.1.0](Rollback-1.1.0.md) zur Verfügung.

Eine Deinstallation mit Datenlöschung ist kein Rollback. Sie ist für diesen Ablauf ausdrücklich ausgeschlossen.
