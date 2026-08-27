# Installation und Update

## Systemvoraussetzungen

- JTL-Shop 5.7.2 oder neuer;
- PHP 8.1 oder neuer;
- HTTPS;
- ein berechtigter JTL-Administrator;
- reguläre Schreibrechte für Plugin- und Cacheverzeichnisse;
- empfohlen: NOVA oder ein sauber abgeleitetes NOVA-Child-Theme.

Version 1.1.1 wurde mit NOVA und einem NOVA-basierten OnvisTheme geprüft. Stark veränderte Templates müssen in einer eigenen Testumgebung abgenommen werden.

## Das richtige ZIP verwenden

Verwenden Sie aus dem GitHub-Release ausschließlich:

`MGD_AI_Kennzeichnung-1.1.1.zip`

Die automatisch angebotenen GitHub-Dateien **Source code (zip)** und **Source code (tar.gz)** sind keine installierbaren JTL-Pakete.

Vergleichen Sie den SHA-256-Wert des Downloads mit dem Wert in den Release-Hinweisen.

## Pflichtbackup

Vor Installation oder Update benötigen Sie:

1. vollständige Datenbanksicherung;
2. Sicherung des Shop-Dateisystems;
3. mindestens eine getrennte Sicherung des vorhandenen Pluginverzeichnisses;
4. dokumentierten Zeitpunkt und verantwortliche Person;
5. ausreichend Zeit für Prüfung und Rückfall.

Prüfen Sie nicht nur, ob eine Backupdatei existiert, sondern ob sie lesbar und einem eindeutigen Shopstand zugeordnet ist.

## Neuinstallation

1. JTL-Backend öffnen.
2. **Plugins → Plugin-Manager → Upload** wählen.
3. Release-ZIP auswählen.
4. JTLs Paketprüfung abwarten.
5. Plugin installieren.
6. Plugin aktivieren.
7. **Bildverwaltung** öffnen und den ersten Scan starten.
8. Einstellungen prüfen, Updatehinweise zunächst deaktiviert lassen.
9. ein unkritisches Bild testweise kennzeichnen.
10. Frontend und Serverprotokoll kontrollieren.

## Update einer bestehenden Version

1. Vorhandene Version und aktiven Status notieren.
2. Pluginverzeichnis und eigene Plugin-Tabellen zusätzlich sichern.
3. Neues Release-ZIP im Plugin-Manager hochladen.
4. JTLs Updatefunktion verwenden.
5. Ergebnis und angezeigte Versionsnummer kontrollieren.
6. Shop-, Plugin- und Template-Cache leeren.
7. Galerie, Einzelbearbeitung, OPC und mindestens eine sichtbare Kennzeichnung prüfen.

Vorhandene Kennzeichnungsentscheidungen sollen bei einem normalen Update erhalten bleiben. Ein Update verändert keine Originalbilder.

## Empfohlene Testreihenfolge

1. getrennte Dev- oder Staging-Installation;
2. gleiche JTL-, PHP- und Templateversion wie Live;
3. eigenes Backup der Testinstallation;
4. exakt das später vorgesehene Release-ZIP;
5. Backend-Prüfung;
6. Frontend-Prüfung auf Desktop und Mobil;
7. erst danach ein neues Live-Backup und die produktive Freigabe.

Zwischen Dev-Abnahme und Live-Installation darf kein neues Paket gebaut werden. Verwenden Sie exakt denselben geprüften Hash.

## Nach der Installation prüfen

- Plugin-Version und Aktivstatus;
- Bildgalerie und Vorschaubilder;
- Anzahl gefundener Bilder und Fundstellen;
- Speichern eines unkritischen Testbildes;
- sichtbare Position und Darstellung;
- responsive Bilder und verlinkte Bilder;
- OPC-Hintergrundbilder;
- Shopnavigation, Suche und Warenkorb;
- Browserkonsole und neue Serverfehler.

## Deinstallation

Eine Deinstallation ist nicht dasselbe wie ein Rollback. Wenn JTL nach dem Löschen der Plugin-Daten fragt, lesen Sie die Auswahl sorgfältig:

- **Daten behalten:** Kennzeichnungen können bei einer späteren Neuinstallation wieder zur Verfügung stehen.
- **Daten löschen:** ausschließlich für einen bewusst vollständigen Rückbau; vorher separates Backup anlegen.

Der Schutzmechanismus löscht keine fremden oder strukturell unerwarteten Tabellen.
