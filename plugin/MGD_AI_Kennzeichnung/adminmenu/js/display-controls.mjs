import { createPreviewModel, PREVIEW_POSITION_CLASSES, PREVIEW_THEME_CLASSES } from './display-preview.mjs';
import { bindNumberAndRange } from './display-range-sync.mjs';

/** Feste Grenzen der serverseitig gespeicherten Darstellungswerte. */
const FIELD_CONFIGURATIONS = Object.freeze({
    blur: Object.freeze({ minimum: 0, maximum: 24, fallback: 0 }),
    border_radius: Object.freeze({ minimum: 0, maximum: 32, fallback: 4 }),
    font_size: Object.freeze({ minimum: 8, maximum: 48, fallback: 12 }),
    inner_padding: Object.freeze({ minimum: 0, maximum: 32, fallback: 6 }),
    outer_margin: Object.freeze({ minimum: 0, maximum: 64, fallback: 8 }),
    transparency: Object.freeze({ minimum: 0, maximum: 90, fallback: 8 }),
});

/** Diese sechs CSS-Variablen sind der vollständige Schreibvertrag der Vorschau. */
const PREVIEW_STYLE_PROPERTIES = Object.freeze([
    '--mgd-preview-font-size',
    '--mgd-preview-outer-margin',
    '--mgd-preview-inner-padding',
    '--mgd-preview-border-radius',
    '--mgd-preview-blur',
    '--mgd-preview-background-opacity',
]);

/** Liest ausschließlich den Wert eines bereits gefundenen Formularelements. */
function readValue(element) {
    return element && typeof element.value === 'string' ? element.value : '';
}

/**
 * Initialisiert nur einen angegebenen Darstellungsbereich. Fehlende Elemente
 * beenden die Funktion still und sicher, damit die übrige Adminseite jederzeit
 * benutzbar bleibt.
 *
 * @param {Element | null} root Darstellungsbereich mit stabilen data-mgd-Attributen.
 * @returns {() => void} Entfernt ausschließlich die hier gesetzten Listener.
 */
export function initializeDisplayControls(root) {
    if (!root || typeof root.querySelector !== 'function') {
        return () => {};
    }

    const form = root.querySelector('[data-mgd-display-form]');
    const preview = root.querySelector('[data-mgd-display-preview]');
    const label = root.querySelector('[data-mgd-display-label]');

    if (!form || !preview || !label || !preview.classList || !preview.style) {
        return () => {};
    }

    const controls = {};
    const removePairListeners = [];

    for (const [name, configuration] of Object.entries(FIELD_CONFIGURATIONS)) {
        const numberInput = form.querySelector(`[data-mgd-display-control="${name}"]`);
        controls[name] = numberInput;

        const rangeInput = form.querySelector(`[data-mgd-display-control="${name}"][data-mgd-display-range]`);

        if (rangeInput) {
            removePairListeners.push(bindNumberAndRange(
                numberInput,
                rangeInput,
                configuration.minimum,
                configuration.maximum,
                configuration.fallback,
            ));
        }
    }

    controls.language = form.querySelector('[data-mgd-display-control="language"]');
    const position = root.querySelector('[data-mgd-display-preview-position]');
    const theme = root.querySelector('[data-mgd-display-preview-theme]');

    /** Überträgt nur das vollständig validierte Modell in die vorhandene Vorschau. */
    const updatePreview = () => {
        const model = createPreviewModel({
            language: readValue(controls.language),
            position: readValue(position),
            theme: readValue(theme),
            fontSize: readValue(controls.font_size),
            outerMargin: readValue(controls.outer_margin),
            innerPadding: readValue(controls.inner_padding),
            borderRadius: readValue(controls.border_radius),
            blur: readValue(controls.blur),
            transparency: readValue(controls.transparency),
        });

        label.textContent = model.text;
        preview.classList.remove(...PREVIEW_POSITION_CLASSES, ...PREVIEW_THEME_CLASSES);
        preview.classList.add(model.positionClass, model.themeClass);

        for (const property of PREVIEW_STYLE_PROPERTIES) {
            preview.style.setProperty(property, model.styles[property]);
        }
    };
    const observedControls = [
        controls.language,
        controls.font_size,
        controls.outer_margin,
        controls.inner_padding,
        controls.border_radius,
        controls.blur,
        controls.transparency,
        position,
        theme,
    ].filter((element) => element && typeof element.addEventListener === 'function');

    for (const element of observedControls) {
        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    }

    updatePreview();

    return () => {
        for (const element of observedControls) {
            if (typeof element.removeEventListener === 'function') {
                element.removeEventListener('input', updatePreview);
                element.removeEventListener('change', updatePreview);
            }
        }

        for (const removeListeners of removePairListeners) {
            removeListeners();
        }
    };
}

/** Startet die lokale Vorschau erst, wenn der statische Adminbereich vorhanden ist. */
function initializeWhenReady() {
    if (typeof document === 'undefined' || typeof document.querySelectorAll !== 'function') {
        return;
    }

    for (const root of document.querySelectorAll('[data-mgd-display-root]')) {
        initializeDisplayControls(root);
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWhenReady, { once: true });
    } else {
        initializeWhenReady();
    }
}
