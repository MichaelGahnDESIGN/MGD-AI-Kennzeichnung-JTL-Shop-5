import { createPhilosophyLinkDialog, normalizeSecureLink } from './philosophy-link-dialog.mjs';

/** Diese Liste ist der vollständige, bewusst kleine Formatvertrag der Werkzeugleiste. */
export const PHILOSOPHY_TOOLBAR_COMMAND_IDS = Object.freeze([
    'paragraph',
    'heading-2',
    'heading-3',
    'bold',
    'italic',
    'unordered-list',
    'ordered-list',
    'link',
    'remove-format',
    'undo',
    'redo',
]);

/**
 * Der Adaptervertrag enthält nur die fachlich erlaubten Operationen und ihre
 * fest verdrahteten HTML-Ziele. Es gibt bewusst weder freie Tag-Namen noch
 * einen generischen Browser-Command-String.
 */
export const PHILOSOPHY_TOOLBAR_COMMANDS = Object.freeze([
    Object.freeze({ id: 'paragraph', method: 'setBlockFormat', value: 'p' }),
    Object.freeze({ id: 'heading-2', method: 'setBlockFormat', value: 'h2' }),
    Object.freeze({ id: 'heading-3', method: 'setBlockFormat', value: 'h3' }),
    Object.freeze({ id: 'bold', method: 'toggleInlineFormat', value: 'strong' }),
    Object.freeze({ id: 'italic', method: 'toggleInlineFormat', value: 'em' }),
    Object.freeze({ id: 'unordered-list', method: 'toggleList', value: 'ul' }),
    Object.freeze({ id: 'ordered-list', method: 'toggleList', value: 'ol' }),
    Object.freeze({ id: 'link', method: 'insertLink', value: null }),
    Object.freeze({ id: 'remove-format', method: 'removeFormat', value: null }),
    Object.freeze({ id: 'undo', method: 'undo', value: null }),
    Object.freeze({ id: 'redo', method: 'redo', value: null }),
]);

const COMMANDS_BY_ID = new Map(PHILOSOPHY_TOOLBAR_COMMANDS.map((command) => [command.id, command]));

/** Statische Beschriftungen verhindern freie Befehle, Tags oder Formatwerte aus der Oberfläche. */
const COMMAND_BUTTONS = Object.freeze([
    Object.freeze({ id: 'paragraph', label: 'Absatz', text: 'P' }),
    Object.freeze({ id: 'heading-2', label: 'Überschrift Ebene 2', text: 'H2' }),
    Object.freeze({ id: 'heading-3', label: 'Überschrift Ebene 3', text: 'H3' }),
    Object.freeze({ id: 'bold', label: 'Fett hervorheben', text: 'B' }),
    Object.freeze({ id: 'italic', label: 'Kursiv hervorheben', text: 'I' }),
    Object.freeze({ id: 'unordered-list', label: 'Aufzählungsliste', text: '•' }),
    Object.freeze({ id: 'ordered-list', label: 'Nummerierte Liste', text: '1.' }),
    Object.freeze({ id: 'link', label: 'Sicheren HTTPS-Link einfügen', text: 'Link' }),
    Object.freeze({ id: 'remove-format', label: 'Formatierung entfernen', text: 'Format' }),
    Object.freeze({ id: 'undo', label: 'Rückgängig', text: '↶' }),
    Object.freeze({ id: 'redo', label: 'Wiederholen', text: '↷' }),
]);

