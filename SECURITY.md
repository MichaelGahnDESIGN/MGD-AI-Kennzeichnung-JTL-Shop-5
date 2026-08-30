# Sicherheit

## Sicherheitsmeldungen

Bitte mögliche Sicherheitslücken nicht als öffentliches Issue veröffentlichen. Die privaten Kontaktmöglichkeiten auf [Michael-Gahn.de](https://Michael-Gahn.de) sind für betroffene Version, Auswirkung und reproduzierbare Prüfschritte vorgesehen. Keine echten Zugangsdaten, Tokens, Kundendaten, lokalen Serverpfade oder vollständigen Logs mitsenden.

## Unterstützter Stand

Sicherheitskorrekturen werden für die aktuelle Version 1.3.0 bewertet. Voraussetzung für den produktiven Betrieb sind JTL-Shop ab 5.7.2, PHP ab 8.1, HTTPS, aktuelle Sicherheitsupdates und die in der Installationsanleitung genannten Backups.

## Schutzgrenzen

Das Plugin erkennt KI-Inhalte nicht automatisch und verarbeitet keine Zahlungs- oder Bestelldaten. Bei Neuinstallationen sind Updatehinweise standardmäßig aktiviert und können jederzeit ausgeschaltet werden. Eine Prüfung sendet keine Bilder, Tokens, Shop-, Kunden- oder Formulardaten; GitHub kann technisch Server-IP, Zeitpunkt und den festen User-Agent erhalten. Positive und negative Ergebnisse werden zwölf Stunden lokal zwischengespeichert. Das Plugin installiert keine Updates automatisch. Eine Deinstallation mit Datenlöschung prüft Eigentümermarker und Schema; diese Schutzprüfung darf nicht umgangen werden.

## Lokaler Philosophie-Editor

Der Editor für die AI-Philosophie lädt **keine externen** Bibliotheken,
Drittinhalte, Fonts, Icons, Styles, Scripts oder CDN-Ressourcen. Er enthält
**keine Telemetrie**, schreibt keine Inhalte in Browser-Speicher und sendet
keine Texte an einen externen Dienst.

Erlaubt sind nur `p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em` und `a`.
Links müssen sich als sichere HTTPS-URL ohne Zugangsdaten und fremden Port
prüfen lassen. Aktive Inhalte und unbekannte Attribute werden entfernt. Die
clientseitige Prüfung verbessert die Rückmeldung; der PHP-Sanitizer prüft beim
Speichern erneut und bleibt die maßgebliche Sicherheitsgrenze. Falls JavaScript
ausfällt oder deaktiviert ist, bleiben die großen Textfelder als
**No-JavaScript-Fallback** vollständig nutzbar.

## Sicher aktualisieren

Nutzen Sie aus dem öffentlichen Repository ausschließlich das ausdrücklich
angehängte, per SHA-256 geprüfte Release-ZIP und aktualisieren Sie das Plugin
als manueller ZIP-Upload im JTL-Plugin-Manager. Die automatischen GitHub-
Quellcodearchive sind keine installierbaren JTL-Pakete. Vor jedem Update sind
ein vollständiges Backup, ein Dev-Test und ein dokumentierter Rollback
erforderlich.
