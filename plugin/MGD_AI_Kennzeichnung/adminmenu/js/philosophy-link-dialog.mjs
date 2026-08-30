import { isSafeHttpsUrl } from './philosophy-sanitizer.mjs';

/* Eindeutige lokale Instanznummern verhindern Kollisionen bei mehreren Editoren. */
let dialogInstanceCounter = 0;

/**
 * Übernimmt eine Linkadresse nur, wenn sie exakt dem bereits servernah
 * geprüften HTTPS-Vertrag des Philosophie-Sanitizers entspricht.
 *
 * Die Funktion trimmt absichtlich nicht: Leerzeichen, relative Adressen,
 * Zugangsdaten und fremde Ports dürfen nicht durch eine Browser-Normalisierung
 * zu scheinbar sicheren Links werden.
 *
 * @param {unknown} value Unvertrauenswürdige Eingabe aus dem URL-Feld.
 * @returns {string|null} Die unveränderte sichere URL oder `null`.
 */
export function normalizeSecureLink(value) {
    return isSafeHttpsUrl(value) ? value : null;
}

/**
 * Erstellt einen rein lokalen Linkdialog. Die Auswahl im visuellen Editor wird
 * nicht manipuliert, solange der Mensch nicht eine gültige Adresse bestätigt.
 * Ein vollständiger synchroner Auswahladapter ist Pflicht: Ohne ihn bleibt
 * der Dialog geschlossen. `onInsert` darf `boolean` oder `Promise<boolean>`
 * liefern; während eines Promise bleibt der Einfügevorgang gesperrt.
 *
 * @param {{
 *   document?: Document|{createElement?: Function, body?: {append?: Function}},
 *   instanceId?: string,
 *   selection?: {capture?: () => unknown, restore?: (snapshot: unknown) => unknown},
 *   onInsert?: (url: string) => unknown,
 *   onCancel?: () => unknown,
 *   normalizeLink?: (value: unknown) => string|null,
 *   labels?: {title?: string, url?: string, submit?: string},
 * }} [options] Kontrollierte Browser- und Callback-Abhängigkeiten.
 * @returns {{
 *   element: HTMLDialogElement|null,
 *   supported: boolean,
 *   selectionReady?: boolean,
 *   busy: boolean,
 *   subscribeBusy: (listener: (busy: boolean) => void) => () => void,
 *   open: () => boolean,
 *   close: () => boolean,
 *   destroy: () => void,
 * }} Dialog-Steuerung ohne globale Browser- oder Netzabhängigkeiten.
 */
