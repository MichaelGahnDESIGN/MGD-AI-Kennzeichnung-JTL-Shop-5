const POSITIONEN = new Set(['top-left', 'top-right', 'bottom-left', 'bottom-right']);
const DARSTELLUNGEN = new Set(['auto', 'light', 'dark']);

const STATUS_TEXTE = Object.freeze({
    unreviewed: 'Ungeprüft',
    none: 'Keine Kennzeichnung',
    generated: 'KI-generiert',
    'partially-generated': 'Teilweise KI-generiert',
    modified: 'KI-bearbeitet',
    deepfake: 'Deepfake',
});

/**
 * Erzeugt ausschließlich vorher festgelegte CSS-Klassen. Freie Eingaben
 * gelangen dadurch weder in Klassennamen noch ungeprüft in die Vorschau.
 */
export function createPreviewClassNames(position, theme) {
    if (!POSITIONEN.has(position)) {
        throw new TypeError('Die Position ist nicht freigegeben.');
    }
    if (!DARSTELLUNGEN.has(theme)) {
        throw new TypeError('Die Darstellung ist nicht freigegeben.');
    }

    return [
        'mgd-preview-label',
        `mgd-preview-label--${position}`,
        `mgd-preview-label--theme-${theme}`,
    ];
}

export function statusText(status) {
    if (!Object.hasOwn(STATUS_TEXTE, status)) {
        throw new TypeError('Der Status ist nicht freigegeben.');
    }

    return STATUS_TEXTE[status];
}

/** Aktualisiert nur die lokale Kopie im Dialog, niemals das Originalbild. */
export function renderPreviewLabel(preview, status, position, theme) {
    preview.querySelector('[data-preview-label]')?.remove();
    if (status === 'none') {
        return;
    }

    const label = document.createElement('span');
    label.dataset.previewLabel = 'true';
    label.classList.add(...createPreviewClassNames(position, theme));
    label.textContent = statusText(status);
    preview.append(label);
}
