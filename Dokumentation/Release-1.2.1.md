# Release 1.2.1

Version 1.2.1 verbessert die Bedienung der Darstellung und bereitet einen
kontrollierten, manuellen Updateweg vor.

## Neu und geändert

- eigener zweispaltiger Darstellungstab mit lokaler Live-Vorschau;
- gekoppelte Eingabefelder und Schieberegler für Eckenradius,
  Hintergrundunschärfe und Transparenz;
- globale Transparenz von 0 % (deckend) bis 90 % (nahezu durchsichtig);
- Position und Farbschema in diesem Tab ausdrücklich **Nur Vorschau**;
- lokale Produktgrafik „Michael Gahn DESIGN Schuh“, ohne externe Bildquelle;
- optionale Herstellernennung **supported by: Michael Gahn DESIGN** mit sicherem
  Link in einem neuen Tab;
- Updatehinweise bei Neuinstallationen standardmäßig eingeschaltet;
- positive und erfolglose GitHub-Prüfungen werden zwölf Stunden lokal
  zwischengespeichert, damit Fehler oder ein privates Repository keine
  wiederholten Anfragen auslösen;
- Dokumentation, Tests, Buildskript und CI auf Version 1.2.1 vereinheitlicht.

## Datenschutz der Updateprüfung

Nur beim Öffnen des adressierten Darstellungstabs und nur bei aktivierter
Einstellung fragt der Shop den festen Endpunkt `api.github.com` ab. GitHub kann
dabei technisch die **Server-IP**, den Zeitpunkt und den **User-Agent**
`MGD-AI-Kennzeichnung-JTL-Shop-5/1.2.1` erhalten. Bilder, Shopinhalte,
Formulardaten, Kundendaten, Tokens und Zugangsdaten werden nicht übertragen.

Das Repository ist derzeit ein **privates Repository**. Eine anonyme Abfrage
liefert daher üblicherweise keine Release-Information. Dieses Ergebnis wird als
Negativcache ebenfalls zwölf Stunden gespeichert. Das Plugin installiert keine
Updates automatisch.

## Installation und Update

Das offizielle Artefakt heißt `MGD_AI_Kennzeichnung-1.2.1.zip`. Verwenden Sie
den im Release angehängten, per SHA-256 geprüften Download und nicht den von
GitHub automatisch erzeugten Quellcode. Version 1.2.1 besitzt keinen
Auto-Updater; erforderlich ist ein **manueller ZIP-Upload** im
JTL-Plugin-Manager.

Vor dem Update:

1. Datenbank und Shop-Dateien vollständig sichern;
2. bisheriges Pluginverzeichnis getrennt sichern;
3. Paket-Hash prüfen;
4. exakt dieses ZIP zuerst in einer getrennten Dev-Umgebung testen;
5. Galerie, Darstellung, Speichern, OPC und Frontend prüfen;
6. erst danach ein neues Live-Backup anlegen und dasselbe Paket verwenden.

## Rollback

Bei einem Fehler zuerst das Plugin deaktivieren und die Caches leeren. Falls das
nicht genügt, das gesicherte Pluginverzeichnis wiederherstellen. Eigene
Plugin-Tabellen nur dann zurückspielen, wenn dies tatsächlich notwendig ist.
Eine Deinstallation mit Datenlöschung ist kein normaler Rollback.
