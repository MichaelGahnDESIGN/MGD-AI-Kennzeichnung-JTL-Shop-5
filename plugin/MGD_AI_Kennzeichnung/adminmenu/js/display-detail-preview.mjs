import { PREVIEW_THEME_CLASSES } from './display-preview.mjs';

/** Nur diese sechs CSS-Variablen dürfen an die lokale Detail-Bühne gelangen. */
const DETAIL_STYLE_PROPERTIES = Object.freeze([
    '--mgd-preview-font-size',
    '--mgd-preview-outer-margin',
    '--mgd-preview-inner-padding',
    '--mgd-preview-border-radius',
    '--mgd-preview-blur',
    '--mgd-preview-background-opacity',
]);

/**
 * Überträgt das bereits validierte Produkt-Vorschaumodell auf die optionale
 * Detail-Bühne. Die Prüfung der Eingabewerte bleibt in createPreviewModel;
 * diese Funktion liest keine Formularwerte und legt keinen eigenen Zustand an.
 * Fehlende Detail-Elemente dürfen die übrige Adminseite nicht unterbrechen.
 *
 * @param {Element | null} root Gemeinsamer lokaler Darstellungsbereich.
 * @param {ReturnType<import('./display-preview.mjs').createPreviewModel>} model Bereits validiertes Vorschau-Modell.
 * @returns {void}
 */
export function updateDetailPreview(root, model) {
    if (!root || typeof root.querySelector !== 'function') {
        return;
    }

    const preview = root.querySelector('[data-mgd-detail-preview]');
    if (!preview || typeof preview.querySelector !== 'function' || !preview.classList || !preview.style) {
        return;
    }

    const label = preview.querySelector('[data-mgd-detail-label]');
    if (!label) {
        return;
    }

    /* Die Bühne bleibt per CSS zentriert; Produkt-Positionsklassen gehören nicht hierher. */
    preview.classList.remove(...PREVIEW_THEME_CLASSES);
    preview.classList.add(model.themeClass);

    for (const property of DETAIL_STYLE_PROPERTIES) {
        preview.style.setProperty(property, model.styles[property]);
    }

    const backgroundOpacity = Number(model.styles['--mgd-preview-background-opacity']);
    const textUpdates = [
        [label, model.text],
        [root.querySelector('[data-mgd-detail-transparency]'), `${Math.round((1 - backgroundOpacity) * 100)} %`],
        [root.querySelector('[data-mgd-detail-blur]'), model.styles['--mgd-preview-blur'].replace('px', ' px')],
    ];

    /* textContent verhindert HTML-Auswertung; unveränderte Texte werden nicht neu geschrieben. */
    for (const [element, text] of textUpdates) {
        if (element && element.textContent !== text) {
            element.textContent = text;
        }
    }

    const opaqueHint = root.querySelector('[data-mgd-detail-opaque]');
    if (opaqueHint) {
        opaqueHint.hidden = backgroundOpacity < 1;
    }
}
