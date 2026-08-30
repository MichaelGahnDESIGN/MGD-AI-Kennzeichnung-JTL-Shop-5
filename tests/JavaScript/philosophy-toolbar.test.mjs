import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createPhilosophyLinkDialog,
    normalizeSecureLink,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-link-dialog.mjs';
import * as toolbarModule from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-toolbar.mjs';

const {
    PHILOSOPHY_TOOLBAR_COMMANDS,
    PHILOSOPHY_TOOLBAR_COMMAND_IDS,
    createPhilosophyToolbar,
} = toolbarModule;

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
        for (const node of nodes) {
            node.parentNode = this;
            this.children.push(node);
        }
    }

    addEventListener(type, listener) {
        this.listeners.set(type, [...(this.listeners.get(type) ?? []), listener]);
    }

    removeEventListener(type, listener) {
        this.listeners.set(type, (this.listeners.get(type) ?? []).filter((entry) => entry !== listener));
    }

    dispatch(type, event = {}) {
        for (const listener of this.listeners.get(type) ?? []) {
            listener({ preventDefault() {}, ...event });
        }
    }

    focus() {
        this.focusCalls += 1;
    }

    remove() {
        if (this.parentNode) {
            this.parentNode.children = this.parentNode.children.filter((child) => child !== this);
            this.parentNode = null;
        }
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

/** Liefert einen synchronen, harmlosen Auswahladapter für Linkdialog-Tests. */
function createSelectionAdapter() {
    return {
        capture() { return { range: 'test' }; },
        restore() {},
    };
}

test('Befehls-IDs sind vollständig, eindeutig und auf die erlaubte Menge begrenzt', () => {
    assert.deepEqual(PHILOSOPHY_TOOLBAR_COMMAND_IDS, [
        'paragraph', 'heading-2', 'heading-3', 'bold', 'italic',
        'unordered-list', 'ordered-list', 'link', 'remove-format', 'undo', 'redo',
    ]);
});

test('Befehlsdeskriptoren enthalten ausschließlich die fest erlaubten HTML-Ziele', () => {
    assert.deepEqual(PHILOSOPHY_TOOLBAR_COMMANDS, [
        { id: 'paragraph', method: 'setBlockFormat', value: 'p' },
        { id: 'heading-2', method: 'setBlockFormat', value: 'h2' },
        { id: 'heading-3', method: 'setBlockFormat', value: 'h3' },
        { id: 'bold', method: 'toggleInlineFormat', value: 'strong' },
        { id: 'italic', method: 'toggleInlineFormat', value: 'em' },
        { id: 'unordered-list', method: 'toggleList', value: 'ul' },
        { id: 'ordered-list', method: 'toggleList', value: 'ol' },
        { id: 'link', method: 'insertLink', value: null },
        { id: 'remove-format', method: 'removeFormat', value: null },
        { id: 'undo', method: 'undo', value: null },
        { id: 'redo', method: 'redo', value: null },
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

test('Werkzeugleiste und Standard-Linkdialog übernehmen injizierte zugängliche Kontextnamen', () => {
    const toolbar = createPhilosophyToolbar({
        document: createDocument(),
        selection: createSelectionAdapter(),
        accessibleName: 'Werkzeuge für englischen Philosophie-Text',
        linkDialogLabels: {
            title: 'Link für englischen Philosophie-Text einfügen',
            url: 'HTTPS-Adresse für englischen Philosophie-Text',
            submit: 'Link für englischen Philosophie-Text einfügen',
        },
    });

    assert.equal(toolbar.element.getAttribute('aria-label'), 'Werkzeuge für englischen Philosophie-Text');
    assert.equal(toolbar.linkDialog.element.children[0].textContent, 'Link für englischen Philosophie-Text einfügen');
    assert.equal(toolbar.linkDialog.element.children[1].textContent, 'HTTPS-Adresse für englischen Philosophie-Text');
    assert.equal(findByRole(toolbar.linkDialog.element, 'link-submit').textContent, 'Link für englischen Philosophie-Text einfügen');
});

test('Werkzeugleisten-Konstruktor entfernt den Standarddialog bei einem Fehler nach dessen Append', () => {
    const document = createDocument();
    const createElement = document.createElement.bind(document);
    let dialog = null;
    document.createElement = (tagName) => {
        if (tagName === 'button' && dialog && document.body.children.includes(dialog)) {
            throw new Error('Toolbar-Button konnte nicht erstellt werden.');
        }
        const element = createElement(tagName);
        if (tagName === 'dialog') {
            dialog = element;
        }
        return element;
    };

    assert.throws(() => createPhilosophyToolbar({
        document,
        selection: createSelectionAdapter(),
    }), /Toolbar-Button/u);
    assert.equal(document.body.children.includes(dialog), false);
    assert.equal([...dialog.listeners.values()].flat().length, 0);
});

test('Linkdialog-Konstruktor entfernt DOM und Teillistener bei einem Fehler nach Append', () => {
    const document = createDocument();
    const baseCreateElement = document.createElement.bind(document);
    let dialog = null;
    document.createElement = (tagName) => {
        const element = baseCreateElement(tagName);
        if (tagName === 'dialog') {
            dialog = element;
            const addEventListener = element.addEventListener.bind(element);
            element.addEventListener = (type, listener) => {
                addEventListener(type, listener);
                if (type === 'cancel') {
                    throw new Error('Dialog-Listener konnte nicht registriert werden.');
                }
            };
        }
        return element;
    };

    assert.throws(() => createPhilosophyLinkDialog({
        document,
        selection: createSelectionAdapter(),
    }), /Dialog-Listener/u);
    assert.equal(document.body.children.includes(dialog), false);
    assert.equal([...dialog.listeners.values()].flat().length, 0);
});

test('feste Befehle nutzen ausschließlich den Adapter, synchronisieren und fokussieren', () => {
    const document = createDocument();
    const commands = [];
    let changes = 0;
    const visual = new TestElement('div');
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        adapter: {
            setBlockFormat(tag) { commands.push(['setBlockFormat', tag]); },
            toggleInlineFormat(tag) { commands.push(['toggleInlineFormat', tag]); },
        },
        onChange() { changes += 1; },
    });

    toolbar.buttons.get('heading-2').dispatch('click');
    toolbar.buttons.get('bold').dispatch('click');

    assert.deepEqual(commands, [['setBlockFormat', 'h2'], ['toggleInlineFormat', 'strong']]);
    assert.equal(changes, 2);
    assert.equal(visual.focusCalls, 2);
});

test('unbekannte Befehle und freie Werte werden vor dem Adapter verworfen', () => {
    const document = createDocument();
    const calls = [];
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: {
            setBlockFormat(value) { calls.push(value); },
        },
    });

    assert.equal(toolbar.executeCommand('heading-2', 'script'), false);
    assert.equal(toolbar.executeCommand('unbekannt'), false);
    assert.deepEqual(calls, []);
});

test('schneller sequenzieller Doppelklick führt denselben Formatbefehl nur einmal aus', () => {
    const document = createDocument();
    const scheduled = [];
    let commands = 0;
    const toolbar = createPhilosophyToolbar({
        document,
        scheduleMicrotask(callback) { scheduled.push(callback); },
        adapter: {
            toggleInlineFormat(tag) {
                assert.equal(tag, 'strong');
                commands += 1;
            },
        },
    });

    const bold = toolbar.buttons.get('bold');
    bold.dispatch('click', { detail: 1 });
    /* Der Browser leert Microtasks zwischen zwei echten DOM-Click-Ereignissen. */
    scheduled.shift()();
    bold.dispatch('click', { detail: 2 });
    assert.equal(commands, 1);

    bold.dispatch('click', { detail: 1 });
    assert.equal(commands, 2);
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
    const dialog = createPhilosophyLinkDialog({
        document,
        selection: createSelectionAdapter(),
        onInsert(url) { inserted.push(url); },
    });
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
    const dialog = createPhilosophyLinkDialog({
        document,
        selection: createSelectionAdapter(),
        onInsert(url) { inserted.push(url); },
    });
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

test('Abbrechen stellt die gesicherte Auswahl wieder her, fokussiert visuell und führt keinen Linkbefehl aus', () => {
    const document = createDocument();
    const events = [];
    let insertions = 0;
    const visual = new TestElement('div');
    visual.focus = () => { events.push('focus'); };
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        selection: {
            capture() { events.push('capture'); return { range: 'ursprünglich' }; },
            restore(snapshot) { events.push(`restore:${snapshot.range}`); },
        },
        adapter: { insertLink() { insertions += 1; } },
    });
    const cancel = findByRole(toolbar.linkDialog.element, 'link-cancel');

    toolbar.buttons.get('link').dispatch('click');
    cancel.dispatch('click');

    assert.equal(insertions, 0);
    assert.equal(toolbar.linkDialog.element.closeCalls, 1);
    assert.deepEqual(events, ['capture', 'restore:ursprünglich', 'focus']);
});

test('ohne Dialogunterstützung bleibt die Werkzeugleiste nutzbar und deaktiviert nur Links', () => {
    const document = createDocument({ dialogSupported: false });
    const toolbar = createPhilosophyToolbar({ document, adapter: { execute() {} } });

    assert.equal(toolbar.buttons.get('link').disabled, true);
    assert.equal(toolbar.buttons.get('bold').disabled, false);
});

test('Linkbutton schließt den Dialog vor Adapter, Synchronisierung und Visualfokus', () => {
    const document = createDocument();
    const events = [];
    const visual = new TestElement('div');
    visual.focus = () => { events.push('focus'); };
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        selection: {
            capture() { events.push('capture'); return 'range'; },
            restore(snapshot) { events.push(`restore:${snapshot}`); },
        },
        adapter: {
            insertLink(url) {
                events.push(`adapter:${url}`);
                assert.equal(toolbar.linkDialog.element.open, false);
            },
        },
        onChange() { events.push('change'); },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');
    const originalClose = toolbar.linkDialog.element.close.bind(toolbar.linkDialog.element);
    toolbar.linkDialog.element.close = () => {
        events.push('close');
        originalClose();
    };

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');

    assert.deepEqual(events, ['capture', 'close', 'restore:range', 'adapter:https://beispiel.de', 'change', 'focus']);
});

test('zwei Linkdialoge verwenden verschiedene IDs und passende Beschriftungsreferenzen', () => {
    const document = createDocument();
    const first = createPhilosophyLinkDialog({ document, instanceId: 'editor', selection: createSelectionAdapter() });
    const second = createPhilosophyLinkDialog({ document, instanceId: 'editor', selection: createSelectionAdapter() });
    const firstInput = findByRole(first.element, 'link-url');
    const secondInput = findByRole(second.element, 'link-url');

    assert.notEqual(first.element.getAttribute('aria-labelledby'), second.element.getAttribute('aria-labelledby'));
    assert.notEqual(firstInput.getAttribute('id'), secondInput.getAttribute('id'));
    assert.equal(firstInput.getAttribute('aria-describedby').endsWith('-error'), true);
    assert.equal(secondInput.getAttribute('aria-describedby').endsWith('-error'), true);
});

test('fehlgeschlagener Linkbefehl öffnet den Dialog mit Inlinefehler erneut und behält die Auswahl handhabbar', () => {
    const document = createDocument();
    const events = [];
    const toolbar = createPhilosophyToolbar({
        document,
        selection: {
            capture() { events.push('capture'); return 'range'; },
            restore(value) { events.push(`restore:${value}`); },
        },
        adapter: {
            insertLink() { events.push('adapter'); return false; },
        },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');
    const error = findByRole(toolbar.linkDialog.element, 'link-error');

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');

    assert.deepEqual(events, ['capture', 'restore:range', 'adapter']);
    assert.equal(toolbar.linkDialog.element.open, true);
    assert.match(error.textContent, /nicht eingefügt/u);
    assert.equal(input.getAttribute('aria-invalid'), 'true');
    assert.equal(input.focusCalls, 2);
});

test('geworfene Linkadapterfehler öffnen den Dialog ohne Erfolgsmeldung erneut', () => {
    const document = createDocument();
    const toolbar = createPhilosophyToolbar({
        document,
        selection: createSelectionAdapter(),
        adapter: {
            insertLink() { throw new Error('Editorfehler'); },
        },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');
    const error = findByRole(toolbar.linkDialog.element, 'link-error');

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');

    assert.equal(toolbar.linkDialog.element.open, true);
    assert.match(error.textContent, /nicht eingefügt/u);
});

test('asynchrone Linkadapter schließen erst nach Erfolg endgültig und blockieren Doppelklicks', async () => {
    const document = createDocument();
    let resolveInsert;
    let insertions = 0;
    let changes = 0;
    const pendingInsert = new Promise((resolve) => { resolveInsert = resolve; });
    const toolbar = createPhilosophyToolbar({
        document,
        selection: createSelectionAdapter(),
        adapter: {
            insertLink() {
                insertions += 1;
                return pendingInsert;
            },
        },
        onChange() { changes += 1; },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');
    submit.dispatch('click');

    assert.equal(toolbar.linkDialog.element.open, false);
    assert.equal(toolbar.buttons.get('link').disabled, true);
    assert.equal(toolbar.buttons.get('link').getAttribute('aria-disabled'), 'true');
    assert.equal(changes, 0);
    assert.equal(insertions, 1);
    /* Nach der Microtask darf auch ein neuer einzelner Link-Klick keinen Snapshot überschreiben. */
    await Promise.resolve();
    toolbar.buttons.get('link').dispatch('click', { detail: 1 });
    assert.equal(toolbar.linkDialog.open(), false);
    assert.equal(toolbar.linkDialog.element.showModalCalls, 1);
    resolveInsert(true);
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(toolbar.linkDialog.element.open, false);
    assert.equal(toolbar.buttons.get('link').disabled, false);
    assert.equal(toolbar.buttons.get('link').getAttribute('aria-disabled'), 'false');
    assert.equal(changes, 1);
});

test('asynchrone Linkadapter öffnen bei false oder Ablehnung mit einem Fehler erneut', async () => {
    const document = createDocument();
    let resolveFalse;
    const falseResult = new Promise((resolve) => { resolveFalse = resolve; });
    const toolbar = createPhilosophyToolbar({
        document,
        selection: createSelectionAdapter(),
        adapter: { insertLink() { return falseResult; } },
    });
    const input = findByRole(toolbar.linkDialog.element, 'link-url');
    const submit = findByRole(toolbar.linkDialog.element, 'link-submit');
    const error = findByRole(toolbar.linkDialog.element, 'link-error');

    toolbar.buttons.get('link').dispatch('click');
    input.value = 'https://beispiel.de';
    submit.dispatch('click');
    assert.equal(toolbar.linkDialog.element.open, false);
    resolveFalse(false);
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(toolbar.linkDialog.element.open, true);
    assert.match(error.textContent, /nicht eingefügt/u);

    const rejectedDocument = createDocument();
    const rejectedToolbar = createPhilosophyToolbar({
        document: rejectedDocument,
        selection: createSelectionAdapter(),
        adapter: { insertLink() { return Promise.reject(new Error('abgelehnt')); } },
    });
    const rejectedInput = findByRole(rejectedToolbar.linkDialog.element, 'link-url');
    const rejectedSubmit = findByRole(rejectedToolbar.linkDialog.element, 'link-submit');
    rejectedToolbar.buttons.get('link').dispatch('click');
    rejectedInput.value = 'https://beispiel.de';
    rejectedSubmit.dispatch('click');
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(rejectedToolbar.linkDialog.element.open, true);
});

test('öffentliches close bleibt während eines Pending-Links wirkungslos und bewahrt die Auswahl für Fehlerpfade', async () => {
    const runFailurePath = async (result) => {
        const document = createDocument();
        const events = [];
        const dialog = createPhilosophyLinkDialog({
            document,
            selection: {
                capture() { events.push('capture'); return 'range'; },
                restore(snapshot) { events.push(`restore:${snapshot}`); },
            },
            onInsert() { return result; },
        });
        const input = findByRole(dialog.element, 'link-url');
        const submit = findByRole(dialog.element, 'link-submit');

        dialog.open();
        input.value = 'https://beispiel.de';
        submit.dispatch('click');
        assert.equal(dialog.close(), false);
        await Promise.resolve();
        await Promise.resolve();
        assert.equal(dialog.element.open, true);
        assert.equal(dialog.close(), true);
        assert.deepEqual(events, ['capture', 'restore:range', 'restore:range']);
    };

    await runFailurePath(Promise.resolve(false));
    await runFailurePath(Promise.reject(new Error('abgelehnt')));
});

test('Thenables werden abgewartet und blockieren Reentranz bis zur Auflösung', async () => {
    const document = createDocument();
    let resolvePending;
    let calls = 0;
    const pending = new Promise((resolve) => { resolvePending = resolve; });
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: {
            toggleInlineFormat() {
                calls += 1;
                return calls === 1 ? pending : undefined;
            },
        },
    });

    const first = toolbar.executeCommand('bold');
    assert.equal(typeof first.then, 'function');
    assert.equal(toolbar.executeCommand('bold'), false);
    assert.equal(calls, 1);
    resolvePending(true);
    assert.equal(await first, true);
    assert.equal(toolbar.executeCommand('bold'), true);
    assert.equal(calls, 2);
});

test('abgewiesene Thenables schlagen fehl, asynchrone Modusadapter veröffentlichen nach Erfolg', async () => {
    const document = createDocument();
    const rejectedPromise = Promise.reject(new Error('asynchroner Adapterfehler'));
    const modeChanges = [];
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: { toggleInlineFormat() { return rejectedPromise; } },
        sync: { showHtml() { return Promise.resolve({ ok: true }); } },
        onModeChange(mode) { modeChanges.push(mode); },
    });

    assert.equal(await toolbar.executeCommand('bold'), false);
    assert.equal(await toolbar.setMode('html'), true);
    assert.deepEqual(modeChanges, ['html']);
    assert.equal(toolbar.visualButton.getAttribute('aria-pressed'), 'false');
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'true');
});

test('fehlgeschlagene Benachrichtigungen ändern keinen erfolgreichen Command- oder Modusstatus', () => {
    const document = createDocument();
    let adapterCalls = 0;
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: {
            toggleInlineFormat() { adapterCalls += 1; return true; },
        },
        sync: { showHtml() { return { ok: true }; } },
        onChange() { return false; },
        onModeChange() { throw new Error('Nur Benachrichtigung fehlgeschlagen'); },
    });

    assert.equal(toolbar.executeCommand('bold'), true);
    assert.equal(adapterCalls, 1);
    assert.equal(toolbar.setMode('html'), true);
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'true');
});