/**
 * Erstellt eine lokale Werkzeugleiste. Sie löst interne IDs ausschließlich
 * auf feste Adaptermethoden und Positivlistenwerte auf; freie Browser-Commands
 * oder HTML-Tags gelangen nicht zum Adapter.
 *
 * @param {{
 *   document?: Document|{createElement?: Function},
 *   adapter?: {
 *     setBlockFormat?: (tag: 'p'|'h2'|'h3') => unknown,
 *     toggleInlineFormat?: (tag: 'strong'|'em') => unknown,
 *     toggleList?: (tag: 'ul'|'ol') => unknown,
 *     insertLink?: (url: string) => unknown,
 *     removeFormat?: () => unknown,
 *     undo?: () => unknown,
 *     redo?: () => unknown,
 *   },
 *   visual?: {focus?: () => void},
 *   selection?: {capture?: () => unknown, restore?: (snapshot: unknown) => unknown},
 *   sync?: {showVisual?: () => ({ok?: boolean}|Promise<{ok?: boolean}>), showHtml?: () => ({ok?: boolean}|Promise<{ok?: boolean}>)},
 *   onChange?: () => unknown,
 *   onModeChange?: (mode: 'visual'|'html') => unknown,
 *   linkDialog?: {
 *     supported?: boolean,
 *     selectionReady?: boolean,
 *     busy?: boolean,
 *     subscribeBusy?: (listener: (busy: boolean) => void) => (() => void),
 *     open?: () => boolean,
 *     destroy?: () => void,
 *   },
 *   initialMode?: 'visual'|'html',
 *   scheduleMicrotask?: (callback: () => void) => void,
 * }} [options] Kleine explizite Abhängigkeiten für Browser und Editor.
 * @returns {{
 *   element: HTMLElement|null,
 *   buttons: Map<string, HTMLButtonElement>,
 *   visualButton: HTMLButtonElement|null,
 *   htmlButton: HTMLButtonElement|null,
 *   linkDialog: object,
 *   executeCommand: (commandId: string, value?: string) => boolean|Promise<boolean>,
 *   setMode: (mode: 'visual'|'html') => boolean|Promise<boolean>,
 *   destroy: () => void,
 * }} Öffentliche Bedienoberfläche ohne globale Initialisierung. Adapterpfade
 * liefern synchron `boolean` oder asynchron `Promise<boolean>`; während einer
 * Promise-Auflösung bleiben die jeweilige Aktion und Doppelklicks gesperrt.
 */
