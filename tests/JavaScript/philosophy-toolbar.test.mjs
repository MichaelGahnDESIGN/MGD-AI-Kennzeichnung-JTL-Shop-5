import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createPhilosophyLinkDialog,
    normalizeSecureLink,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-link-dialog.mjs';
import {
    PHILOSOPHY_TOOLBAR_COMMAND_IDS,
    createPhilosophyToolbar,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-toolbar.mjs';

/** Minimaler DOM-Ersatz, der nur den öffentlich genutzten Browservertrag abbildet. */
class TestElement {
    constructor(tagName) {
        this.tagName = tagName;
        this.attributes = new Map();
        this.children = [];
        this.listeners = new Map();
        this.disabled = false;
        this.hidden = false;
        this.value = '';
        this.textContent = '';
        this.focusCalls = 0;
    }

    setAttribute(name, value) {
        this.attributes.set(name, String(value));
    }

    getAttribute(name) {
        return this.attributes.get(name) ?? null;
    }

    append(...nodes) {
        this.children.push(...nodes);
    }

    addEventListener(type, listener) {
        this.listeners.set(type, [...(this.listeners.get(type) ?? []), listener]);
    }

    removeEventListener(type, listener) {
        this.listeners.set(type, (this.listeners.get(type) ?? []).filter((entry) => entry !== listener));
    }

    dispatch(type) {
        for (const listener of this.listeners.get(type) ?? []) {
            listener({ preventDefault() {} });
        }
    }

    focus() {
        this.focusCalls += 1;
    }
}

class TestDialog extends TestElement {
    constructor() {
        super('dialog');
        this.open = false;
        this.showModalCalls = 0;
        this.closeCalls = 0;
    }

    showModal() {
        this.open = true;
        this.showModalCalls += 1;
    }

    close() {
        this.open = false;
        this.closeCalls += 1;
        this.dispatch('close');
    }
}

/** Erzeugt ein lokales Dokument, wahlweise ohne native Dialogunterstützung. */
function createDocument({ dialogSupported = true } = {}) {
    const body = new TestElement('body');

    return {
        body,
        createElement(tagName) {
            if (tagName === 'dialog' && dialogSupported) {
                return new TestDialog();
            }

            return new TestElement(tagName);
        },
    };
}

/** Sucht ein direkt erstelltes Element über sein bewusst stabiles Datenattribut. */
function findByRole(element, role) {
    if (element.getAttribute('data-mgd-philosophy-role') === role) {
        return element;
    }

    for (const child of element.children) {
        const found = findByRole(child, role);
        if (found) {
            return found;
        }
    }

    return null;
}

test('Befehls-IDs sind vollständig, eindeutig und auf die erlaubte Menge begrenzt', () => {
    assert.deepEqual(PHILOSOPHY_TOOLBAR_COMMAND_IDS, [
        'paragraph', 'heading-2', 'heading-3', 'bold', 'italic',
        'unordered-list', 'ordered-list', 'link', 'remove-format', 'undo', 'redo',
    ]);
});

test('Werkzeugleiste erstellt nur echte deutsch beschriftete Buttons', () => {
    const document = createDocument();
    const toolbar = createPhilosophyToolbar({ document, adapter: { execute() {} } });
    const commandButtons = PHILOSOPHY_TOOLBAR_COMMAND_IDS.map((commandId) => toolbar.buttons.get(commandId));

    for (const button of commandButtons) {
        assert.equal(button.tagName, 'button');
        assert.equal(button.getAttribute('type'), 'button');
        assert.match(button.getAttribute('aria-label'), /[A-Za-zÄÖÜäöüß]/u);
    }
    assert.equal(toolbar.buttons.get('bold').textContent, 'B');
    assert.equal(toolbar.buttons.get('italic').textContent, 'I');
    assert.equal(toolbar.buttons.get('heading-2').textContent, 'H2');
    assert.equal(toolbar.buttons.get('unordered-list').textContent, '•');
    assert.equal(toolbar.buttons.get('ordered-list').textContent, '1.');
    assert.equal(toolbar.visualButton.getAttribute('aria-pressed'), 'true');
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'false');
});

test('feste Befehle nutzen ausschließlich den Adapter, synchronisieren und fokussieren', () => {
    const document = createDocument();
    const commands = [];
    let changes = 0;
    const visual = new TestElement('div');
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        adapter: { execute(commandId, value) { commands.push([commandId, value]); } },
        onChange() { changes += 1; },
    });

    toolbar.buttons.get('heading-2').dispatch('click');
    toolbar.buttons.get('bold').dispatch('click');

    assert.deepEqual(commands, [['heading-2', undefined], ['bold', undefined]]);
    assert.equal(changes, 2);
    assert.equal(visual.focusCalls, 2);
});