export function createPhilosophyLinkDialog(options = {}) {
    const configuration = isObject(options) ? options : {};
    const documentAdapter = configuration.document ?? globalThis.document;
    const normalizeLink = typeof configuration.normalizeLink === 'function'
        ? configuration.normalizeLink
        : normalizeSecureLink;
    const onInsert = typeof configuration.onInsert === 'function' ? configuration.onInsert : () => false;
    const onCancel = typeof configuration.onCancel === 'function' ? configuration.onCancel : () => {};
    const selection = isObject(configuration.selection) ? configuration.selection : {};
    const selectionReady = typeof selection.capture === 'function' && typeof selection.restore === 'function';
    const labels = isObject(configuration.labels) ? configuration.labels : {};
    const titleText = resolveAccessibleText(labels.title, 'Link einfügen');
    const urlText = resolveAccessibleText(labels.url, 'HTTPS-Adresse');
    const submitText = resolveAccessibleText(labels.submit, 'Link einfügen');

    if (!documentAdapter || typeof documentAdapter.createElement !== 'function') {
        return createUnsupportedDialog();
    }

    const dialog = documentAdapter.createElement('dialog');
    if (!dialog || typeof dialog.showModal !== 'function' || typeof dialog.close !== 'function') {
        return createUnsupportedDialog();
    }

    const ids = createDialogIds(configuration.instanceId);
    dialog.setAttribute('aria-labelledby', ids.title);
    dialog.setAttribute('class', 'mgd-philosophy-link-dialog');
    dialog.setAttribute('data-mgd-philosophy-role', 'link-dialog');

    const title = createElement(documentAdapter, 'h2', titleText);
    title.setAttribute('id', ids.title);
    const label = createElement(documentAdapter, 'label', urlText);
    const input = createElement(documentAdapter, 'input');
    input.setAttribute('type', 'url');
    input.setAttribute('inputmode', 'url');
    input.setAttribute('autocomplete', 'url');
    input.setAttribute('aria-describedby', ids.error);
    input.setAttribute('aria-invalid', 'false');
    input.setAttribute('data-mgd-philosophy-role', 'link-url');
    label.setAttribute('for', ids.input);
    input.setAttribute('id', ids.input);
    const error = createElement(documentAdapter, 'p', '');
    error.setAttribute('id', ids.error);
    error.setAttribute('role', 'alert');
    error.setAttribute('data-mgd-philosophy-role', 'link-error');
    const actions = createElement(documentAdapter, 'div');
    actions.setAttribute('class', 'mgd-philosophy-link-dialog__actions');
    const cancel = createButton(documentAdapter, 'Abbrechen', 'link-cancel');
    const submit = createButton(documentAdapter, submitText, 'link-submit');

    actions.append(cancel, submit);
    dialog.append(title, label, input, error, actions);
    if (documentAdapter.body && typeof documentAdapter.body.append === 'function') {
        documentAdapter.body.append(dialog);
    }

    let insertionInProgress = false;
    let open = false;
    let destroyed = false;
    let hasSelectionSnapshot = false;
    let selectionSnapshot;
    const busyListeners = new Set();

    /** Öffnet den Dialog nur einmal; die bestehende Editor-Auswahl bleibt dabei unangetastet. */
    const openDialog = () => {
        if (destroyed || !selectionReady || insertionInProgress || open || dialog.open) {
            return false;
        }

        clearError();
        input.value = '';
        if (!captureSelection()) {
            return false;
        }
        try {
            dialog.showModal();
            open = true;
            if (typeof input.focus === 'function') {
                input.focus();
            }

            return true;
        } catch {
            /* Ein nicht öffnbarer nativer Dialog ist sicherheitshalber unbenutzbar. */
            clearSelectionSnapshot();
            return false;
        }
    };

    /** Schließt nur das modale Element; die Auswahl wird danach gezielt durch den aufrufenden Pfad behandelt. */
    const closeModal = () => {
        if (!open && !dialog.open) {
            return true;
        }
        try {
            dialog.close();
        } catch {
            /* Browserfehler dürfen nie einen Einfügevorgang auslösen. */
            return false;
        }
        if (dialog.open) {
            return false;
        }
        open = false;

        return true;
    };

    /** Der öffentliche Close-Pfad verhält sich wie ein Abbruch: Auswahl wiederherstellen und verwerfen. */
    const closeDialog = () => {
        /* Ohne Abort-Vertrag darf ein laufender Adapter nicht halb abgebrochen werden. */
        if (destroyed || insertionInProgress) {
            return false;
        }
        if (!closeModal()) {
            return false;
        }
        const restored = restoreSelection();
        clearSelectionSnapshot();

        return restored;
    };

    /** Bestätigt nur eine einmalige, unveränderte, positiv geprüfte HTTPS-Adresse. */
    const submitLink = (event) => {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (destroyed || insertionInProgress || (!open && !dialog.open)) {
            return;
        }

        let normalized;
        try {
            normalized = normalizeLink(input.value);
        } catch {
            normalized = null;
        }
        if (typeof normalized !== 'string' || !isSafeHttpsUrl(normalized)) {
            showError('Bitte geben Sie eine vollständige sichere HTTPS-Adresse ohne Zugangsdaten oder fremden Port ein.');
            return;
        }

        setInsertionInProgress(true);
        /* Ein modal geöffneter Dialog macht den übrigen Editor inert. */
        if (!closeModal()) {
            setInsertionInProgress(false);
            showError('Der Linkdialog konnte nicht sicher geschlossen werden.');
            return;
        }
        if (!restoreSelection()) {
            setInsertionInProgress(false);
            reopenAfterFailure('Der Link konnte nicht eingefügt werden. Bitte versuchen Sie es erneut.');
            return;
        }
        const insertionResult = callCallback(onInsert, normalized);
        if (isThenable(insertionResult)) {
            Promise.resolve(insertionResult).then(
                (result) => finalizeInsertion(result),
                () => finalizeInsertion(CALLBACK_FAILED),
            );
            return;
        }
        finalizeInsertion(insertionResult);
    };

    const cancelLink = (event) => {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (closeDialog()) {
            consumeBestEffortResult(callCallback(onCancel));
        }
    };
    const handleClose = () => {
        open = false;
    };
    const handleInput = () => { clearError(); };
    const handleInputKeydown = (event) => {
        if (event && event.key === 'Enter') {
            submitLink(event);
        }
    };
    const handleCancel = (event) => { cancelLink(event); };

    try {
        cancel.addEventListener('click', cancelLink);
        submit.addEventListener('click', submitLink);
        dialog.addEventListener('close', handleClose);
        dialog.addEventListener('cancel', handleCancel);
        input.addEventListener('input', handleInput);
        input.addEventListener('keydown', handleInputKeydown);
    } catch (error) {
        /* Ein nur teilweise gebauter Dialog darf weder DOM noch Listener zurücklassen. */
        runBestEffort(() => { cancel.removeEventListener('click', cancelLink); });
        runBestEffort(() => { submit.removeEventListener('click', submitLink); });
        runBestEffort(() => { dialog.removeEventListener('close', handleClose); });
        runBestEffort(() => { dialog.removeEventListener('cancel', handleCancel); });
        runBestEffort(() => { input.removeEventListener('input', handleInput); });
        runBestEffort(() => { input.removeEventListener('keydown', handleInputKeydown); });
        runBestEffort(() => { dialog.remove(); });
        throw error;
    }

    return {
        element: dialog,
        supported: true,
        selectionReady,
        get busy() {
            return insertionInProgress;
        },
        subscribeBusy(listener) {
            if (destroyed || typeof listener !== 'function') {
                return () => {};
            }
            busyListeners.add(listener);
            safelyNotifyBusyListener(listener, insertionInProgress);

            return () => { busyListeners.delete(listener); };
        },
        open: openDialog,
        close: closeDialog,
        destroy() {
            if (destroyed) {
                return;
            }
            destroyed = true;
            cancel.removeEventListener('click', cancelLink);
            submit.removeEventListener('click', submitLink);
            dialog.removeEventListener('close', handleClose);
            dialog.removeEventListener('cancel', handleCancel);
            input.removeEventListener('input', handleInput);
            input.removeEventListener('keydown', handleInputKeydown);
            insertionInProgress = false;
            busyListeners.clear();
            clearSelectionSnapshot();
            try {
                if (dialog.open) {
                    dialog.close();
                }
            } catch {
                /* Entfernen bleibt auch dann der sichere Abschluss. */
            }
            open = false;
            if (typeof dialog.remove === 'function') {
                dialog.remove();
            }
        },
    };

    /** Erfasst die Auswahl vor dem ersten Dialogfokus; globale Selection-APIs bleiben außen vor. */
    function captureSelection() {
        if (typeof selection.capture !== 'function') {
            return false;
        }

        const snapshot = callCallback(selection.capture);
        if (snapshot === null || snapshot === undefined || snapshot === CALLBACK_FAILED || isThenable(snapshot)) {
            consumeBestEffortResult(snapshot);
            return false;
        }
        selectionSnapshot = snapshot;
        hasSelectionSnapshot = true;

        return true;
    }

    /** Stellt die Auswahl erst nach dem vollständigen Schließen des modalen Dialogs wieder her. */
    function restoreSelection() {
        if (!hasSelectionSnapshot || typeof selection.restore !== 'function') {
            return true;
        }

        const result = callCallback(selection.restore, selectionSnapshot);
        if (isThenable(result)) {
            consumeBestEffortResult(result);
            return false;
        }

        return didSucceed(result);
    }

    /** Öffnet nach einem fehlgeschlagenen Adapter erneut, ohne die Linkeingabe zu verlieren. */
    function reopenAfterFailure(message) {
        if (destroyed) {
            return;
        }
        showError(message);
        try {
            dialog.showModal();
            open = true;
            if (typeof input.focus === 'function') {
                input.focus();
            }
        } catch {
            /* Ohne nativen Dialog bleibt der Fehler fail-closed statt den Link zu übernehmen. */
        }
    }

    function showError(message) {
        error.textContent = message;
        input.setAttribute('aria-invalid', 'true');
    }

    function clearError() {
        error.textContent = '';
        input.setAttribute('aria-invalid', 'false');
    }

    function clearSelectionSnapshot() {
        selectionSnapshot = undefined;
        hasSelectionSnapshot = false;
    }

    /** Beendet den Pending-Zustand erst nach dem asynchronen Linkadapter und öffnet bei Fehler erneut. */
    function finalizeInsertion(result) {
        if (destroyed) {
            insertionInProgress = false;
            clearSelectionSnapshot();
            return;
        }
        setInsertionInProgress(false);
        if (didSucceed(result)) {
            clearSelectionSnapshot();
            return;
        }
        reopenAfterFailure('Der Link konnte nicht eingefügt werden. Bitte versuchen Sie es erneut.');
    }

    /** Veröffentlicht den Busy-Zustand lokal, damit die zugehörige Toolbar konsistent deaktivieren kann. */
    function setInsertionInProgress(nextValue) {
        if (insertionInProgress === nextValue) {
            return;
        }
        insertionInProgress = nextValue;
        for (const listener of busyListeners) {
            safelyNotifyBusyListener(listener, insertionInProgress);
        }
    }
}

