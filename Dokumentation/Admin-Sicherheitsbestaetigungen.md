# Lebenszyklus der Admin-Sicherheitsbestätigungen

## Zweck und Datenminimierung

Änderungen an mehreren Bildern und die Bereinigung veralteter Fundstellen müssen zuerst in einer Vorschau bestätigt werden. Der Browser erhält dafür ein zufälliges Einmaltoken. Die vollständige geplante Änderung bleibt höchstens **10 Minuten** im serverseitigen JTL-Sitzungsspeicher und wird nicht als verstecktes Formularfeld an den Browser ausgelagert.

Die Tabelle `xplugin_mgd_ai_confirmation_claim` verhindert eine doppelte Ausführung bei parallelen Anfragen oder veralteten Sitzungskopien. Sie speichert ausschließlich:

- einen nicht umkehrbaren SHA-256-Hash des zufälligen Einmaltokens,
- dessen Ablaufzeit,
- den Zeitpunkt der erfolgreichen Beanspruchung.

Sie enthält keine Bild-IDs, Einstellungswerte, Sitzungsschlüssel, Benutzerkennungen, E-Mail-Adressen oder sonstige Vorgangsdaten. Dadurch kann die Tabelle nicht zur Zuordnung einzelner Administratoren verwendet werden.

## Replay-Sperrfrist und automatische Löschung

Ein abgelaufener Claim wird nicht sofort entfernt. Er bleibt **mindestens einen vollständigen Tag** nach seinem Ablauf als Replay-Sperre erhalten. Diese zusätzliche Frist verhindert, dass eine parallel bereits gelesene Sitzungskopie denselben Claim unmittelbar nach dessen Ablauf erneut einfügen kann.

Die Bereinigung erfolgt opportunistisch vor dem nächsten bestätigten Adminvorgang: Dann wird genau ein begrenzter Batch von höchstens **1.000** Zeilen gelöscht, deren Ablaufzeit seit mehr als einem Tag überschritten ist. Es gibt keine unbeschränkte Schleife innerhalb eines Admin-Aufrufs. Schlägt diese Bereinigung fehl, wird auch der neue Claim nicht geschrieben; die Mutation bleibt damit sicher gesperrt.

Ohne einen späteren bestätigten Adminvorgang gibt es daher **keine garantierte Höchstdauer** der Speicherung. Ältere Hashzeilen bleiben bis zu einem zukünftigen Claim oder bis zur Deinstallation bestehen. Das verbleibende Datenschutzrisiko ist durch die strikte Datenminimierung begrenzt: Gespeichert werden nur der Hash eines zufälligen Einmaltokens und zwei technische Zeitwerte, niemals Vorgangsdaten oder personenbezogene Zuordnungsmerkmale.

Ob ein neuer Claim noch gültig ist, entscheidet abschließend die UTC-Uhr der Datenbank innerhalb desselben `INSERT`-Befehls. Die vorherige PHP-Prüfung dient nur einer verständlichen, schnellen Rückmeldung. Eine abweichende oder während des Requests fortschreitende PHP-Uhr kann daher keinen abgelaufenen Claim freigeben.

## Deinstallation und Rückbau

Bei einer Deinstallation beziehungsweise einem Migrations-Rückbau darf die flüchtige Claim-Tabelle vollständig entfernt werden. Vor dem Löschen prüft die Migration den festen Eigentumsmarker und den vollständigen erwarteten Schemaaufbau. Eine fremde oder veränderte gleichnamige Tabelle wird nicht gelöscht; der Rückbau bricht in diesem Fall kontrolliert ab.