test('asynchrone Benachrichtigungen halten nur die Sperre und lassen den Mutationserfolg bestehen', async () => {
    const document = createDocument();
    let resolveChange;
    let commandCalls = 0;
    const pendingChange = new Promise((resolve) => { resolveChange = resolve; });
    const toolbar = createPhilosophyToolbar({
        document,
        adapter: {
            toggleInlineFormat() { commandCalls += 1; return true; },
        },
        sync: {
            showHtml() { return { ok: true }; },
        },
        onChange() { return pendingChange; },
        onModeChange() { return Promise.reject(new Error('Benachrichtigung nicht erreichbar')); },
    });

    const commandResult = toolbar.executeCommand('bold');
    assert.equal(typeof commandResult.then, 'function');
    assert.equal(toolbar.executeCommand('bold'), false);
    assert.equal(commandCalls, 1);
    resolveChange(false);
    assert.equal(await commandResult, true);

    assert.equal(await toolbar.setMode('html'), true);
    assert.equal(toolbar.htmlButton.getAttribute('aria-pressed'), 'true');
});

test('Destroy invalidiert ausstehende Toolbar-Befehle und Moduswechsel ohne spätere Nebenwirkung', async () => {
    const document = createDocument();
    const visual = new TestElement('div');
    let resolveCommand;
    let changes = 0;
    const pendingCommand = new Promise((resolve) => { resolveCommand = resolve; });
    const toolbar = createPhilosophyToolbar({
        document,
        visual,
        adapter: { toggleInlineFormat() { return pendingCommand; } },
        onChange() { changes += 1; },
    });
    const commandResult = toolbar.executeCommand('bold');

    toolbar.destroy();
    resolveCommand(true);

    assert.equal(await commandResult, false);
    assert.equal(changes, 0);
    assert.equal(visual.focusCalls, 0);
    assert.equal(toolbar.executeCommand('bold'), false);
    assert.equal(toolbar.setMode('html'), false);

    const modeDocument = createDocument();
    let resolveMode;
    const modeChanges = [];
    const pendingMode = new Promise((resolve) => { resolveMode = resolve; });
    const modeToolbar = createPhilosophyToolbar({
        document: modeDocument,
        sync: { showHtml() { return pendingMode; } },
        onModeChange(mode) { modeChanges.push(mode); },
    });
    const modeResult = modeToolbar.setMode('html');
    modeToolbar.destroy();
    resolveMode({ ok: true });

    assert.equal(await modeResult, false);
    assert.deepEqual(modeChanges, []);
    assert.equal(modeToolbar.visualButton.getAttribute('aria-pressed'), 'true');
    assert.equal(modeToolbar.htmlButton.getAttribute('aria-pressed'), 'false');
});

