#!/usr/bin/env bash

# Erstellt aus einer festen Positivliste ein reproduzierbares JTL-Installations-ZIP.
set -euo pipefail

projekt_wurzel="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
quellordner="${projekt_wurzel}/plugin/MGD_AI_Kennzeichnung"
ausgabeordner="${projekt_wurzel}/dist"
ausgabedatei="${ausgabeordner}/MGD_AI_Kennzeichnung-1.2.0.zip"
arbeitsordner="$(mktemp -d "${TMPDIR:-/tmp}/mgd-ai-release.XXXXXX")"

aufräumen() {
    rm -rf "${arbeitsordner}"
}
trap aufräumen EXIT INT TERM

if [[ ! -f "${quellordner}/info.xml" || ! -f "${quellordner}/Bootstrap.php" ]]; then
    echo "Der freigegebene Plugin-Quellordner ist unvollständig." >&2
    exit 1
fi

if find "${quellordner}" -type l -print -quit | grep -q .; then
    echo "Symlinks sind im Release-Paket nicht erlaubt." >&2
    exit 1
fi

if find "${quellordner}" \( -name '.env' -o -name '.env.*' -o -name '.DS_Store' -o -name '.git' -o -name 'vendor' -o -name 'tests' \) -print -quit | grep -q .; then
    echo "Der Plugin-Quellordner enthält eine verbotene Datei oder ein verbotenes Verzeichnis." >&2
    exit 1
fi

mkdir -p "${arbeitsordner}/MGD_AI_Kennzeichnung" "${ausgabeordner}"
cp -R "${quellordner}/." "${arbeitsordner}/MGD_AI_Kennzeichnung/"

# Einheitliche Rechte und Zeitstempel machen den Binärinhalt reproduzierbar.
find "${arbeitsordner}/MGD_AI_Kennzeichnung" -type d -exec chmod 0755 {} +
find "${arbeitsordner}/MGD_AI_Kennzeichnung" -type f -exec chmod 0644 {} +
TZ=UTC find "${arbeitsordner}/MGD_AI_Kennzeichnung" -exec touch -t 202608120000.00 {} +

rm -f "${ausgabedatei}"
(
    cd "${arbeitsordner}"
    # JTL-Shop 5.7.2 bestimmt den Plugin-Stamm aus dem ersten ZIP-Eintrag.
    # Deshalb muss der Stammordner zwingend vor der sortierten Dateiliste stehen.
    {
        printf '%s\n' 'MGD_AI_Kennzeichnung/'
        find MGD_AI_Kennzeichnung -type f -print | LC_ALL=C sort
    } | zip -X -q "${ausgabedatei}" -@
)

echo "Release erstellt: ${ausgabedatei}"
