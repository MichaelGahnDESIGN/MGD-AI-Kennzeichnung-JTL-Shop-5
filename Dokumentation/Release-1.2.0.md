# Release 1.2.0

## Zusammenfassung

Version 1.2.0 ergänzt den rein lesenden Backend-Tab **Impressum**. Berechtigte
JTL-Administratoren finden dort die freigegebenen geschäftlichen Angaben von
Michael Gahn DESIGN. Der Tab ersetzt nicht das öffentliche Impressum und nimmt
keine Änderung an öffentlichen Shopseiten vor.

## Sicherheit und Datenschutz

- nur im Administrationsbereich erreichbar;
- offizielle JTL-Pluginberechtigung erforderlich;
- ausschließlich lesender GET-Aufruf;
- keine Datenbank, kein Formular und keine externe Anfrage;
- keine Kunden-, Bestell-, Zahlungs- oder Administratordaten;
- keine Änderung vorhandener Bilder oder Kennzeichnungsentscheidungen.

## Installationspaket

Dateiname: `MGD_AI_Kennzeichnung-1.2.0.zip`

Das Paket wird reproduzierbar mit `scripts/build-release.sh` erstellt. Vor der
Installation sind ZIP-Prüfung und SHA-256-Vergleich erforderlich. Die
Aktualisierung wird zuerst auf `dev.onvis-shop.de` getestet; `onvis-shop.de`
bleibt bis zu einer getrennten Live-Freigabe unverändert.

## Prüfung nach dem Update

1. Plugin-Version 1.2.0 und Aktivstatus kontrollieren.
2. Tab **Impressum** im Plugin-Menü öffnen.
3. Adresse, neue Telefonnummer und E-Mail-Adresse prüfen.
4. Telefon- und E-Mail-Link kontrollieren.
5. Bildverwaltung und eine vorhandene Frontend-Kennzeichnung gegenprüfen.
6. neue Serverfehler ausschließen.