test('ohne vollständigen Auswahladapter bleibt der Link deaktiviert und der Dialog geschlossen', () => {
    const document = createDocument();
    const noSelection = createPhilosophyToolbar({
        document,
        adapter: { insertLink() { throw new Error('Darf nicht laufen'); } },
    });
    const partialSelection = createPhilosophyToolbar({
        document: createDocument(),
        selection: { capture() { return 'range'; } },
        adapter: { insertLink() { throw new Error('Darf nicht laufen'); } },
    });

    assert.equal(noSelection.buttons.get('link').disabled, true);
    assert.equal(noSelection.linkDialog.open(), false);
    assert.equal(partialSelection.buttons.get('link').disabled, true);
});

test('Linkdialog öffnet ohne echten Selection-Snapshot nicht', () => {
    const dialog = createPhilosophyLinkDialog({
        document: createDocument(),
        selection: { capture() { return null; }, restore() { return true; } },
    });

    assert.equal(dialog.open(), false);
    assert.equal(dialog.element.open, false);
});

test('Destroy hält einen ausstehenden Linkdialog nach später Ablehnung dauerhaft entfernt', async () => {
    const document = createDocument();
    let resolveInsert;
    const pendingInsert = new Promise((resolve) => { resolveInsert = resolve; });
    const dialog = createPhilosophyLinkDialog({
        document,
        selection: createSelectionAdapter(),
        onInsert() { return pendingInsert; },
    });
    const input = findByRole(dialog.element, 'link-url');
    const submit = findByRole(dialog.element, 'link-submit');

    dialog.open();
    input.value = 'https://beispiel.de';
    submit.dispatch('click');
    dialog.destroy();
    resolveInsert(false);
    await Promise.resolve();
    await Promise.resolve();

    assert.equal(document.body.children.includes(dialog.element), false);
    assert.equal(dialog.element.open, false);
    assert.equal(dialog.element.showModalCalls, 1);
    assert.equal(dialog.busy, false);
    assert.equal(dialog.open(), false);
});

