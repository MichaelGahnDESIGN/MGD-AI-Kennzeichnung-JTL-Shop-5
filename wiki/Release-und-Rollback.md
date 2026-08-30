# Release und Rollback

## Release-Paket prüfen

Ein offizieller Release enthält:

- Versionshinweise;
- installierbares ZIP `MGD_AI_Kennzeichnung-<Version>.zip`;
- SHA-256-Wert des Pakets;
- automatisch erzeugten Quellcode-Download von GitHub.

Für JTL verwenden Sie ausschließlich das explizit angehängte Plugin-ZIP.

## Sicherer Freigabeprozess

1. vollständiges Dev-Backup;
2. Paket-Hash prüfen;
3. Update auf Dev installieren;
4. Pluginaktivstatus und Version prüfen;
5. Galerie, Scan und Speichern testen;
6. OPC und Dateimanager-Fallback prüfen;
7. sichtbare Labels auf Desktop und Mobil kontrollieren;
8. Fehlerprotokolle prüfen;
9. erst danach neues Live-Backup;
10. exakt dasselbe Paket auf Live verwenden.

Das Plugin besitzt keinen Auto-Updater. Das geprüfte Paket wird als
**manueller ZIP-Upload** im JTL-Plugin-Manager eingespielt. Verwenden Sie aus
dem öffentlichen Repository das ausdrücklich angehängte Release-ZIP und nicht
die automatischen GitHub-Quellcodearchive.

## Release 1.3.0

Version 1.3.0 ergänzt den vollständig lokalen Philosophie-Editor. Deutsch und
Englisch stehen untereinander und lassen sich visuell oder optional als
bereinigtes HTML bearbeiten. Beide Sprachfassungen werden gemeinsam
gespeichert. Externe Editorbibliotheken, Drittinhalte und Telemetrie werden
nicht geladen; die großen Textfelder bleiben ohne JavaScript nutzbar.

## Release 1.2.1

Version 1.2.1 ergänzt den zweispaltigen Darstellungstab mit lokaler
Live-Vorschau, globaler Transparenz und gekoppelten Reglern. Position und
Farbschema sind dort **Nur Vorschau** und bleiben bildbezogen. Die optionale
Footer-Nennung lautet **supported by: Michael Gahn DESIGN**.

Updateprüfungen übertragen keine Bilder oder Tokens. GitHub kann technisch
Server-IP, Zeitpunkt und User-Agent erhalten. Positive und negative Ergebnisse
werden zwölf Stunden lokal gespeichert.

## Release 1.2.0

Version 1.2.0 ergänzt einen geschützten, rein lesenden Impressum-Tab. Er zeigt
die freigegebenen Herstellerangaben, verwendet keine Datenbank und verändert
weder das öffentliche Shop-Impressum noch vorhandene Plugin-Daten.

## Release 1.1.1

Version 1.1.1 verbessert die Frontend-Ausgabe für:

- normale Bilder;
- verlinkte Bilder;
- responsive `picture`-Ausgaben;
- statische OPC-Hintergründe;
- verzögert geladene Hintergründe über `data-image-src`.

Originalbilder, Linkziele, Status, Position und Darstellung werden nicht verändert.

## Wann sollte zurückgerollt werden?

- neuer Shopfehler;
- Backend nicht mehr erreichbar;
- Kennzeichnung bricht Layout oder Navigation;
- Speichern erzeugt inkonsistente Daten;
- unerwartete neue Fehler im Serverprotokoll;
- nicht erklärbare Abweichung zwischen Dev und Live.

## Schnellster sicherer Rückfall

1. Plugin im JTL-Plugin-Manager deaktivieren.
2. Shop-, Plugin- und Template-Cache leeren.
3. Frontend prüfen.

Da das Plugin fehlertolerant ausgelegt ist, genügt die Deaktivierung häufig als sofortige Stabilisierung.

## Vollständiger Versions-Rollback

1. Plugin deaktivieren.
2. aktuelles fehlerhaftes Pluginverzeichnis separat sichern.
3. vorheriges geprüftes Pluginverzeichnis wiederherstellen.
4. Datenbank nur zurückspielen, wenn die Version tatsächlich eine inkompatible Datenänderung verursacht hat.
5. Cache leeren.
6. alte Version aktivieren.
7. Galerie und Frontend prüfen.

Nach dem Rollback zusätzlich den Darstellungstab und mindestens eine sichtbare
Kennzeichnung sowie beide Sprachfassungen der AI-Philosophie prüfen. Ein Cache
kann sonst noch CSS oder Einstellungen der neueren Version zeigen.

## Deinstallation ist kein Rollback

Eine Deinstallation kann – abhängig von der in JTL gewählten Option – Plugin-Daten löschen. Für einen normalen Rückfall ist das nicht erforderlich. Behalten Sie die Daten, wenn Sie lediglich zur vorherigen Pluginversion zurückkehren möchten.

## Fehler dokumentieren

Notieren Sie:

- JTL-, PHP-, Template- und Pluginversion;
- Zeitpunkt;
- ausgeführte Aktion;
- sichtbare Fehlermeldung;
- bereinigten technischen Fehlercode;
- ob Deaktivierung den Fehler beseitigt;
- verwendeten Paket-Hash.

Keine Tokens, Passwörter, Kundendaten oder vollständigen Logs in GitHub veröffentlichen.
