# Sicherheit

## Sicherheitsmeldungen

Bitte mögliche Sicherheitslücken nicht als öffentliches Issue veröffentlichen. Die privaten Kontaktmöglichkeiten auf [Michael-Gahn.de](https://Michael-Gahn.de) sind für betroffene Version, Auswirkung und reproduzierbare Prüfschritte vorgesehen. Keine echten Zugangsdaten, Tokens, Kundendaten, lokalen Serverpfade oder vollständigen Logs mitsenden.

## Unterstützter Stand

Sicherheitskorrekturen werden für die aktuelle Version 1.2.1 bewertet. Voraussetzung für den produktiven Betrieb sind JTL-Shop ab 5.7.2, PHP ab 8.1, HTTPS, aktuelle Sicherheitsupdates und die in der Installationsanleitung genannten Backups.

## Schutzgrenzen

Das Plugin erkennt KI-Inhalte nicht automatisch und verarbeitet keine Zahlungs- oder Bestelldaten. Bei Neuinstallationen sind Updatehinweise standardmäßig aktiviert und können jederzeit ausgeschaltet werden. Eine Prüfung sendet keine Bilder, Tokens, Shop-, Kunden- oder Formulardaten; GitHub kann technisch Server-IP, Zeitpunkt und den festen User-Agent erhalten. Positive und negative Ergebnisse werden zwölf Stunden lokal zwischengespeichert. Das Plugin installiert keine Updates automatisch. Eine Deinstallation mit Datenlöschung prüft Eigentümermarker und Schema; diese Schutzprüfung darf nicht umgangen werden.

## Sicher aktualisieren

Das Repository ist privat. Daher kann die anonyme GitHub-Prüfung trotz neuer
Version ohne Hinweis bleiben. Nutzen Sie ausschließlich das signierte oder per
SHA-256 geprüfte Release-ZIP und aktualisieren Sie das Plugin als manueller
ZIP-Upload im JTL-Plugin-Manager. Vor jedem Update sind ein vollständiges Backup,
ein Dev-Test und ein dokumentierter Rollback erforderlich.
