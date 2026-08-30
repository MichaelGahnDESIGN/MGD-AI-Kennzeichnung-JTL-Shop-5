import { createPhilosophyLinkDialog } from './philosophy-link-dialog.mjs';

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
 * Erstellt eine lokale Werkzeugleiste. Der Adapter erhält nur IDs aus der
 * festen Positivliste; er kann daher keine beliebigen Browser-Commands oder
 * HTML-Tags ausführen.
 *
 * @param {{
 *   document?: Document|{createElement?: Function},
 *   adapter?: {execute?: (commandId: string, value?: string) => unknown},
 *   visual?: {focus?: () => void},
 *   sync?: {showVisual?: () => {ok?: boolean}, showHtml?: () => {ok?: boolean}},
 *   onChange?: () => void,
 *   onModeChange?: (mode: 'visual'|'html') => void,
 *   linkDialog?: {supported?: boolean, open?: () => boolean, destroy?: () => void},
 *   initialMode?: 'visual'|'html',
 * }} [options] Kleine explizite Abhängigkeiten für Browser und Editor.
 * @returns {{
 *   element: HTMLElement|null,
 *   buttons: Map<string, HTMLButtonElement>,
 *   visualButton: HTMLButtonElement|null,
 *   htmlButton: HTMLButtonElement|null,
 *   linkDialog: object,
 *   executeCommand: (commandId: string, value?: string) => boolean,
 *   setMode: (mode: 'visual'|'html') => boolean,
 *   destroy: () => void,
 * }} Öffentliche Bedienoberfläche ohne globale Initialisierung.
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
    let activeMode = configuration.initialMode === 'html' ? 'html' : 'visual';
    let commandInProgress = false;

    const toolbar = documentAdapter.createElement('div');
    toolbar.setAttribute('class', 'mgd-philosophy-toolbar');
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Werkzeuge für den Philosophie-Text');
    const buttons = new Map();
    const commandListeners = new Map();
    const linkDialog = configuration.linkDialog ?? createPhilosophyLinkDialog({
        document: documentAdapter,
        onInsert(url) {
            executeCommand('link', url);
        },
    });

    for (const definition of COMMAND_BUTTONS) {
        const button = createToolbarButton(documentAdapter, definition.label, definition.text, definition.id);
        if (definition.id === 'link' && linkDialog.supported !== true) {
            button.disabled = true;
        }
        const listener = () => {
            if (definition.id === 'link') {
                if (linkDialog.supported === true && typeof linkDialog.open === 'function') {
                    linkDialog.open();
                }
                return;
            }
            executeCommand(definition.id);
        };
        button.addEventListener('click', listener);
        buttons.set(definition.id, button);
        commandListeners.set(definition.id, listener);
        toolbar.append(button);
    }

    const visualButton = createModeButton(documentAdapter, 'Visuelle Bearbeitung', 'Visuell', 'visual');
    const htmlButton = createModeButton(documentAdapter, 'HTML-Bearbeitung', 'HTML', 'html');
    const visualListener = () => { setMode('visual'); };
    const htmlListener = () => { setMode('html'); };
    visualButton.addEventListener('click', visualListener);
    htmlButton.addEventListener('click', htmlListener);
    toolbar.append(visualButton, htmlButton);
    updateModeButtons();

    /** Führt nur einen festen Befehl aus und normalisiert danach den Editorzustand erneut. */
    function executeCommand(commandId, value) {
        if (!PHILOSOPHY_TOOLBAR_COMMAND_IDS.includes(commandId) || commandInProgress) {
            return false;
        }

        commandInProgress = true;
        try {
            if (typeof adapter.execute !== 'function' || adapter.execute(commandId, value) === false) {
                return false;
            }
            onChange();
            focusVisual();

            return true;
        } catch {
            return false;
        } finally {
            commandInProgress = false;
        }
    }

    /** Wechselt ausschließlich zwischen den beiden festen Ansichten und veröffentlicht erst Erfolg. */
    function setMode(mode) {
        if (mode !== 'visual' && mode !== 'html') {
            return false;
        }

        try {
            const result = sync && typeof (mode === 'visual' ? sync.showVisual : sync.showHtml) === 'function'
                ? (mode === 'visual' ? sync.showVisual() : sync.showHtml())
                : { ok: true };
            if (result && result.ok === false) {
                return false;
            }
            activeMode = mode;
            updateModeButtons();
            onModeChange(mode);
            if (mode === 'visual') {
                focusVisual();
            }

            return true;
        } catch {
            return false;
        }
    }

    /** Hält die beiden Toggle-Zustände für Screenreader und Maus sichtbar konsistent. */
    function updateModeButtons() {
        visualButton.setAttribute('aria-pressed', activeMode === 'visual' ? 'true' : 'false');
        htmlButton.setAttribute('aria-pressed', activeMode === 'html' ? 'true' : 'false');
    }

    /** Rückgabe des Fokus bleibt lokal und ist absichtlich unabhängig von Browser-Selections. */
    function focusVisual() {
        if (visual && typeof visual.focus === 'function') {
            visual.focus();
        }
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
