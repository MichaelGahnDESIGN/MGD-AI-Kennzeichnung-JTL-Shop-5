/**
 * Prüft eine Konfigurationsgrenze. Nur nicht-negative sichere Ganzzahlen sind
 * für die Anzeige von Pixeln und Prozentwerten erlaubt.
 *
 * @param {unknown} value Zu prüfende Grenze.
 * @returns {boolean} Ob die Grenze sicher verwendet werden kann.
 */
function isSafeBoundary(value) {
    return Number.isSafeInteger(value) && value >= 0;
}

/**
 * Ermittelt aus fehlerhafter Konfiguration immer einen sicheren, kleinen
 * Bereich. Bei einer widersprüchlichen Konfiguration wird ausschließlich der
 * bereinigte Mindestwert verwendet, nie der unkontrollierte Eingabewert.
 *
 * @param {unknown} minimum Untere Grenze.
 * @param {unknown} maximum Obere Grenze.
 * @param {unknown} fallback Sicherer Standardwert.
 * @returns {{ minimum: number, maximum: number, fallback: number, valid: boolean }} Bereinigte Konfiguration.
 */
function normalizeConfiguration(minimum, maximum, fallback) {
    const safeMinimum = isSafeBoundary(minimum) ? minimum : 0;
    const safeMaximum = isSafeBoundary(maximum) && maximum >= safeMinimum ? maximum : safeMinimum;
    const safeFallback = Number.isSafeInteger(fallback) && fallback >= safeMinimum && fallback <= safeMaximum
        ? fallback
        : safeMinimum;
    const isValid = isSafeBoundary(minimum)
        && isSafeBoundary(maximum)
        && maximum >= minimum
        && Number.isSafeInteger(fallback)
        && fallback >= minimum
        && fallback <= maximum;

    return {
        minimum: safeMinimum,
        maximum: safeMaximum,
        fallback: isValid ? safeFallback : safeMinimum,
        valid: isValid,
    };
}

/**
 * Normalisiert einen von Menschen eingegebenen Ganzzahlstring. Bewusst werden
 * weder Vorzeichen, Dezimalzahlen, Einheiten noch führende Nullen akzeptiert.
 * Dadurch kann der Wert später gefahrlos in fest definierte CSS-Werte münden.
 *
 * @param {unknown} value Nutzereingabe aus einem HTML-Eingabefeld.
 * @param {unknown} minimum Untere, inklusive Grenze.
 * @param {unknown} maximum Obere, inklusive Grenze.
 * @param {unknown} fallback Sichere Anzeige bei ungültiger Eingabe.
 * @returns {number} Eine garantiert sichere Ganzzahl innerhalb des Bereichs.
 */
export function normalizeInteger(value, minimum, maximum, fallback) {
    const configuration = normalizeConfiguration(minimum, maximum, fallback);

    if (!configuration.valid || typeof value !== 'string' || !/^(?:0|[1-9]\d*)$/.test(value)) {
        return configuration.fallback;
    }

    const number = Number(value);

    if (!Number.isSafeInteger(number) || number < configuration.minimum || number > configuration.maximum) {
        return configuration.fallback;
    }

    return number;
}

/**
 * Koppelt genau ein Zahlenfeld mit genau einem Regler. Die Funktion schreibt
 * nur in die beiden übergebenen Felder; sie versendet nichts und verändert
 * weder Formular noch lokalen Speicher.
 *
 * @param {{ value: unknown, addEventListener?: Function, removeEventListener?: Function } | null} numberInput Zahlenfeld.
 * @param {{ value: unknown, addEventListener?: Function, removeEventListener?: Function } | null} rangeInput Regler.
 * @param {number} minimum Untere Grenze.
 * @param {number} maximum Obere Grenze.
 * @param {number} fallback Sicherer Standardwert.
 * @param {(() => void) | undefined} afterSynchronize Optionale, lokale Folgeaktion nach einem Nutzerevent.
 * @returns {() => void} Entfernt die registrierten Listener wieder.
 */
export function bindNumberAndRange(numberInput, rangeInput, minimum, maximum, fallback, afterSynchronize) {
    if (!numberInput || !rangeInput || typeof numberInput.addEventListener !== 'function' || typeof rangeInput.addEventListener !== 'function') {
        return () => {};
    }

    /** Übernimmt einen geprüften Wert gleichzeitig in beide Bedienelemente. */
    const synchronize = (value) => {
        const normalizedValue = String(normalizeInteger(value, minimum, maximum, fallback));
        numberInput.value = normalizedValue;
        rangeInput.value = normalizedValue;
    };
    const synchronizeNumber = () => {
        synchronize(numberInput.value);
        if (typeof afterSynchronize === 'function') {
            afterSynchronize();
        }
    };
    const synchronizeRange = () => {
        synchronize(rangeInput.value);
        if (typeof afterSynchronize === 'function') {
            afterSynchronize();
        }
    };

    synchronize(numberInput.value);

    for (const eventName of ['input', 'change']) {
        numberInput.addEventListener(eventName, synchronizeNumber);
        rangeInput.addEventListener(eventName, synchronizeRange);
    }

    return () => {
        if (typeof numberInput.removeEventListener !== 'function' || typeof rangeInput.removeEventListener !== 'function') {
            return;
        }

        for (const eventName of ['input', 'change']) {
            numberInput.removeEventListener(eventName, synchronizeNumber);
            rangeInput.removeEventListener(eventName, synchronizeRange);
        }
    };
}
