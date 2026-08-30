/**
 * Synchronisiert die drei Darstellungen eines Philosophie-Textes.
 *
 * Der PHP-Textarea-Wert (`source`) bleibt der Wert, den das Formular beim
 * Absenden übermittelt. `html` ist die bearbeitbare HTML-Ansicht und `visual`
 * kapselt einen späteren Rich-Text-Editor. Das Modul kennt bewusst weder DOM
 * noch Toolbar noch Browser-Speicher. Es arbeitet ausschließlich mit den
 * übergebenen, kleinen Adaptern.
 */

const MODES = new Set(['visual', 'html']);

/**
 * Erstellt eine zustandsbehaftete, aber pro Sprache vollständig isolierte
 * Synchronisierung.
 *
 * Adapter-Vertrag:
 * - `source` und `html` sind feldähnliche Objekte mit der String-Eigenschaft
 *   `value`.
 * - `visual.render(safeHtml)` übernimmt ausschließlich bereits bereinigtes
 *   HTML; `visual.serialize()` liefert den sichtbaren Editorinhalt.
 * - `sanitize(value)` muss einen String liefern. Fehler und andere Rückgaben
 *   werden aus Sicherheitsgründen zu einem leeren String.
 *
 * Alle öffentlichen Methoden geben `{ ok, mode, value }` zurück. `ok` ist nur
 * dann `true`, wenn Bereinigung sowie alle drei Adapteraktualisierungen
 * erfolgreich waren. `value` ist stets der sichere Wert, niemals Roh-HTML.
 *
 * @param {{
 *   source: {value: unknown},
 *   html: {value: unknown},
 *   visual: {render: (safeHtml: string) => void, serialize: () => unknown},
 *   sanitize: (html: unknown) => unknown,
 *   initialMode?: 'visual'|'html',
 *   onModeChange?: (mode: 'visual'|'html') => void,
 * }} adapters Abhängigkeiten einer einzelnen Sprachinstanz.
 * @returns {{
 *   showVisual: () => {ok: boolean, mode: 'visual'|'html', value: string},
 *   showHtml: () => {ok: boolean, mode: 'visual'|'html', value: string},
 *   prepareInput: () => {ok: boolean, mode: 'visual'|'html', value: string},
 *   prepareSubmit: () => {ok: boolean, mode: 'visual'|'html', value: string},
 *   currentMode: () => 'visual'|'html',
 * }} Kleine Steuerungs-API für einen späteren Editor-Bootstrap.
 */
export function createPhilosophySourceSync(adapters) {
    const configuration = isObject(adapters) ? adapters : {};
    const source = configuration.source;
    const html = configuration.html;
    const visual = configuration.visual;
    const sanitize = configuration.sanitize;
    const onModeChange = configuration.onModeChange;

    /* Die Zustandsmaschine kennt absichtlich nur die zwei sichtbaren Modi. */
    let mode = resolveInitialMode(configuration.initialMode);
    /* Der letzte sicher bereinigte Wert schützt nach Adapterfehlern vor Stale-Reads. */
    let canonicalValue = '';
    let hasCanonicalValue = false;
    let requiresCanonicalRecovery = false;
    /* Reentrante Adapter und Callbacks erhalten ein fail-closed Busy-Ergebnis. */
    let synchronizationInProgress = false;

    /** Wechselt in die visuelle Bearbeitung und bereinigt deren Eingabequelle. */
    function showVisual() {
        return synchronize('visual');
    }

    /** Wechselt in die HTML-Bearbeitung und bereinigt deren Eingabequelle. */
    function showHtml() {
        return synchronize('html');
    }

    /**
     * Synchronisiert während des Tippens, ohne das aktive HTML-Textfeld zu
     * überschreiben. So bleiben unvollständige Tags und die Cursorposition
     * bearbeitbar, während Source und inaktive Visualansicht sicher bleiben.
     */
    function prepareInput() {
        return synchronize(mode, false, true);
    }

    /**
     * Bereitet den Formularwert vor, ohne den Modus zu ändern. Dadurch wird
     * auch ein noch nicht ausgelöster Editor-Input beim Submit berücksichtigt.
     */
    function prepareSubmit() {
        return synchronize(mode, false);
    }

    /** Liefert ausschließlich einen der beiden erlaubten Zustände. */
    function currentMode() {
        return mode;
    }

    /**
     * Liest den aktuell maßgeblichen Inhalt, bereinigt ihn und schreibt ihn
     * in einer sicheren Reihenfolge zurück. Der Formularwert wird zuerst
     * überschrieben, damit Adapterfehler keinen unsicheren Rohwert bewahren.
     */
    function synchronize(nextMode, announceModeChange = true, preserveActiveHtml = false) {
        if (!MODES.has(nextMode)) {
            return createResult(false, mode, '');
        }

        if (synchronizationInProgress) {
            return createResult(false, mode, hasCanonicalValue ? canonicalValue : '');
        }

        synchronizationInProgress = true;
        try {
            const input = readCurrentValue();
            const sanitization = sanitizeValue(input.value, sanitize);
            const safeValue = sanitization.value;

            /*
             * Der kanonische Wert wird vor Adapterzugriffen festgehalten. Falls
             * ein Adapter nur teilweise schreibt oder einen alten Visualzustand
             * behält, darf dieser Inhalt später niemals wieder Formularquelle sein.
             */
            canonicalValue = safeValue;
            hasCanonicalValue = true;

            /*
             * Klare Fail-closed-Reihenfolge: zuerst der zu sendende Formularwert,
             * dann HTML und zuletzt der rein visuelle Adapter. Alle drei erhalten
             * ausschließlich den bereits bereinigten Wert.
             */
            const sourceWritten = writeField(source, safeValue);
            const htmlWritten = preserveActiveHtml && mode === 'html'
                ? true
                : writeField(html, safeValue);
            const visualRendered = renderVisual(visual, safeValue);
            const synchronized = input.ok && sanitization.ok
                && sourceWritten && htmlWritten && visualRendered;

            if (!synchronized) {
                /*
                 * DOM-Schreibvorgänge sind nicht transaktional. Statt einen
                 * möglicherweise alten Adapter erneut auszulesen, verwenden alle
                 * folgenden Aufrufe ausschließlich diesen sicheren Fallback.
                 */
                requiresCanonicalRecovery = true;

                return createResult(false, mode, safeValue);
            }

            /* Modus und UI-Callback werden erst nach dem vollständigen Commit sichtbar. */
            mode = nextMode;
            requiresCanonicalRecovery = false;
            if (announceModeChange) {
                notifyModeChange(onModeChange, mode);
            }

            return createResult(true, mode, safeValue);
        } finally {
            synchronizationInProgress = false;
        }
    }

    /**
     * Der Initialmodus ist standardmäßig HTML. Deshalb liest der erste Submit
     * den sichtbaren `html`-Adapter. Nach einem fehlgeschlagenen Adapter-Commit
     * ersetzt der sichere kanonische Wert jede potenziell veraltete Darstellung.
     */
    function readCurrentValue() {
        if (requiresCanonicalRecovery && hasCanonicalValue) {
            return { ok: true, value: canonicalValue };
        }

        return mode === 'visual' ? serializeVisual(visual) : readField(html);
    }

    return { showVisual, showHtml, prepareInput, prepareSubmit, currentMode };
}

