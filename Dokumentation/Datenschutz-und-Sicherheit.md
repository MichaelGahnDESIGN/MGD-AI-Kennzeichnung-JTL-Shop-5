# Datenschutz und Sicherheit

## Datenminimierung

Das Plugin speichert ausschließlich technische lokale Bildschlüssel, lokale Pfade, Fundstellen, Kennzeichnungsstatus, Anzeigeeinstellungen und die redaktionellen Philosophie-Texte. Es benötigt keine Kundenprofile, Bestellungen, Zahlungsdaten, E-Mail-Adressen oder externen KI-Konten.

Es findet keine automatische KI-Erkennung statt. Das Plugin sendet **keine Bilder**, Bildinhalte, Shoptexte oder personenbezogenen Daten an einen KI-Anbieter.

## Netzwerkzugriffe

Updatehinweise sind **standardmäßig deaktiviert**. Erst nach ausdrücklicher Aktivierung ruft das Plugin höchstens alle zwölf Stunden am festen Endpunkt `api.github.com` öffentliche Metadaten des neuesten Releases ab. TLS-Prüfung ist verpflichtend, Weiterleitungen sind gesperrt und die Antwort ist auf 65.536 Byte begrenzt. Das Plugin lädt oder installiert keine Updates selbst.

## Administration

Schreibzugriffe verlangen eine angemeldete, berechtigte JTL-Administration, ein gültiges CSRF-Token und bei Stapel- oder Löschvorgängen eine kurzlebige Einmalbestätigung. Logs enthalten nur feste Ereigniscodes und Mengen, keine Tokens, SQL-Ausnahmen, lokalen Pfade oder eingegebenen Inhalte.

## Ausgabe und Deinstallation

HTML, Klassen und numerische Stile stammen aus Positivlisten. Philosophie-Inhalte erlauben nur wenige semantische Elemente und HTTPS-Links ohne Zugangsdaten. Bei Deinstallation werden fremde oder veränderte Tabellen nie gelöscht.