export function createPhilosophyToolbar(options = {}) {
    const configuration = isObject(options) ? options : {};
    const documentAdapter = configuration.document ?? globalThis.document;
    if (!documentAdapter || typeof documentAdapter.createElement !== 'function') {
        return createUnavailableToolbar();
    }

    const adapter = isObject(configuration.adapter) ? configuration.adapter : {};
    const visual = configuration.visual;
    const onChange = typeof configuration.onChange === 'function' ? configuration.onChange : () => {};
    const onModeChange = typeof configuration.onModeChange === 'function' ? configuration.onModeChange : () => {};
    const sync = isObject(configuration.sync) ? configuration.sync : null;
    const selection = isObject(configuration.selection) ? configuration.selection : null;
    const scheduleMicrotask = typeof configuration.scheduleMicrotask === 'function'
        ? configuration.scheduleMicrotask
        : scheduleForCurrentTurn;
    let activeMode = configuration.initialMode === 'html' ? 'html' : 'visual';
    let commandInProgress = false;
    let modeChangeInProgress = false;

    const toolbar = documentAdapter.createElement('div');
    toolbar.setAttribute('class', 'mgd-philosophy-toolbar');
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Werkzeuge für den Philosophie-Text');
    const buttons = new Map();
    const commandListeners = new Map();
    const pressedButtonIds = new Set();
    let linkButton = null;
    let unsubscribeLinkBusy = () => {};
    const linkDialog = configuration.linkDialog ?? createPhilosophyLinkDialog({
        document: documentAdapter,
        selection,
        onInsert(url) {
            return executeCommand('link', url);
        },
        onCancel() {
            focusVisual();
            return true;
        },
    });

    for (const definition of COMMAND_BUTTONS) {
        const button = createToolbarButton(documentAdapter, definition.label, definition.text, definition.id);
        if (definition.id === 'link') {
            linkButton = button;
            updateLinkButtonState();
        }
        const listener = (event) => {
            if (!claimButtonForCurrentTurn(definition.id, event)) {
                return;
            }
            if (definition.id === 'link') {
                if (isLinkDialogReady() && linkDialog.busy !== true) {
                    linkDialog.open();
                }
                updateLinkButtonState();
                return;
            }
            executeCommand(definition.id);
        };
        button.addEventListener('click', listener);
        buttons.set(definition.id, button);
        commandListeners.set(definition.id, listener);
        toolbar.append(button);
    }

    if (isLinkDialogReady() && typeof linkDialog.subscribeBusy === 'function') {
        const unsubscribe = linkDialog.subscribeBusy(() => { updateLinkButtonState(); });
        if (typeof unsubscribe === 'function') {
            unsubscribeLinkBusy = unsubscribe;
        }
    }
    updateLinkButtonState();

    const visualButton = createModeButton(documentAdapter, 'Visuelle Bearbeitung', 'Visuell', 'visual');
    const htmlButton = createModeButton(documentAdapter, 'HTML-Bearbeitung', 'HTML', 'html');
    const visualListener = (event) => {
        if (claimButtonForCurrentTurn('mode-visual', event)) {
            setMode('visual');
        }
    };
    const htmlListener = (event) => {
        if (claimButtonForCurrentTurn('mode-html', event)) {
            setMode('html');
        }
    };
    visualButton.addEventListener('click', visualListener);
    htmlButton.addEventListener('click', htmlListener);
    toolbar.append(visualButton, htmlButton);
    updateModeButtons();

    /** Führt nur einen festen Befehl aus und normalisiert danach den Editorzustand erneut. */
    function executeCommand(commandId, value) {
        const command = COMMANDS_BY_ID.get(commandId);
        const payload = resolveCommandPayload(command, value);
        if (!payload.ok || commandInProgress) {
            return false;
        }

        commandInProgress = true;
        try {
            const adapterResult = invokeAdapter(adapter, command, payload.value);
            if (isThenable(adapterResult)) {
                return Promise.resolve(adapterResult).then(
                    (result) => finishCommand(result),
                    () => finishCommand(false),
                );
            }
            return finishCommand(adapterResult);
        } catch {
            commandInProgress = false;
            return false;
        }

        /** Trennt die erfolgreiche Editor-Mutation strikt von der optionalen UI-Benachrichtigung. */
        function finishCommand(adapterResult) {
            if (adapterResult === false) {
                commandInProgress = false;
                return false;
            }

            let notificationResult;
            try {
                notificationResult = onChange();
            } catch {
                /* Die Mutation ist bereits erfolgreich; Benachrichtigungen dürfen sie nicht zurückrollen. */
                focusVisual();
                commandInProgress = false;
                return true;
            }
            focusVisual();
            if (isThenable(notificationResult)) {
                return Promise.resolve(notificationResult).then(
                    () => { commandInProgress = false; return true; },
                    () => { commandInProgress = false; return true; },
                );
            }
            commandInProgress = false;

            return true;
        }
    }

    /** Wechselt ausschließlich zwischen den beiden festen Ansichten und veröffentlicht erst Erfolg. */
    function setMode(mode) {
        if ((mode !== 'visual' && mode !== 'html') || modeChangeInProgress) {
            return false;
        }

        modeChangeInProgress = true;
        try {
            const result = sync && typeof (mode === 'visual' ? sync.showVisual : sync.showHtml) === 'function'
                ? (mode === 'visual' ? sync.showVisual() : sync.showHtml())
                : { ok: true };
            if (isThenable(result)) {
                return Promise.resolve(result).then(
                    (resolved) => finishModeChange(resolved),
                    () => finishModeChange({ ok: false }),
                );
            }
            return finishModeChange(result);
        } catch {
            modeChangeInProgress = false;
            return false;
        }

        /** Veröffentlicht den erfolgreichen Modus vor der rein nachgelagerten Benachrichtigung. */
        function finishModeChange(result) {
            if (result && result.ok === false) {
                modeChangeInProgress = false;
                return false;
            }

            activeMode = mode;
            updateModeButtons();
            if (mode === 'visual') {
                focusVisual();
            }
            let notificationResult;
            try {
                notificationResult = onModeChange(mode);
            } catch {
                modeChangeInProgress = false;
                return true;
            }
            if (isThenable(notificationResult)) {
                return Promise.resolve(notificationResult).then(
                    () => { modeChangeInProgress = false; return true; },
                    () => { modeChangeInProgress = false; return true; },
                );
            }
            modeChangeInProgress = false;

            return true;
        }
    }

    /** Hält die beiden Toggle-Zustände für Screenreader und Maus sichtbar konsistent. */
    function updateModeButtons() {
        visualButton.setAttribute('aria-pressed', activeMode === 'visual' ? 'true' : 'false');
        htmlButton.setAttribute('aria-pressed', activeMode === 'html' ? 'true' : 'false');
    }

    /** Die sichere Dialoginstanz ist allein für ihre Auswahl- und Busy-Capability verantwortlich. */
    function isLinkDialogReady() {
        return isObject(linkDialog)
            && linkDialog.supported === true
            && linkDialog.selectionReady === true
            && typeof linkDialog.open === 'function';
    }

    /** Hält native Deaktivierung und aria-disabled auch während asynchroner Links gleich. */
    function updateLinkButtonState() {
        if (!linkButton) {
            return;
        }
        const disabled = !isLinkDialogReady() || linkDialog.busy === true;
        linkButton.disabled = disabled;
        linkButton.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    }

    /** Rückgabe des Fokus bleibt lokal und ist absichtlich unabhängig von Browser-Selections. */
    function focusVisual() {
        if (visual && typeof visual.focus === 'function') {
            visual.focus();
        }
    }

    /**
     * Sperrt nur denselben Button bis zur nächsten Microtask. Der zweite
     * native Click eines Doppelklicks wird zusätzlich über `detail > 1`
     * erkannt; spätere Einzelklicks werden nicht künstlich verzögert.
     */
    function claimButtonForCurrentTurn(buttonId, event) {
        /* Ein zweiter nativer Click eines Doppelklicks trägt zuverlässig detail > 1. */
        if (event && Number.isInteger(event.detail) && event.detail > 1) {
            return false;
        }
        if (pressedButtonIds.has(buttonId)) {
            return false;
        }

        pressedButtonIds.add(buttonId);
        try {
            scheduleMicrotask(() => {
                pressedButtonIds.delete(buttonId);
            });
        } catch {
            /* Fehlerhafte Test- oder Browseradapter dürfen keine Dauersperre hinterlassen. */
            pressedButtonIds.delete(buttonId);
        }

        return true;
    }

    return {
        element: toolbar,
        buttons,
        visualButton,
        htmlButton,
        linkDialog,
        executeCommand,
        setMode,
        destroy() {
            unsubscribeLinkBusy();
            for (const [commandId, button] of buttons) {
                const listener = commandListeners.get(commandId);
                if (listener && typeof button.removeEventListener === 'function') {
                    button.removeEventListener('click', listener);
                }
            }
            visualButton.removeEventListener('click', visualListener);
            htmlButton.removeEventListener('click', htmlListener);
            if (linkDialog && typeof linkDialog.destroy === 'function') {
                linkDialog.destroy();
            }
        },
    };
}