test('injizierte Linkdialoge werden anhand ihrer eigenen Sicherheits- und Busy-Capabilities freigegeben', () => {
    const document = createDocument();
    let opens = 0;
    let notifyBusy = () => {};
    const protectedDialog = {
        supported: true,
        selectionReady: true,
        busy: false,
        open() { opens += 1; return true; },
        subscribeBusy(listener) { notifyBusy = listener; return () => { notifyBusy = () => {}; }; },
        destroy() {},
    };
    const protectedToolbar = createPhilosophyToolbar({
        document,
        linkDialog: protectedDialog,
    });
    const unprotectedToolbar = createPhilosophyToolbar({
        document: createDocument(),
        selection: createSelectionAdapter(),
        linkDialog: { supported: true, selectionReady: false, open() { throw new Error('Darf nicht öffnen'); } },
    });

    assert.equal(protectedToolbar.buttons.get('link').disabled, false);
    protectedToolbar.buttons.get('link').dispatch('click');
    assert.equal(opens, 1);
    protectedDialog.busy = true;
    notifyBusy(true);
    assert.equal(protectedToolbar.buttons.get('link').disabled, true);
    assert.equal(protectedToolbar.buttons.get('link').getAttribute('aria-disabled'), 'true');
    protectedDialog.busy = false;
    notifyBusy(false);
    assert.equal(protectedToolbar.buttons.get('link').disabled, false);
    assert.equal(protectedToolbar.buttons.get('link').getAttribute('aria-disabled'), 'false');
    assert.equal(unprotectedToolbar.buttons.get('link').disabled, true);
});

