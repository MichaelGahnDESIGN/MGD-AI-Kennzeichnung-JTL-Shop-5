import { normalizeInteger } from './display-range-sync.mjs';

/** Ausschließlich diese Texte dürfen aus der lokalen Sprachwahl entstehen. */
const TEXT_BY_LANGUAGE = Object.freeze({
    auto: 'KI-GENERIERT',
    de: 'KI-GENERIERT',
    en: 'AI-GENERATED',
});

/** Ausschließlich diese Klassen dürfen aus der lokalen Positionswahl entstehen. */
const POSITION_CLASSES = Object.freeze({
    'bottom-left': 'mgd-display-preview--bottom-left',
    'bottom-right': 'mgd-display-preview--bottom-right',
    'top-left': 'mgd-display-preview--top-left',
    'top-right': 'mgd-display-preview--top-right',
});

/** Ausschließlich diese Klassen dürfen aus der lokalen Farbschemawahl entstehen. */
const THEME_CLASSES = Object.freeze({
    auto: 'mgd-display-preview--theme-auto',
    dark: 'mgd-display-preview--theme-dark',
    light: 'mgd-display-preview--theme-light',
});

/**
 * Nimmt einen Wert nur dann an, wenn er in einer positiven Liste steht.
 * Freie Klassennamen aus Select-Werten sind damit kategorisch ausgeschlossen.
 *
 * @param {unknown} value Ungeprüfter Auswahlwert.
 * @param {Record<string, string>} allowedValues Positive Liste.
 * @param {string} fallback Sicherer Schlüssel.
 * @returns {string} Ausschließlich ein erlaubter Schlüssel.
 */
function allowValue(value, allowedValues, fallback) {
    return typeof value === 'string' && Object.prototype.hasOwnProperty.call(allowedValues, value) ? value : fallback;
}

/**
 * Erstellt das vollständige, DOM-freie Modell der lokalen Vorschau. Sämtliche
 * Rückgabewerte sind entweder konstante Texte/Klassen oder fest benannte
 * CSS-Eigenschaften mit vorab validierten Pixel- beziehungsweise Alpha-Werten.
 *
 * @param {Record<string, unknown>} values Ungeprüfte Formular- und Vorschauwerte.
 * @returns {{ text: string, positionClass: string, themeClass: string, styles: Record<string, string> }} Sicheres Vorschau-Modell.
 */
export function createPreviewModel(values = {}) {
    /* Auch ein fehlerhafter externer Aufruf darf keine Adminseite abbrechen. */
    const safeValues = values && typeof values === 'object' ? values : {};
    const language = allowValue(safeValues.language, TEXT_BY_LANGUAGE, 'auto');
    const position = allowValue(safeValues.position, POSITION_CLASSES, 'bottom-right');
    const theme = allowValue(safeValues.theme, THEME_CLASSES, 'auto');
    const fontSize = normalizeInteger(safeValues.fontSize, 8, 48, 12);
    const outerMargin = normalizeInteger(safeValues.outerMargin, 0, 64, 8);
    const innerPadding = normalizeInteger(safeValues.innerPadding, 0, 32, 6);
    const borderRadius = normalizeInteger(safeValues.borderRadius, 0, 32, 4);
    const blur = normalizeInteger(safeValues.blur, 0, 24, 0);
    const transparency = normalizeInteger(safeValues.transparency, 0, 90, 8);

    return {
        text: TEXT_BY_LANGUAGE[language],
        positionClass: POSITION_CLASSES[position],
        themeClass: THEME_CLASSES[theme],
        styles: {
            '--mgd-preview-font-size': `${fontSize}px`,
            '--mgd-preview-outer-margin': `${outerMargin}px`,
            '--mgd-preview-inner-padding': `${innerPadding}px`,
            '--mgd-preview-border-radius': `${borderRadius}px`,
            '--mgd-preview-blur': `${blur}px`,
            '--mgd-preview-background-opacity': ((100 - transparency) / 100).toFixed(2),
        },
    };
}

/** Exportiert die festen Klassen für die kontrollierte DOM-Aktualisierung. */
export const PREVIEW_POSITION_CLASSES = Object.freeze(Object.values(POSITION_CLASSES));

/** Exportiert die festen Klassen für die kontrollierte DOM-Aktualisierung. */
export const PREVIEW_THEME_CLASSES = Object.freeze(Object.values(THEME_CLASSES));
