import { sanitizePhilosophyHtml, isSafeHttpsUrl } from './philosophy-sanitizer.mjs';
import { createPhilosophySourceSync } from './philosophy-source-sync.mjs';
import { createPhilosophyToolbar } from './philosophy-toolbar.mjs';

const ELEMENT_NODE = 1;
const TEXT_NODE = 3;
const ALLOWED_ELEMENTS = new Set(['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'a']);
const BLOCK_ELEMENTS = new Set(['p', 'h2', 'h3', 'li']);
const LANGUAGE_NAMES = Object.freeze({ de: 'Deutsch', en: 'Englisch' });
const initializedRoots = new WeakMap();
const formStates = new WeakMap();
let editorSequence = 0;

/**
 * Initialisiert alle Philosophie-Sprachkarten unabhängig voneinander.
 * Ein Fehler in einer Karte bleibt lokal; ihr serverseitiges Textfeld wird
 * weder versteckt noch unbenutzbar gemacht.
 *
 * @param {{document?: Document, sanitize?: (value: unknown) => string}} [options]
 * @returns {{instances: Array<object>, destroy: () => void}}
 */
export function initializePhilosophyEditors(options = {}) {
    const documentAdapter = options.document ?? globalThis.document;
    if (!documentAdapter || typeof documentAdapter.querySelectorAll !== 'function') {
        return { instances: [], destroy() {} };
    }

    const roots = Array.from(documentAdapter.querySelectorAll('[data-philosophy-language]'));
    const instances = [];
    const localFailures = [];

    for (const root of roots) {
        const existing = initializedRoots.get(root);
        if (existing) {
            instances.push(existing);
            continue;
        }

        removeFallbackStatus(root);
        try {
            const instance = initializeLanguageEditor(root, {
                document: documentAdapter,
                sanitize: options.sanitize,
            });
            initializedRoots.set(root, instance);
            instances.push(instance);
        } catch {
            localFailures.push(showFallbackStatus(root, documentAdapter));
        }
    }

    let destroyed = false;
    return {
        instances,
        destroy() {
            if (destroyed) {
                return;
            }
            destroyed = true;
            for (const instance of instances) {
                instance.destroy();
            }
            for (const status of localFailures) {
                status.remove();
            }
        },
    };
}

/** Erstellt genau eine vollständig lokale Sprachinstanz. */
function initializeLanguageEditor(root, options) {
    const documentAdapter = options.document;
    const language = root.getAttribute('data-philosophy-language');
    const languageName = LANGUAGE_NAMES[language];
    const source = root.querySelector('[data-philosophy-source]');
    if (!languageName || !source || typeof source.value !== 'string') {
        throw new Error('Ungültige Philosophie-Sprachkarte.');
    }

    const originalSourceValue = source.value;
    const sanitize = typeof options.sanitize === 'function'
        ? options.sanitize
        : createBrowserSanitizer(documentAdapter);
    const canonical = sanitize(source.value);
    if (typeof canonical !== 'string') {
        throw new Error('Der Sanitizer lieferte keinen sicheren Text.');
    }

    const idPrefix = `mgd-ai-philosophy-${language}-${++editorSequence}`;
    const wrapper = documentAdapter.createElement('div');
    wrapper.setAttribute('id', `${idPrefix}-editor`);
    wrapper.setAttribute('class', 'mgd-philosophy-editor');
    wrapper.setAttribute('data-mgd-philosophy-role', 'editor');

    const visual = documentAdapter.createElement('div');
    visual.setAttribute('id', `${idPrefix}-visual`);
    visual.setAttribute('class', 'mgd-philosophy-editor__visual');
    visual.setAttribute('contenteditable', 'true');
    visual.setAttribute('role', 'textbox');
    visual.setAttribute('aria-multiline', 'true');
    visual.setAttribute('aria-label', `${languageName}: visuelle Bearbeitung`);
    visual.setAttribute('lang', language);
    visual.setAttribute('data-mgd-philosophy-role', 'visual');

    const html = documentAdapter.createElement('textarea');
    html.setAttribute('id', `${idPrefix}-html`);
    html.setAttribute('class', 'mgd-philosophy-editor__html');
    html.setAttribute('rows', '18');
    html.setAttribute('aria-label', `${languageName}: HTML-Quelltext`);
    html.setAttribute('lang', language);
    html.setAttribute('data-mgd-philosophy-role', 'html');

    const status = documentAdapter.createElement('p');
    status.setAttribute('id', `${idPrefix}-status`);
    status.setAttribute('class', 'mgd-philosophy-editor__status');
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.setAttribute('data-mgd-philosophy-role', 'status');

    const visualAdapter = createPhilosophyVisualAdapter({
        visual,
        document: documentAdapter,
        sanitize,
    });
    const sourceLabel = root.querySelector('[data-philosophy-source-label]');
    let rollbackResources = () => {};

    try {
        /*
         * Der bekannte Initialisierungsrandfall wird bewusst vor SourceSync
         * geschlossen: Alle drei Repräsentationen beginnen mit demselben erneut
         * bereinigten Serverwert. Ein leeres Hilfsfeld kann source daher nie leeren.
         */
        source.value = canonical;
        html.value = canonical;
        visualAdapter.render(canonical);

        const selection = createNativePhilosophySelection({ visual, document: documentAdapter });
        const commandAdapter = createPhilosophyCommandAdapter({
            visual,
            document: documentAdapter,
            selection,
        });

        const sync = createPhilosophySourceSync({
            source,
            html,
            visual: visualAdapter,
            sanitize,
            initialMode: 'visual',
        });

        const updateMode = (mode) => {
            const visualMode = mode === 'visual';
            setVisibility(visual, visualMode);
            setVisibility(html, !visualMode);
            status.textContent = visualMode ? 'Visuelle Bearbeitung' : 'HTML-Quelltext';
        };
        const reportSync = (result) => {
            if (!result || result.ok !== true) {
                status.textContent = 'Änderung konnte nicht sicher übernommen werden.';
                return false;
            }
            updateMode(sync.currentMode());
            return true;
        };

        const toolbar = createPhilosophyToolbar({
            document: documentAdapter,
            visual,
            selection,
            adapter: commandAdapter,
            sync,
            initialMode: 'visual',
            onChange() { return reportSync(sync.prepareSubmit()); },
            onModeChange(mode) { updateMode(mode); },
        });
        rollbackResources = () => {
            runBestEffort(() => { toolbar.destroy(); });
            runBestEffort(() => { wrapper.remove(); });
        };
        if (!toolbar.element) {
            throw new Error('Werkzeugleiste konnte nicht erstellt werden.');
        }

        wrapper.append(toolbar.element, visual, html, status);
        root.appendChild(wrapper);
        updateMode('visual');

        const handleVisualInput = () => { reportSync(sync.prepareSubmit()); };
        const handleHtmlInput = () => { reportSync(sync.prepareInput()); };
        const handlePaste = (event) => {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            if (!insertSafePaste(event, { visual, document: documentAdapter, selection, sanitize })) {
                status.textContent = 'Einfügen war ohne sichere Auswahl nicht möglich.';
                visual.focus();
                return;
            }
            reportSync(sync.prepareSubmit());
            visual.focus();
        };
        rollbackResources = () => {
            runBestEffort(() => { visual.removeEventListener('input', handleVisualInput); });
            runBestEffort(() => { visual.removeEventListener('paste', handlePaste); });
            runBestEffort(() => { html.removeEventListener('input', handleHtmlInput); });
            runBestEffort(() => { toolbar.destroy(); });
            runBestEffort(() => { wrapper.remove(); });
        };
        visual.addEventListener('input', handleVisualInput);
        visual.addEventListener('paste', handlePaste);
        html.addEventListener('input', handleHtmlInput);

        let destroyed = false;
        const instance = {
            ok: true,
            language,
            root,
            source,
            html,
            visual,
            status,
            toolbar,
            sync,
            prepareSubmit() {
                return sync.prepareSubmit();
            },
            destroy() {
                if (destroyed) {
                    return;
                }
                destroyed = true;
                runBestEffort(() => { visual.removeEventListener('input', handleVisualInput); });
                runBestEffort(() => { visual.removeEventListener('paste', handlePaste); });
                runBestEffort(() => { html.removeEventListener('input', handleHtmlInput); });
                runBestEffort(() => { toolbar.destroy(); });
                runBestEffort(() => { wrapper.remove(); });
                runBestEffort(() => { setVisibility(source, true); });
                if (sourceLabel) {
                    runBestEffort(() => { setVisibility(sourceLabel, true); });
                }
                runBestEffort(() => { initializedRoots.delete(root); });
                runBestEffort(() => { unregisterFormInstance(instance); });
            },
        };
        rollbackResources = () => { instance.destroy(); };

        /*
         * Registrierung und Fallback-Verstecken bilden den Commit der Instanz.
         * Jeder Fehler bis einschließlich dieses Punkts räumt alle bereits
         * erzeugten lokalen Ressourcen wieder auf und lässt die Source sichtbar.
         */
        registerFormInstance(instance);
        setVisibility(source, false);
        if (sourceLabel) {
            setVisibility(sourceLabel, false);
        }
        return instance;
    } catch (error) {
        runBestEffort(rollbackResources);
        runBestEffort(() => { source.value = originalSourceValue; });
        runBestEffort(() => { setVisibility(source, true); });
        if (sourceLabel) {
            runBestEffort(() => { setVisibility(sourceLabel, true); });
        }
        throw error;
    }
}

/** Führt unabhängige Aufräumschritte aus, ohne nachfolgende Schritte zu blockieren. */
function runBestEffort(callback) {
    try {
        callback();
    } catch {
        /* Ein defekter DOM-Adapter darf keine Dialoge oder Listener zurücklassen. */
    }
}

/**
 * Baut den echten Sanitizer mit getrenntem Parse- und Zieldokument auf.
 * Die expliziten Adapter vermeiden globale Zustände und erleichtern Fail-Closed.
 */
function createBrowserSanitizer(documentAdapter) {
    const Parser = documentAdapter.defaultView?.DOMParser ?? globalThis.DOMParser;
    if (typeof Parser !== 'function' || !documentAdapter.implementation?.createHTMLDocument) {
        throw new Error('Sichere HTML-Verarbeitung wird nicht unterstützt.');
    }

    return (value) => sanitizePhilosophyHtml(value, {
        domParser: new Parser(),
        document: documentAdapter.implementation.createHTMLDocument(''),
    });
}

/**
 * Rendert ausschließlich bereinigtes HTML über Parser, importNode und append.
 * Die Rückserialisierung läuft über eine Positivliste und erneut durch den
 * Sanitizer, bevor SourceSync den Wert in das Formular übernehmen darf.
 */
export function createPhilosophyVisualAdapter({ visual, document, sanitize }) {
    const Parser = document.defaultView?.DOMParser ?? globalThis.DOMParser;
    if (typeof Parser !== 'function' || typeof document.importNode !== 'function') {
        throw new Error('DOM-Fragmentverarbeitung wird nicht unterstützt.');
    }

    return {
        render(safeHtml) {
            const verified = sanitize(safeHtml);
            if (typeof verified !== 'string') {
                throw new Error('Ungültiger Visualwert.');
            }

            /*
             * Ein Input-Ereignis darf eine weiterhin gültige DOM-Struktur nicht
             * ersetzen: Das würde die native Auswahl und den Schreibcursor
             * verlieren. Der schnelle Pfad gilt ausschließlich für einen
             * vollständig kanonischen Baum ohne freie Elemente oder Attribute.
             */
            const currentSerialized = serializeSafeChildren(visual);
            if (isCanonicalVisualTree(visual)
                && sanitize(currentSerialized) === verified) {
                return;
            }

            const parsed = new Parser().parseFromString(verified, 'text/html');
            if (!parsed?.body) {
                throw new Error('Visualwert konnte nicht geparst werden.');
            }
            const fragment = document.createDocumentFragment();
            for (const node of Array.from(parsed.body.childNodes ?? [])) {
                fragment.appendChild(document.importNode(node, true));
            }
            visual.replaceChildren();
            visual.appendChild(fragment);
        },
        serialize() {
            return sanitize(serializeSafeChildren(visual));
        },
    };
}

/** Prüft den sichtbaren Baum gegen dieselbe enge Positivliste wie der Serialisierer. */
function isCanonicalVisualTree(visual) {
    return Array.from(visual.childNodes ?? []).every(isCanonicalVisualNode);
}

function isCanonicalVisualNode(node) {
    if (node.nodeType === TEXT_NODE) {
        return true;
    }
    if (node.nodeType !== ELEMENT_NODE) {
        return false;
    }

    const name = String(node.localName ?? node.tagName ?? '').toLowerCase();
    if (!ALLOWED_ELEMENTS.has(name)) {
        return false;
    }

    const attributes = readAttributeEntries(node);
    if (name === 'a') {
        const href = node.getAttribute('href');
        if (!isSafeHttpsUrl(href)
            || node.getAttribute('rel') !== 'noopener noreferrer'
            || attributes.length !== 2
            || attributes.some(([attributeName]) => attributeName !== 'href' && attributeName !== 'rel')) {
            return false;
        }
    } else if (attributes.length !== 0) {
        return false;
    }

    return Array.from(node.childNodes ?? []).every(isCanonicalVisualNode);
}

/** Vereinheitlicht echte NamedNodeMaps und den kleinen Map-basierten Testadapter. */
function readAttributeEntries(element) {
    return Array.from(element.attributes ?? [], (attribute) => Array.isArray(attribute)
        ? [String(attribute[0]).toLowerCase(), String(attribute[1])]
        : [String(attribute.name).toLowerCase(), String(attribute.value)]);
}

/** Serialisiert nur Text und die explizit erlaubte Editorstruktur. */
function serializeSafeChildren(parent) {
    return Array.from(parent.childNodes ?? []).map(serializeSafeNode).join('');
}

function serializeSafeNode(node) {
    if (node.nodeType === TEXT_NODE) {
        const value = typeof node.data === 'string' ? node.data : node.nodeValue;
        return escapeHtmlText(typeof value === 'string' ? value : '');
    }
    if (node.nodeType !== ELEMENT_NODE) {
        return '';
    }

    const name = String(node.localName ?? node.tagName ?? '').toLowerCase();
    if (!ALLOWED_ELEMENTS.has(name)) {
        return serializeSafeChildren(node);
    }

    let attributes = '';
    if (name === 'a') {
        const href = node.getAttribute('href');
        if (!isSafeHttpsUrl(href)) {
            return serializeSafeChildren(node);
        }
        attributes = ` href="${escapeHtmlAttribute(href)}" rel="noopener noreferrer"`;
    }
    return `<${name}${attributes}>${serializeSafeChildren(node)}</${name}>`;
}

function escapeHtmlText(value) {
    return value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
}

function escapeHtmlAttribute(value) {
    return escapeHtmlText(value).replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}

/**
 * Kapselt die native Selection pro Visualroot. Nur vollständig lokale,
 * klonbare Ranges werden erfasst oder wiederhergestellt.
 */
export function createNativePhilosophySelection({ visual, document }) {
    const getSelection = document?.defaultView?.getSelection;
    if (typeof getSelection !== 'function') {
        return null;
    }

    return {
        capture() {
            try {
                const selection = getSelection.call(document.defaultView);
                if (!selection || selection.rangeCount !== 1) {
                    return null;
                }
                const range = selection.getRangeAt(0);
                if (!isLocalRange(range, visual) || typeof range.cloneRange !== 'function') {
                    return null;
                }
                return range.cloneRange();
            } catch {
                return null;
            }
        },
        restore(snapshot) {
            try {
                if (!isLocalRange(snapshot, visual)) {
                    return false;
                }
                const selection = getSelection.call(document.defaultView);
                if (!selection
                    || typeof selection.removeAllRanges !== 'function'
                    || typeof selection.addRange !== 'function') {
                    return false;
                }
                selection.removeAllRanges();
                selection.addRange(snapshot);
                return selection.rangeCount === 1 && selection.getRangeAt(0) === snapshot;
            } catch {
                return false;
            }
        },
    };
}

function isLocalRange(range, visual) {
    return Boolean(range
        && visual
        && typeof visual.contains === 'function'
        && visual.contains(range.startContainer)
        && visual.contains(range.endContainer));
}

/** Erstellt ausschließlich die fest verdrahteten Toolbar-Befehle. */
export function createPhilosophyCommandAdapter({ visual, document }) {
    const fixedBlockTags = new Set(['p', 'h2', 'h3']);
    const fixedInlineTags = new Set(['strong', 'em']);
    const fixedListTags = new Set(['ul', 'ol']);

    const currentRange = () => {
        try {
            const selection = document.defaultView?.getSelection?.();
            if (!selection || selection.rangeCount !== 1) {
                return null;
            }
            const range = selection.getRangeAt(0);
            return isLocalRange(range, visual) ? range : null;
        } catch {
            return null;
        }
    };

    const wrapRange = (tag, allowCollapsedText = null) => {
        const range = currentRange();
        if (!range || (range.collapsed && allowCollapsedText === null)) {
            return false;
        }
        const element = document.createElement(tag);
        if (allowCollapsedText !== null && range.collapsed) {
            element.appendChild(document.createTextNode(allowCollapsedText));
        } else {
            element.appendChild(range.extractContents());
        }
        range.insertNode(element);
        return true;
    };

    return {
        setBlockFormat(tag) {
            if (!fixedBlockTags.has(tag)) {
                return false;
            }
            const range = currentRange();
            if (!range) {
                return false;
            }
            const block = findLocalAncestor(range.startContainer, visual, BLOCK_ELEMENTS);
            if (!block) {
                return wrapRange(tag);
            }
            const replacement = document.createElement(tag);
            try {
                for (const child of Array.from(block.childNodes ?? [])) {
                    if (typeof child.cloneNode !== 'function') {
                        return false;
                    }
                    replacement.appendChild(child.cloneNode(true));
                }
            } catch {
                return false;
            }
            return replaceNode(block, replacement);
        },
        toggleInlineFormat(tag) {
            return fixedInlineTags.has(tag) && wrapRange(tag);
        },
        toggleList(tag) {
            if (!fixedListTags.has(tag)) {
                return false;
            }
            const range = currentRange();
            if (!range) {
                return false;
            }
            const list = document.createElement(tag);
            const item = document.createElement('li');
            if (!range.collapsed) {
                item.appendChild(range.extractContents());
            }
            list.appendChild(item);
            range.insertNode(list);
            return true;
        },
        insertLink(url) {
            if (!isSafeHttpsUrl(url)) {
                return false;
            }
            const range = currentRange();
            if (!range) {
                return false;
            }
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('rel', 'noopener noreferrer');
            if (range.collapsed) {
                link.appendChild(document.createTextNode(url));
            } else {
                link.appendChild(range.extractContents());
            }
            range.insertNode(link);
            return true;
        },
        removeFormat() {
            const range = currentRange();
            if (!range || range.collapsed) {
                return false;
            }
            const fragment = range.extractContents();
            range.insertNode(document.createTextNode(fragment.textContent ?? ''));
            return true;
        },
        undo() { return currentRange() !== null && executeFixedHistoryCommand(document, 'undo'); },
        redo() { return currentRange() !== null && executeFixedHistoryCommand(document, 'redo'); },
    };
}

/** History nutzt nur die beiden festen nativen Befehle und ansonsten keinen Ersatzpfad. */
function executeFixedHistoryCommand(documentAdapter, command) {
    try {
        if ((command !== 'undo' && command !== 'redo')
            || typeof documentAdapter.execCommand !== 'function') {
            return false;
        }
        if (typeof documentAdapter.queryCommandSupported === 'function'
            && documentAdapter.queryCommandSupported(command) !== true) {
            return false;
        }
        return documentAdapter.execCommand(command, false, null) === true;
    } catch {
        return false;
    }
}

function findLocalAncestor(node, visual, allowed) {
    let current = node?.nodeType === ELEMENT_NODE ? node : node?.parentNode;
    while (current && current !== visual) {
        const name = String(current.localName ?? current.tagName ?? '').toLowerCase();
        if (allowed.has(name)) {
            return current;
        }
        current = current.parentNode;
    }
    return null;
}

function replaceNode(current, replacement) {
    const parent = current.parentNode;
    if (!parent || typeof parent.replaceChild !== 'function') {
        return false;
    }

    try {
        parent.replaceChild(replacement, current);

        return replacement.parentNode === parent && current.parentNode !== parent;
    } catch {
        return false;
    }
}

/** Übernimmt Clipboard-Inhalte ausschließlich über sichere, explizite Knoten. */
function insertSafePaste(event, { visual, document, selection, sanitize }) {
    const snapshot = selection?.capture?.();
    if (!snapshot || !isLocalRange(snapshot, visual)) {
        return false;
    }

    try {
        const clipboard = event?.clipboardData;
        if (!clipboard || typeof clipboard.getData !== 'function') {
            return false;
        }
        const html = clipboard.getData('text/html');
        const plain = clipboard.getData('text/plain');
        const fragment = document.createDocumentFragment();
        if (typeof html === 'string' && html !== '') {
            const safeHtml = sanitize(html);
            if (typeof safeHtml !== 'string') {
                return false;
            }
            const Parser = document.defaultView?.DOMParser ?? globalThis.DOMParser;
            if (typeof Parser !== 'function') {
                return false;
            }
            const parsed = new Parser().parseFromString(safeHtml, 'text/html');
            for (const node of Array.from(parsed?.body?.childNodes ?? [])) {
                fragment.appendChild(document.importNode(node, true));
            }
        } else if (typeof plain === 'string') {
            fragment.appendChild(document.createTextNode(Array.from(plain).slice(0, 10_000).join('')));
        } else {
            return false;
        }
        snapshot.deleteContents();
        snapshot.insertNode(fragment);
        return true;
    } catch {
        return false;
    }
}

/** Setzt hidden und CSS-Anzeige gemeinsam, damit kein widersprüchlicher Zustand entsteht. */
function setVisibility(element, visible) {
    element.hidden = !visible;
    element.style.display = visible ? 'block' : 'none';
    element.setAttribute('aria-hidden', visible ? 'false' : 'true');
}

function showFallbackStatus(root, documentAdapter) {
    const status = documentAdapter.createElement('p');
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.setAttribute('data-mgd-philosophy-role', 'fallback-status');
    status.textContent = 'Der erweiterte Editor konnte nicht gestartet werden. Das Textfeld bleibt nutzbar.';
    root.appendChild(status);
    return status;
}

function removeFallbackStatus(root) {
    for (const status of Array.from(root.querySelectorAll('[data-mgd-philosophy-role="fallback-status"]'))) {
        status.remove();
    }
}

/** Registriert genau einen Submit-Listener je Formular für beide Sprachinstanzen. */
function registerFormInstance(instance) {
    const form = instance.root.closest('form');
    if (!form) {
        return;
    }
    let state = formStates.get(form);
    if (!state) {
        state = { instances: new Set(), listener: null };
        state.listener = (event) => {
            const failed = [];
            for (const editor of state.instances) {
                let result;
                try {
                    result = editor.prepareSubmit();
                } catch {
                    result = { ok: false };
                }
                if (!result || result.ok !== true) {
                    failed.push(editor);
                }
            }
            if (failed.length === 0) {
                return;
            }
            event.preventDefault();
            for (const editor of failed) {
                editor.status.textContent = 'Dieser Inhalt konnte nicht sicher vorbereitet und daher nicht gespeichert werden.';
            }
            failed[0].visual.focus();
        };
        form.addEventListener('submit', state.listener);
        formStates.set(form, state);
    }
    state.instances.add(instance);
}

function unregisterFormInstance(instance) {
    const form = instance.root.closest('form');
    const state = form ? formStates.get(form) : null;
    if (!state) {
        return;
    }
    state.instances.delete(instance);
    if (state.instances.size === 0) {
        form.removeEventListener('submit', state.listener);
        formStates.delete(form);
    }
}

/* Progressive Standardinitialisierung; Modulimport ohne Browser bleibt folgenlos. */
if (globalThis.document && typeof globalThis.document.querySelectorAll === 'function') {
    initializePhilosophyEditors({ document: globalThis.document });
}
