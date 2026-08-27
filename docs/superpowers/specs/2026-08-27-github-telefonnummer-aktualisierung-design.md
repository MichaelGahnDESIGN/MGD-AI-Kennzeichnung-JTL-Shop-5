# Entwurf: Telefonnummer in eigenen GitHub-Projekten aktualisieren

## Ziel

Die bisherige geschäftliche Telefonnummer `+49 (0) 176 557 647 48` wird in allen von **MichaelGahnDESIGN** selbst verwalteten GitHub-Repositories durch `+49 (0) 151 59156639` ersetzt.

## Sicherer Geltungsbereich

- Berücksichtigt werden öffentliche und private Repositories des GitHub-Kontos `MichaelGahnDESIGN`.
- Geändert werden nur eindeutige Textvorkommen der alten, persönlichen Geschäftsnummer.
- Telefonnummern von Kunden, Projektpartnern, Beispieldaten oder Drittanbietern bleiben unverändert.
- Binärdateien, generierte Abhängigkeiten, Backups und Git-Historie werden nicht umgeschrieben.
- Es erfolgt kein erzwungener Push und keine Änderung alter Tags oder Releases.

## Vorgehen

1. Alle eigenen Repositories und deren Standardbranch werden über die GitHub-API ermittelt.
2. Root-Dateien wie `IMPRESSUM.md` und `README.md` sowie weitere versionierte Textdateien werden auf das exakte alte Nummernmuster geprüft.
3. Nur tatsächlich betroffene Repositories werden in temporäre, isolierte Arbeitsverzeichnisse geklont.
4. Vor jeder Änderung werden repositoryeigene `AGENTS.md`, `GRUNDREGELN.md` und `PROJEKTREGELN.md` gelesen.
5. Die Telefonnummer wird exakt und ohne weitere redaktionelle Änderungen ersetzt.
6. Pro Repository wird der Diff auf ausschließlich erwartete Textänderungen geprüft.
7. Jedes Repository erhält einen eigenen nachvollziehbaren Commit auf seinem Standardbranch.
8. Nach dem Push wird über GitHub erneut geprüft, dass die alte Nummer in den betroffenen Standardbranches nicht mehr vorkommt.

## Commit-Konvention

Die reine Kontaktdatenänderung verwendet den Commit-Titel:

`docs: aktualisiert geschäftliche Telefonnummer`

Repositories mit eigenen strengeren Konventionen behalten ihre projektspezifischen Vorgaben.

## Datenschutz und Sicherheit

Die neue Nummer wurde vom Inhaber ausdrücklich für die geschäftliche Veröffentlichung freigegeben. Die Änderung darf keine Zugangsdaten, Tokens, Kundendaten oder nicht angeforderte Kontaktdaten in Ausgaben oder Commits aufnehmen.

## Abnahme

Die Aktualisierung ist abgeschlossen, wenn:

1. alle eindeutigen Vorkommen der alten Geschäftsnummer auf den Standardbranches ersetzt sind;
2. keine fremden Telefonnummern verändert wurden;
3. jeder betroffene Diff ausschließlich erwartete Kontaktdatenänderungen enthält;
4. alle erfolgreichen Pushes mit Repository und Commit dokumentiert sind;
5. blockierte oder abweichende Repositories ausdrücklich mit Ursache aufgeführt werden.