test('öffentliches close stellt die Auswahl wieder her und verwirft sie anschließend', () => {
    const document = createDocument();
    const events = [];
    const dialog = createPhilosophyLinkDialog({
        document,
        selection: {
            capture() { events.push('capture'); return 'range'; },
            restore(value) { events.push(`restore:${value}`); },
        },
    });

    dialog.open();
    assert.equal(dialog.close(), true);
    assert.equal(dialog.open(), true);
    assert.equal(dialog.close(), true);
    assert.deepEqual(events, ['capture', 'restore:range', 'capture', 'restore:range']);
    assert.equal(dialog.element.open, false);
});

test('Dialog verarbeitet Enter und Escape lokal, setzt aria-invalid zurück und entfernt sich beim Destroy', () => {
    const document = createDocument();
    const inserted = [];
    const dialog = createPhilosophyLinkDialog({
        document,
        selection: createSelectionAdapter(),
        onInsert(url) { inserted.push(url); return true; },
    });
    const input = findByRole(dialog.element, 'link-url');
    const submit = findByRole(dialog.element, 'link-submit');

    dialog.open();
    input.value = 'http://unsicher.example';
    submit.dispatch('click');
    assert.equal(input.getAttribute('aria-invalid'), 'true');
    input.dispatch('input');
    assert.equal(input.getAttribute('aria-invalid'), 'false');
    input.value = 'https://beispiel.de';
    input.dispatch('keydown', { key: 'Enter' });
    assert.deepEqual(inserted, ['https://beispiel.de']);

    dialog.open();
    dialog.element.dispatch('cancel');
    assert.equal(dialog.element.open, false);
    dialog.destroy();
    assert.equal(document.body.children.includes(dialog.element), false);
});

