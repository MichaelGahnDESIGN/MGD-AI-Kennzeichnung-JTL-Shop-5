import { createPreviewModel, PREVIEW_POSITION_CLASSES, PREVIEW_THEME_CLASSES } from './display-preview.mjs';
import { bindNumberAndRange } from './display-range-sync.mjs';

/** Stabile, voneinander unabhängige Schlüssel der drei gekoppelten Eingabepaare. */
const RANGE_PAIR_CONFIGURATIONS = Object.freeze({
    blur: Object.freeze({ fallback: 0, maximum: 24, minimum: 0, setting: 'blur' }),
    borderRadius: Object.freeze({ fallback: 4, maximum: 32, minimum: 0, setting: 'borderRadius' }),
    transparency: Object.freeze({ fallback: 8, maximum: 90, minimum: 0, setting: 'transparency' }),
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

    const controls = {
        fontSize: form.querySelector('[data-mgd-display-control="font_size"]'),
        innerPadding: form.querySelector('[data-mgd-display-control="inner_padding"]'),
        language: form.querySelector('[data-mgd-display-control="language"]'),
        outerMargin: form.querySelector('[data-mgd-display-control="outer_margin"]'),
    };
    const rangePairs = [];

    for (const [name, configuration] of Object.entries(RANGE_PAIR_CONFIGURATIONS)) {
        const numberInput = form.querySelector(`[data-mgd-number][data-mgd-setting="${configuration.setting}"]`);
        const rangeInput = form.querySelector(`[data-mgd-range][data-mgd-setting="${configuration.setting}"]`);
        controls[name] = numberInput;

        if (numberInput && rangeInput) {
            rangePairs.push({ configuration, numberInput, rangeInput });
        }
    }

    const position = root.querySelector('[data-mgd-display-preview-position]');
    const theme = root.querySelector('[data-mgd-display-preview-theme]');

    /** Überträgt nur das vollständig validierte Modell in die vorhandene Vorschau. */
    const updatePreview = () => {
        const model = createPreviewModel({
            language: readValue(controls.language),
            position: readValue(position),
            theme: readValue(theme),
            fontSize: readValue(controls.fontSize),
            outerMargin: readValue(controls.outerMargin),
            innerPadding: readValue(controls.innerPadding),
            borderRadius: readValue(controls.borderRadius),
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
        controls.fontSize,
        controls.outerMargin,
        controls.innerPadding,
        controls.borderRadius,
        controls.blur,
        controls.transparency,
        ...rangePairs.flatMap(({ rangeInput, numberInput }) => [numberInput, rangeInput]),
        position,
        theme,
    ].filter((element, index, elements) => element
        && typeof element.addEventListener === 'function'
        && elements.indexOf(element) === index);
    const pairedControls = new Set(rangePairs.flatMap(({ rangeInput, numberInput }) => [numberInput, rangeInput]));
    const removePairListeners = rangePairs.map(({ configuration, numberInput, rangeInput }) => bindNumberAndRange(
        numberInput,
        rangeInput,
        configuration.minimum,
        configuration.maximum,
        configuration.fallback,
        updatePreview,
    ));

    for (const element of observedControls) {
        if (pairedControls.has(element)) {
            continue;
        }

        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    }

    updatePreview();

    return () => {
        for (const element of observedControls) {
            if (pairedControls.has(element)) {
                continue;
            }

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
