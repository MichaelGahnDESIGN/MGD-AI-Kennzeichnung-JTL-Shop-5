# Release 1.3.1

> **Historischer Hinweis:** Im frühen JTL-Update-Lifecycle konnte die hier
> verwendete allgemeine Smarty-Instanz den Frontend- statt den
> Backend-Compile-Ordner ansprechen. Verwenden Sie deshalb Version 1.3.3 oder
> neuer. Die Nachfolgeversion nutzt die tatsächlich aktive Smarty-Engine.

Version 1.3.1 korrigiert die Aktualisierung der Plugin-Oberfläche in
JTL-Shop. Nach einem erfolgreichen Update konnte JTL weiterhin eine bereits
kompilierte Vorlage der Vorversion anzeigen. Dadurch waren die neuen, großen
Sprachkarten und die lokale Formatierungsleiste der AI-Philosophie zwar im
Pluginpaket vorhanden, aber im Backend noch nicht sichtbar.

## Ursache

Das Installationspaket verwendet absichtlich feste Datei-Zeitstempel, damit
zwei Builds exakt denselben Binärinhalt erzeugen. Eine zuvor von JTL
kompilierte Smarty-Vorlage konnte dadurch einen neueren Zeitstempel besitzen
als die aktualisierte Quelldatei. JTL hielt den alten Cache dann fälschlich für
aktuell.

## Korrektur

Der offizielle JTL-Update-Lifecycle verwirft nun nach dem Update gezielt alle
kompilierten `.tpl`-Vorlagen innerhalb des eigenen Pluginverzeichnisses. Beim
nächsten Öffnen erzeugt JTL diese Vorlagen aus der gerade installierten Version
neu.

- keine Löschung fremder Shop- oder Template-Caches;
- keine Änderung an Bildern, Kennzeichnungen oder Philosophie-Inhalten;
- keine Datenbankmigration;
- keine externe Verbindung und keine Telemetrie;
- weiterhin reproduzierbares Installationspaket.

## Prüfung auf Dev

Vor einer Live-Aktualisierung muss Version 1.3.1 zuerst auf einer getrennten
Dev- oder Staging-Installation hochgeladen und über den JTL-Plugin-Manager
aktualisiert werden. Danach sind mindestens zu prüfen:

1. Plugin-Manager zeigt Version 1.3.1 als aktiviert;
2. AI-Philosophie zeigt Deutsch und Englisch untereinander;
3. beide Sprachkarten besitzen die lokale Werkzeugleiste sowie die Modi
   **Visuell** und **HTML**;
4. Bildverwaltung und Darstellung laden ohne Browserfehler;
5. vorhandene Bildkennzeichnungen und Texte bleiben erhalten.

## Rückfall

Bei einem Fehler das Plugin zunächst deaktivieren und das vor dem Update
gesicherte Pluginverzeichnis wiederherstellen. Die Korrektur verändert keine
Plugin-Daten und erfordert keine Deinstallation mit Datenlöschung. Live darf
erst nach erfolgreicher Dev-Abnahme aktualisiert werden.