/** Liefert eine explizit nicht unterstützte Steuerung statt unsicherer Ersatzdialoge. */
function createUnsupportedDialog() {
    return {
        element: null,
        supported: false,
        selectionReady: false,
        busy: false,
        subscribeBusy: () => () => {},
        open: () => false,
        close: () => false,
        destroy: () => {},
    };
}

/** Erzeugt pro Dialog eindeutige, sichere IDs oder akzeptiert eine explizite lokale Instanzkennung. */
function createDialogIds(instanceId) {
    const serial = String(++dialogInstanceCounter);
    const candidate = typeof instanceId === 'string' && /^[A-Za-z0-9_-]{1,64}$/u.test(instanceId)
        ? instanceId
        : 'dialog';
    const prefix = `mgd-philosophy-link-${candidate}-${serial}`;

    return Object.freeze({ error: `${prefix}-error`, input: `${prefix}-url`, title: `${prefix}-title` });
}

const CALLBACK_FAILED = Symbol('callback-failed');

/** Führt einen Callback aus und kapselt nur synchrone Ausnahmen; Thenables bleiben für den Aufrufer sichtbar. */
function callCallback(callback, ...argumentsList) {
    try {
        return callback(...argumentsList);
    } catch {
        return CALLBACK_FAILED;
    }
}

