#!/usr/bin/env bash

# Erstellt aus einer festen Positivliste ein reproduzierbares JTL-Installations-ZIP.
set -euo pipefail

projekt_wurzel="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
quellordner="${projekt_wurzel}/plugin/MGD_AI_Kennzeichnung"
ausgabeordner="${projekt_wurzel}/dist"
ausgabedatei="${ausgabeordner}/MGD_AI_Kennzeichnung-1.3.2.zip"
arbeitsordner="$(mktemp -d "${TMPDIR:-/tmp}/mgd-ai-release.XXXXXX")"
paketordner="${arbeitsordner}/MGD_AI_Kennzeichnung"
mkdir -p "${ausgabeordner}"
# Das temporäre Archiv liegt absichtlich im Ausgabeordner. Nur dadurch bleibt
# das abschließende Umbenennen garantiert auf demselben Dateisystem atomar.
temporaerer_ausgabeordner="$(mktemp -d "${ausgabeordner}/.mgd-ai-release.XXXXXX")"
temporaeres_zip="${temporaerer_ausgabeordner}/MGD_AI_Kennzeichnung-1.3.2.zip"
paketmanifest="${temporaerer_ausgabeordner}/paketmanifest.txt"
archivmanifest="${temporaerer_ausgabeordner}/archivmanifest.txt"

# Ausschließlich diese Bestandteile dürfen überhaupt als Paketquelle dienen.
# Neue Top-Level-Pfade müssen dadurch bewusst in einem Code-Review freigegeben
# werden und gelangen nicht versehentlich in ein veröffentlichtes ZIP.
freigegebene_top_level_pfade=(
    "Admin"
    "Bootstrap.php"
    "Domain"
    "Infrastructure"
    "Migrations"
    "Portlets"
    "Presentation"
    "Scanner"
    "Service"
    "Setup"
    "adminmenu"
    "frontend"
    "info.xml"
)

# Innerhalb der freigegebenen Verzeichnisse bleiben nur die tatsächlich vom
# Plugin benötigten Quell-, Template- und Bildformate zulässig.
freigegebene_endungen=("css" "js" "mjs" "php" "png" "svg" "tpl" "xml")

aufraeumen() {
    rm -rf "${arbeitsordner}" "${temporaerer_ausgabeordner}"
}
trap aufraeumen EXIT INT TERM

if [[ ! -f "${quellordner}/info.xml" || ! -f "${quellordner}/Bootstrap.php" ]]; then
    echo "Der freigegebene Plugin-Quellordner ist unvollständig." >&2
    exit 1
fi

if find "${quellordner}" -type l -print -quit | grep -q .; then
    echo "Symlinks sind im Release-Paket nicht erlaubt." >&2
    exit 1
fi

ist_freigegeben() {
    local gesucht="$1"
    shift
    local erlaubt
    for erlaubt in "$@"; do
        if [[ "${gesucht}" == "${erlaubt}" ]]; then
            return 0
        fi
    done

    return 1
}

pruefe_relativpfad() {
    local relativpfad="$1"

    # Versteckte Pfadteile, Steuerzeichen und mehrdeutige Sonderzeichen werden
    # fail-closed abgelehnt. Die aktuellen Plugin-Dateien benötigen nur diese
    # überschaubare, plattformunabhängige Zeichenmenge.
    if [[ "/${relativpfad}" == */.* ]] \
        || ! LC_ALL=C grep -Eq '^[A-Za-z0-9_./-]+$' <<<"${relativpfad}"; then
        echo "Nicht freigegebener Pfad im Plugin-Quellordner: ${relativpfad}" >&2
        return 1
    fi
}

# Auch ein unbekannter Top-Level-Pfad führt zum Abbruch. Ignorieren wäre hier
# gefährlich, weil ein Entwickler sonst annehmen könnte, die Datei sei Teil des
# geprüften Releasepakets.
while IFS= read -r -d '' top_level_pfad; do
    top_level_name="$(basename "${top_level_pfad}")"
    if ! ist_freigegeben "${top_level_name}" "${freigegebene_top_level_pfade[@]}"; then
        echo "Top-Level-Pfad ist für das Release nicht freigegeben: ${top_level_name}" >&2
        exit 1
    fi