test('Toolbar verwendet weder Netz-, Beacon- oder Browser-Speicher-APIs', () => {
    const document = createDocument();
    const calls = { beacon: 0, fetch: 0, storage: 0, webSocket: 0, xhr: 0 };
    class BlockedXmlHttpRequest {
        constructor() { calls.xhr += 1; throw new Error('Nicht erlaubt'); }
    }
    class BlockedWebSocket {
        constructor() { calls.webSocket += 1; throw new Error('Nicht erlaubt'); }
    }
    const replacements = {
        XMLHttpRequest: BlockedXmlHttpRequest,
        WebSocket: BlockedWebSocket,
        fetch: () => { calls.fetch += 1; throw new Error('Nicht erlaubt'); },
        localStorage: new Proxy({}, { get() { calls.storage += 1; throw new Error('Nicht erlaubt'); } }),
        navigator: { sendBeacon() { calls.beacon += 1; throw new Error('Nicht erlaubt'); } },
        sessionStorage: new Proxy({}, { get() { calls.storage += 1; throw new Error('Nicht erlaubt'); } }),
    };
    const originals = new Map();
    for (const [name, replacement] of Object.entries(replacements)) {
        originals.set(name, Object.getOwnPropertyDescriptor(globalThis, name));
        Object.defineProperty(globalThis, name, { configurable: true, value: replacement, writable: true });
    }
    try {
        createPhilosophyToolbar({ document, adapter: { execute() {} } });
        assert.deepEqual(calls, { beacon: 0, fetch: 0, storage: 0, webSocket: 0, xhr: 0 });
    } finally {
        for (const [name, original] of originals) {
            if (original) {
                Object.defineProperty(globalThis, name, original);
            } else {
                delete globalThis[name];
            }
        }
    }
});
