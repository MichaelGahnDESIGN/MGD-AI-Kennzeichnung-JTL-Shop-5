/**
 * Normalisiert eine beliebige Auswahl auf positive, eindeutige Ganzzahlen.
 * Ungültige Browser- oder manipulierte Werte werden früh verworfen; die
 * endgültige Prüfung findet zusätzlich serverseitig statt.
 */
export function normalizeSelectedAssetIds(values) {
    const normalized = [];
    const known = new Set();

    for (const value of values) {
        const text = typeof value === 'string' ? value.trim() : value;
        const numeric = typeof text === 'number' || typeof text === 'string' ? Number(text) : Number.NaN;
        if (!Number.isSafeInteger(numeric) || numeric < 1 || known.has(numeric)) {
            continue;
        }
        known.add(numeric);
        normalized.push(numeric);
    }

    return normalized;
}

/** Verbindet die Auswahlboxen mit dem sichtbaren, barrierearmen Zähler. */
export function initializeGallerySelection(root) {
    const checkboxes = [...root.querySelectorAll('input[name="asset_ids[]"]')];
    const counter = root.querySelector('[data-selection-count]');
    if (!counter) {
        return;
    }

    const update = () => {
        const selected = normalizeSelectedAssetIds(
            checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value),
        );
        counter.textContent = String(selected.length);
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
    update();
}