test('Modusschalter aktualisieren aria-pressed und nutzen den Sync-Adapter', () => {
    const document = createDocument();
    const modes = [];
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: { execute() {} },
        sync: {
            showVisual() { modes.push('visual'); return { ok: true }; },
            showHtml() { modes.push('html'); return { ok: true }; },
        },
    });

    toolbar.htmlButton.dispatch('click');
    assert.deepEqual(modes, ['html']);
    assert.equal(toolbar.visualButton.getAttribute('aria-pressed'), 'false');
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'true');
    toolbar.visualButton.dispatch('click');
    assert.equal(toolbar.visualButton.getAttribute('aria-pressed'), 'true');
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'false');
});

test('sicherer Dialog akzeptiert ausschließlich den Sanitizer-Vertrag und fügt einen Link einmal ein', () => {
    const document = createDocument();
    const inserted = [];
    const dialog = createPhilosophyLinkDialog({ document, onInsert(url) { inserted.push(url); } });
    const input = findByRole(dialog.element, 'link-url');
    const submit = findByRole(dialog.element, 'link-submit');

    assert.equal(dialog.open(), true);
    input.value = 'https://beispiel.de/pfad?x=1';
    submit.dispatch('click');
    submit.dispatch('click');

    assert.deepEqual(inserted, ['https://beispiel.de/pfad?x=1']);
    assert.equal(dialog.element.showModalCalls, 1);
    assert.equal(dialog.element.closeCalls, 1);
});

test('unsichere Linkadressen zeigen einen Inlinefehler und führen keinen Befehl aus', () => {
    const document = createDocument();
    const inserted = [];
    const dialog = createPhilosophyLinkDialog({ document, onInsert(url) { inserted.push(url); } });
    const input = findByRole(dialog.element, 'link-url');
    const submit = findByRole(dialog.element, 'link-submit');
    const error = findByRole(dialog.element, 'link-error');

    dialog.open();
    input.value = 'https://nutzer:passwort@beispiel.de:444/pfad';
    submit.dispatch('click');

    assert.deepEqual(inserted, []);
    assert.match(error.textContent, /HTTPS/u);
    assert.equal(dialog.element.open, true);
    assert.equal(normalizeSecureLink('https://beispiel.de:443/'), 'https://beispiel.de:443/');
    assert.equal(normalizeSecureLink('http://beispiel.de'), null);
    assert.equal(normalizeSecureLink('https://beispiel.de:444'), null);
});

test('Abbrechen bewahrt die Auswahl und führt keinen Linkbefehl aus', () => {
    const document = createDocument();
    let insertions = 0;
    const dialog = createPhilosophyLinkDialog({ document, onInsert() { insertions += 1; } });
    const cancel = findByRole(dialog.element, 'link-cancel');

    dialog.open();
    cancel.dispatch('click');

    assert.equal(insertions, 0);
    assert.equal(dialog.element.closeCalls, 1);
});

test('ohne Dialogunterstützung bleibt die Werkzeugleiste nutzbar und deaktiviert nur Links', () => {
    const document = createDocument({ dialogSupported: false });
    const toolbar = createPhilosophyToolbar({ document, adapter: { execute() {} } });

    assert.equal(toolbar.buttons.get('link').disabled, true);
    assert.equal(toolbar.buttons.get('bold').disabled, false);
});

test('Linkbutton führt nach erfolgreichem Einfügen genau einen Adapterbefehl aus und fokussiert', () => {
    const document = createDocument();
    const commands = [];
    const visual = new TestElement('div');
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        adapter: { execute(commandId, value) { commands.push([commandId, value]); } },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');

    assert.deepEqual(commands, [['link', 'https://beispiel.de']]);
    assert.equal(visual.focusCalls, 1);
});

test('Toolbar verwendet weder Netz noch Browser-Speicher', () => {
    const document = createDocument();
    let fetchCalls = 0;
    const originalFetch = globalThis.fetch;
    globalThis.fetch = () => { fetchCalls += 1; throw new Error('Nicht erlaubt'); };
    try {
        createPhilosophyToolbar({ document, adapter: { execute() {} } });
        assert.equal(fetchCalls, 0);
    } finally {
        globalThis.fetch = originalFetch;
    }
});
