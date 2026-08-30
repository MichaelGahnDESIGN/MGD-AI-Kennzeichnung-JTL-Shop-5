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
 *   onModeChange?: (mode: 'visual'|'html') => void,
 * }} adapters Abhängigkeiten einer einzelnen Sprachinstanz.
 * @returns {{
 *   showVisual: () => {ok: boolean, mode: 'visual'|'html', value: string},
 *   showHtml: () => {ok: boolean, mode: 'visual'|'html', value: string},
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
    let mode = 'html';
    /* Vor dem ersten Wechsel ist das serverseitige Formularfeld maßgeblich. */
    let hasSynchronizedRepresentation = false;

    /** Wechselt in die visuelle Bearbeitung und bereinigt deren Eingabequelle. */
    function showVisual() {
        return synchronize('visual');
    }

    /** Wechselt in die HTML-Bearbeitung und bereinigt deren Eingabequelle. */
    function showHtml() {
        return synchronize('html');
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
    function synchronize(nextMode, announceModeChange = true) {
        if (!MODES.has(nextMode)) {
            return createResult(false, mode, '');
        }

        const input = readCurrentValue();
        const sanitization = sanitizeValue(input.value, sanitize);
        const safeValue = sanitization.value;

        /* Der Modus bleibt selbst bei Fehlern ein gültiger, nachvollziehbarer Zustand. */
        mode = nextMode;
        if (announceModeChange) {
            notifyModeChange(onModeChange, mode);
        }

        const sourceWritten = writeField(source, safeValue);
        const htmlWritten = writeField(html, safeValue);
        const visualRendered = renderVisual(visual, safeValue);
        /*
         * Ein defekter Visualadapter darf die bereits sicher aktualisierten
         * Formular- und HTML-Werte nicht wieder zur unsicheren Initialquelle
         * zurückstufen. Für den nächsten Wechsel sind diese beiden Werte daher
         * weiterhin die maßgebliche, sichere Basis.
         */
        hasSynchronizedRepresentation = sourceWritten && htmlWritten;

        return createResult(
            input.ok && sanitization.ok && sourceWritten && htmlWritten && visualRendered,
            mode,
            safeValue,
        );
    }

    /**
     * Beim ersten Aufruf stammt der Inhalt immer aus dem servergerenderten
     * Formularfeld. Danach liest die Zustandsmaschine ausschließlich die
     * gerade sichtbare Repräsentation.
     */
    function readCurrentValue() {
        if (!hasSynchronizedRepresentation) {
            return readField(source);
        }

        return mode === 'visual' ? serializeVisual(visual) : readField(html);
    }

    return { showVisual, showHtml, prepareSubmit, currentMode };
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

        return true;
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