done < <(find "${quellordner}" -mindepth 1 -maxdepth 1 -print0)

freigegebene_dateien=()
for freigegebener_pfad in "${freigegebene_top_level_pfade[@]}"; do
    vollstaendiger_pfad="${quellordner}/${freigegebener_pfad}"
    if [[ ! -e "${vollstaendiger_pfad}" ]]; then
        echo "Freigegebener Pflichtpfad fehlt: ${freigegebener_pfad}" >&2
        exit 1
    fi

    while IFS= read -r -d '' verzeichnis; do
        relativ="${verzeichnis#"${quellordner}/"}"
        pruefe_relativpfad "${relativ}" || exit 1
    done < <(find "${vollstaendiger_pfad}" -type d -print0)

    while IFS= read -r -d '' datei; do
        relativ="${datei#"${quellordner}/"}"
        pruefe_relativpfad "${relativ}" || exit 1
        endung="${relativ##*.}"
        if ! ist_freigegeben "${endung}" "${freigegebene_endungen[@]}"; then
            echo "Dateityp ist für das Release nicht freigegeben: ${relativ}" >&2
            exit 1
        fi
        freigegebene_dateien+=("${relativ}")
    done < <(find "${vollstaendiger_pfad}" -type f -print0)
done

# Es wird nicht der gesamte Pluginordner kopiert. Jede Datei stammt aus der
# oben aufgebauten und vollständig geprüften Positivliste.
mkdir -p "${paketordner}"
for relativ in "${freigegebene_dateien[@]}"; do
    ziel="${paketordner}/${relativ}"
    mkdir -p "$(dirname "${ziel}")"
    cp "${quellordner}/${relativ}" "${ziel}"
done

# Einheitliche Rechte und Zeitstempel machen den Binärinhalt reproduzierbar.
find "${paketordner}" -type d -exec chmod 0755 {} +
find "${paketordner}" -type f -exec chmod 0644 {} +
TZ=UTC find "${paketordner}" -exec touch -t 202608120000.00 {} +

(
    # Info-ZIP schreibt DOS-Zeitstempel ohne Zeitzonenangabe. Deshalb muss
    # nicht nur das vorherige `touch`, sondern auch der Archivierungsprozess
    # selbst unabhängig von der Zeitzone des ausführenden Servers laufen.
    export TZ=UTC
    cd "${arbeitsordner}"
    # JTL-Shop 5.7.2 bestimmt den Plugin-Stamm aus dem ersten ZIP-Eintrag.
    # Deshalb muss der Stammordner zwingend vor der sortierten Dateiliste stehen.
    {
        printf '%s\n' 'MGD_AI_Kennzeichnung/'
        find MGD_AI_Kennzeichnung -type f -print | LC_ALL=C sort
    } > "${paketmanifest}"
    zip -X -q "${temporaeres_zip}" -@ < "${paketmanifest}"
)

# Das neue Archiv wird vollständig geprüft, bevor der bisherige geprüfte Stand
# berührt wird. Neben der ZIP-Integrität muss die enthaltene Positivliste exakt
# dem zuvor erzeugten Manifest entsprechen.
if ! unzip -tq "${temporaeres_zip}" >/dev/null; then
    echo "Die Integritätsprüfung des neuen Release-ZIPs ist fehlgeschlagen." >&2
    exit 1
fi
if ! unzip -Z1 "${temporaeres_zip}" > "${archivmanifest}" \
    || ! cmp -s "${paketmanifest}" "${archivmanifest}"; then
    echo "Die Integritätsprüfung des neuen Release-Inhalts ist fehlgeschlagen." >&2
    exit 1
fi

# mv verwendet innerhalb desselben Dateisystems rename(2): Das vollständig
# geprüfte neue ZIP ersetzt das alte in genau einem atomaren Schritt. Schlägt
# dieser Schritt fehl, bleibt das alte Ziel unverändert erhalten.
mv -f "${temporaeres_zip}" "${ausgabedatei}"

echo "Release erstellt: ${ausgabedatei}"
