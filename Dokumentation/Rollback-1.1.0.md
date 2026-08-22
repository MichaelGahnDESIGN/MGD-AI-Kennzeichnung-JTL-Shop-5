# Sicherer Rollback von Version 1.1.0

Diese Anleitung gilt nur, wenn vor dem Update ein geprüftes Backup des Pluginverzeichnisses und der Plugin-Datenbanktabellen angelegt wurde.

1. **Plugin 1.1.0 deaktivieren.** Dadurch stoppt die Ausgabe, ohne gespeicherte Kennzeichnungen zu löschen.
2. Das aktive Pluginverzeichnis außerhalb des Webroots zusätzlich sichern und eindeutig als fehlerhaften Stand kennzeichnen.
3. Das gesicherte **Pluginverzeichnis 1.0.0** an seinen ursprünglichen Ort zurückspielen. Keine JTL-Core- oder Template-Datei ersetzen.
4. Die **Datenbanktabellen nicht löschen**. Version 1.0.0 kann die vorhandenen Kennzeichnungen weiter lesen; die Sicherung bleibt als zusätzlicher Rückfallpunkt erhalten.
5. JTL-Plugin- und Template-**Caches leeren**, aber keine Kunden-, Bestell-, Session- oder Wawi-Daten entfernen.
6. Version 1.0.0 aktivieren und Startseite, Produktseite, Admin-Bildverwaltung und Fehlerprotokoll prüfen.

Eine Deinstallation mit Datenlöschung ist **kein Rollback** und darf dafür nicht verwendet werden. Wenn der alte Stand nicht fehlerfrei läuft, nichts weiter löschen oder überschreiben, sondern Plugin deaktiviert lassen und die vollständige Datei- und Datenbanksicherung desselben Zeitpunkts kontrolliert wiederherstellen.