/** Lässt die kurze Doppelklick-Sperre nach der laufenden Browser-Ereignisphase enden. */
function scheduleForCurrentTurn(callback) {
    if (typeof globalThis.queueMicrotask === 'function') {
        globalThis.queueMicrotask(callback);

        return;
    }

    Promise.resolve().then(callback);
}

/** Erlaubt für Nicht-Linkbefehle keinen fremden Wert und prüft Links nochmals unabhängig. */
function resolveCommandPayload(command, rawValue) {
    if (!command) {
        return { ok: false, value: null };
    }
    if (command.id !== 'link') {
        return rawValue === undefined
            ? { ok: true, value: command.value }
            : { ok: false, value: null };
    }

    const secureLink = normalizeSecureLink(rawValue);

    return secureLink === null
        ? { ok: false, value: null }
        : { ok: true, value: secureLink };
}

/** Ruft ausschließlich die pro Deskriptor erlaubte Adaptermethode mit dem festen Ziel auf. */
function invokeAdapter(adapter, command, value) {
    const method = adapter[command.method];
    if (typeof method !== 'function') {
        return false;
    }

    return command.id === 'link' || command.value !== null
        ? method.call(adapter, value)
        : method.call(adapter);
}

/** Erkennt Promises und exotische Thenables ohne sie als erfolgreichen Adapterwert zu behandeln. */
function isThenable(value) {
    try {
        return value !== null
            && (typeof value === 'object' || typeof value === 'function')
            && typeof value.then === 'function';
    } catch {
        return true;
    }
}

/** Erstellt einen nicht bedienbaren, aber typsicheren Rückgabewert ohne DOM. */
function createUnavailableToolbar() {
    return {
        element: null,
        buttons: new Map(),
        visualButton: null,
        htmlButton: null,
        linkDialog: { supported: false, open: () => false, destroy: () => {} },
        executeCommand: () => false,
        setMode: () => false,
        destroy: () => {},
    };
}

/** Jede Befehlsschaltfläche ist ein echter Button und trägt ausschließlich deutsche Bedienhinweise. */
function createToolbarButton(documentAdapter, label, text, commandId) {
    const button = documentAdapter.createElement('button');
    button.setAttribute('type', 'button');
    button.setAttribute('aria-label', label);
    button.setAttribute('data-mgd-philosophy-command', commandId);
    button.textContent = text;

    return button;
}

/** Die Modusschalter verwenden absichtlich keine Radio-Rolle, da sie als Toggle-Zustände lesbar bleiben. */
function createModeButton(documentAdapter, label, text, mode) {
    const button = createToolbarButton(documentAdapter, label, text, `mode-${mode}`);
    button.setAttribute('data-mgd-philosophy-mode', mode);
    button.setAttribute('aria-pressed', 'false');

    return button;
}

function isObject(value) {
    return value !== null && typeof value === 'object';
}