/** Ein explizites `false` oder ein gekapselter Callbackfehler bedeutet keinen Erfolg. */
function didSucceed(result) {
    return result !== false && result !== CALLBACK_FAILED;
}

function isThenable(value) {
    try {
        return value !== null
            && (typeof value === 'object' || typeof value === 'function')
            && typeof value.then === 'function';
    } catch {
        return true;
    }
}

/** Hängt beide Promise-Ausgänge an, damit Ablehnungen niemals als unhandled verbleiben. */
function consumeBestEffortResult(result) {
    if (!isThenable(result)) {
        return;
    }
    try {
        Promise.resolve(result).then(() => {}, () => {});
    } catch {
        /* Auch exotische Thenables dürfen die Dialogsteuerung nicht unterbrechen. */
    }
}

/** Fehler eines Busy-Beobachters dürfen die sichere Dialogsteuerung niemals beeinflussen. */
function safelyNotifyBusyListener(listener, busy) {
    try {
        listener(busy);
    } catch {
        /* Eine externe Toolbar-Benachrichtigung bleibt bewusst optional. */
    }
}

/** Erstellt Textknoten ausschließlich über `textContent`, niemals über HTML-Zeichenketten. */
function createElement(documentAdapter, tagName, text = null) {
    const element = documentAdapter.createElement(tagName);
    if (text !== null) {
        element.textContent = text;
    }

    return element;
}

/** Alle Aktionen sind echte Nicht-Submit-Buttons und damit formularneutral. */
function createButton(documentAdapter, text, role) {
    const button = createElement(documentAdapter, 'button', text);
    button.setAttribute('type', 'button');
    button.setAttribute('data-mgd-philosophy-role', role);

    return button;
}

/** Begrenzt injizierte Beschriftungen auf kurze, nicht leere reine Textwerte. */
function resolveAccessibleText(value, fallback) {
    if (typeof value !== 'string') {
        return fallback;
    }
    const normalized = value.trim();

    return normalized !== '' && Array.from(normalized).length <= 200 ? normalized : fallback;
}

/** Konstruktions-Rollbacks bleiben vollständig, auch wenn ein DOM-Adapter beim Aufräumen wirft. */
function runBestEffort(callback) {
    try {
        callback();
    } catch {
        /* Der ursprüngliche Konstruktionsfehler bleibt die maßgebliche Ursache. */
    }
}

function isObject(value) {
    return value !== null && typeof value === 'object';
}