/** Erzeugt ein einheitliches, für UI und Formularlogik einfach prüfbares Ergebnis. */
function createResult(ok, mode, value) {
    return { ok, mode, value };
}

/** Liest nur Stringwerte; nicht lesbare oder manipulierte Adapter sind leer. */
function readField(field) {
    try {
        return isObject(field) && typeof field.value === 'string'
            ? { ok: true, value: field.value }
            : { ok: false, value: '' };
    } catch {
        return { ok: false, value: '' };
    }
}

/** Schreibt nur sichere Strings und meldet auch Setterfehler an die Steuerung zurück. */
function writeField(field, safeValue) {
    try {
        if (!isObject(field)) {
            return false;
        }

        field.value = safeValue;

        /* Manche DOM-Wrapper ignorieren Schreibvorgänge still; deshalb zwingender Read-back. */
        return field.value === safeValue;
    } catch {
        return false;
    }
}

/** Liest den kontrollierten Serialisierer; jeder Fehler wird fail-closed leer. */
function serializeVisual(visual) {
    try {
        if (!isObject(visual) || typeof visual.serialize !== 'function') {
            return { ok: false, value: '' };
        }

        const serialized = visual.serialize();

        return typeof serialized === 'string'
            ? { ok: true, value: serialized }
            : { ok: false, value: '' };
    } catch {
        return { ok: false, value: '' };
    }
}

/** Übergibt dem Visualadapter ausschließlich die bereits bereinigte Zeichenkette. */
function renderVisual(visual, safeValue) {
    try {
        if (!isObject(visual) || typeof visual.render !== 'function') {
            return false;
        }

        visual.render(safeValue);

        return true;
    } catch {
        return false;
    }
}

/** Kapselt den Sanitizer: Ausnahmen und Nicht-Strings werden niemals übernommen. */
function sanitizeValue(rawValue, sanitize) {
    try {
        const sanitized = typeof sanitize === 'function' ? sanitize(rawValue) : null;

        return typeof sanitized === 'string'
            ? { ok: true, value: sanitized }
            : { ok: false, value: '' };
    } catch {
        return { ok: false, value: '' };
    }
}

/** Ein optionaler UI-Callback darf die sichere Datensynchronisation nicht stören. */
function notifyModeChange(callback, mode) {
    try {
        if (typeof callback === 'function') {
            callback(mode);
        }
    } catch {
        /* UI-Status ist nachrangig; die sichere Formularsynchronisation läuft weiter. */
    }
}

/** Akzeptiert auch prototypfreie Adapterobjekte, aber keine primitiven Werte. */
function isObject(value) {
    return value !== null && typeof value === 'object';
}

/** Nutzt nur einen explizit erlaubten Startmodus und fällt sonst auf HTML zurück. */
function resolveInitialMode(initialMode) {
    return MODES.has(initialMode) ? initialMode : 'html';
}
