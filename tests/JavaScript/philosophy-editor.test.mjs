import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
    createPhilosophyCommandAdapter,
    createNativePhilosophySelection,
    initializePhilosophyEditors,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs';

const ELEMENT_NODE = 1;
const TEXT_NODE = 3;
const DOCUMENT_FRAGMENT_NODE = 11;

/**
 * Kleines Browsermodell für die Integrationsgrenze des Editors.
 * Es bleibt absichtlich auf die verwendeten DOM-, Range- und Selection-APIs
 * begrenzt, damit der Testlauf keine externe Browserbibliothek benötigt.
 */
class TestNode {
    constructor(nodeType, ownerDocument = null) {
        this.nodeType = nodeType;
        this.ownerDocument = ownerDocument;
        this.parentNode = null;
        this.childNodes = [];
    }

    appendChild(node) {
        if (node.nodeType === DOCUMENT_FRAGMENT_NODE) {
            for (const child of [...node.childNodes]) {
                this.appendChild(child);
            }
            return node;
        }
        node.remove();
        node.parentNode = this;
        this.childNodes.push(node);
        return node;
    }

    append(...nodes) {
        for (const node of nodes) {
            this.appendChild(typeof node === 'string' ? this.ownerDocument.createTextNode(node) : node);
        }
    }

    replaceChildren(...nodes) {
        for (const child of this.childNodes) {
            child.parentNode = null;
        }
        this.childNodes = [];
        this.append(...nodes);
    }

    replaceChild(replacement, current) {
        const position = this.childNodes.findIndex((child) => child === current);
        if (position < 0) {
            throw new Error('Der zu ersetzende Knoten gehört nicht zu diesem Elternelement.');
        }
        replacement.remove();
        current.parentNode = null;
        replacement.parentNode = this;
        this.childNodes.splice(position, 1, replacement);
        return current;
    }

    remove() {
        if (!this.parentNode) {
            return;
        }
        this.parentNode.childNodes = this.parentNode.childNodes.filter((child) => child !== this);
        this.parentNode = null;
    }

    contains(node) {
        return node === this || this.childNodes.some((child) => child.contains(node));
    }

    cloneNode(deep = false) {
        const clone = this.nodeType === TEXT_NODE
            ? new TestText(this.data, this.ownerDocument)
            : new TestNode(this.nodeType, this.ownerDocument);
        if (deep) {
            for (const child of this.childNodes) {
                clone.appendChild(child.cloneNode(true));
            }
        }
        return clone;
    }

    get textContent() {
        if (this.nodeType === TEXT_NODE) {
            return this.data;
        }
        return this.childNodes.map((child) => child.textContent).join('');
    }

    set textContent(value) {
        if (this.nodeType === TEXT_NODE) {
            this.data = String(value);
            this.nodeValue = this.data;
            return;
        }
        this.replaceChildren();
        if (String(value) !== '') {
            this.appendChild(this.ownerDocument.createTextNode(String(value)));
        }
    }
}

class TestText extends TestNode {
    constructor(data, ownerDocument) {
        super(TEXT_NODE, ownerDocument);
        this.data = data;
        this.nodeValue = data;
    }

    cloneNode() {
        return new TestText(this.data, this.ownerDocument);
    }
}

class TestElement extends TestNode {
    constructor(name, ownerDocument) {
        super(ELEMENT_NODE, ownerDocument);
        this.localName = name.toLowerCase();
        this.tagName = this.localName.toUpperCase();
        this.attributes = new Map();
        this.listeners = new Map();
        this.style = {};
        this.hidden = false;
        this.disabled = false;
        this.value = '';
        this.focusCalls = 0;
    }

    setAttribute(name, value) {
        this.attributes.set(name, String(value));
    }

    getAttribute(name) {
        return this.attributes.get(name) ?? null;
    }

    removeAttribute(name) {
        this.attributes.delete(name);
    }

    addEventListener(type, listener) {
        this.listeners.set(type, [...(this.listeners.get(type) ?? []), listener]);
    }

    removeEventListener(type, listener) {
        this.listeners.set(type, (this.listeners.get(type) ?? []).filter((entry) => entry !== listener));
    }

    dispatch(type, event = {}) {
        const completeEvent = {
            defaultPrevented: false,
            preventDefault() { this.defaultPrevented = true; },
            ...event,
        };
        for (const listener of [...(this.listeners.get(type) ?? [])]) {
            listener(completeEvent);
        }
        return completeEvent;
    }

    focus() {
        this.focusCalls += 1;
    }

    matches(selector) {
        const attribute = selector.match(/^\[([^=\]]+)(?:="([^"]*)")?\]$/u);
        if (attribute) {
            return this.attributes.has(attribute[1])
                && (attribute[2] === undefined || this.getAttribute(attribute[1]) === attribute[2]);
        }
        return this.localName === selector.toLowerCase();
    }

    querySelectorAll(selector) {
        const result = [];
        for (const child of this.childNodes) {
            if (child.nodeType === ELEMENT_NODE && child.matches(selector)) {
                result.push(child);
            }
            if (typeof child.querySelectorAll === 'function') {
                result.push(...child.querySelectorAll(selector));
            }
        }
        return result;
    }

    querySelector(selector) {
        return this.querySelectorAll(selector)[0] ?? null;
    }

    closest(selector) {
        let current = this;
        while (current) {
            if (current.nodeType === ELEMENT_NODE && current.matches(selector)) {
                return current;
            }
            current = current.parentNode;
        }
        return null;
    }

    cloneNode(deep = false) {
        const clone = new TestElement(this.localName, this.ownerDocument);
        for (const [name, value] of this.attributes) {
            clone.setAttribute(name, value);
        }
        if (deep) {
            for (const child of this.childNodes) {
                clone.appendChild(child.cloneNode(true));
            }
        }
        return clone;
    }
}

class TestDialog extends TestElement {
    constructor(document) {
        super('dialog', document);
        this.open = false;
    }

    showModal() { this.open = true; }
    close() { this.open = false; this.dispatch('close'); }
}

class TestFragment extends TestNode {
    constructor(document) {
        super(DOCUMENT_FRAGMENT_NODE, document);
    }

    cloneNode(deep = false) {
        const clone = new TestFragment(this.ownerDocument);
        if (deep) {
            for (const child of this.childNodes) {
                clone.appendChild(child.cloneNode(true));
            }
        }
        return clone;
    }
}

/** Range-Ersatz für kollabierte Einfügungen und geklonte lokale Auswahlen. */
class TestRange {
    constructor(root) {
        this.startContainer = root;
        this.endContainer = root;
        this.startOffset = root.childNodes.length;
        this.endOffset = root.childNodes.length;
        this.collapsed = true;
    }

    cloneRange() {
        const clone = new TestRange(this.startContainer);
        Object.assign(clone, this);
        return clone;
    }

    deleteContents() {}

    insertNode(node) {
        this.startContainer.appendChild(node);
    }

    extractContents() {
        return this.startContainer.ownerDocument.createDocumentFragment();
    }

    selectNodeContents(node) {
        this.startContainer = node;
        this.endContainer = node;
        this.startOffset = 0;
        this.endOffset = node.childNodes.length;
        this.collapsed = node.childNodes.length === 0;
    }

    setStartAfter(node) {
        this.startContainer = node.parentNode;
        this.endContainer = node.parentNode;
    }

    collapse() { this.collapsed = true; }
}

class TestSelection {
    constructor() {
        this.ranges = [];
    }

    get rangeCount() { return this.ranges.length; }
    getRangeAt(index) { return this.ranges[index]; }
    removeAllRanges() { this.ranges = []; }
    addRange(range) { this.ranges = [range]; }
}

/** Sehr kleiner HTML-Fragmentparser für die bereits bereinigte Positivliste. */
class TestDomParser {
    parseFromString(html) {
        const document = new TestDocument();
        const stack = [document.body];
        const tokens = html.match(/<[^>]+>|[^<]+/gu) ?? [];
        for (const token of tokens) {
            const end = token.match(/^<\/([a-z0-9-]+)>$/iu);
            if (end) {
                const position = stack.findLastIndex((node) => node.localName === end[1].toLowerCase());
                if (position > 0) {
                    stack.length = position;
                }
                continue;
            }
            const start = token.match(/^<([a-z0-9-]+)([^>]*)>$/iu);
            if (start) {
                const element = document.createElement(start[1]);
                for (const match of start[2].matchAll(/([a-z-]+)="([^"]*)"/giu)) {
                    element.setAttribute(match[1], match[2].replaceAll('&amp;', '&'));
                }
                stack.at(-1).appendChild(element);
                stack.push(element);
                continue;
            }
            stack.at(-1).appendChild(document.createTextNode(token));
        }
        return document;
    }
}

class TestDocument extends TestNode {
    constructor() {
        super(9, null);
        this.ownerDocument = this;
        this.body = new TestElement('body', this);
        this.appendChild(this.body);
        this.selection = new TestSelection();
        this.defaultView = {
            DOMParser: TestDomParser,
            getSelection: () => this.selection,
        };
        this.implementation = { createHTMLDocument: () => new TestDocument() };
    }

    createElement(name) {
        return name.toLowerCase() === 'dialog' ? new TestDialog(this) : new TestElement(name, this);
    }

    createTextNode(value) { return new TestText(String(value), this); }
    createDocumentFragment() { return new TestFragment(this); }
    importNode(node, deep) { const clone = node.cloneNode(deep); setOwner(clone, this); return clone; }
    createRange() { return new TestRange(this.body); }
    querySelectorAll(selector) { return this.body.querySelectorAll(selector); }
}

function setOwner(node, document) {
    node.ownerDocument = document;
    for (const child of node.childNodes) {
        setOwner(child, document);
    }
}

/** Bereinigt die Sicherheitsbeispiele deterministisch für die Bootstrap-Tests. */
function sanitizeForTest(value) {
    if (typeof value !== 'string') {
        return '';
    }
    return value
        .slice(0, 10_000)
        .replace(/<script[^>]*>[\s\S]*?<\/script>/giu, '')
        .replace(/\son[a-z]+="[^"]*"/giu, '');
}

function createFixture() {
    const document = new TestDocument();
    const form = document.createElement('form');
    form.setAttribute('data-philosophy-form', '');
    const roots = new Map();

    for (const [language, value] of [['de', '<p>Deutsch</p>'], ['en', '<p>English</p>']]) {
        const root = document.createElement('section');
        root.setAttribute('data-philosophy-language', language);
        const label = document.createElement('label');
        label.setAttribute('data-philosophy-source-label', '');
        label.textContent = language === 'de' ? 'Deutscher Inhalt' : 'Englischer Inhalt';
        const source = document.createElement('textarea');
        source.setAttribute('data-philosophy-source', '');
        source.value = value;
        root.append(label, source);
        form.appendChild(root);
        roots.set(language, { root, source, label });
    }
    document.body.appendChild(form);

    return { document, form, roots };
}

function initialize(fixture, overrides = {}) {
    return initializePhilosophyEditors({
        document: fixture.document,
        sanitize: sanitizeForTest,
        ...overrides,
    });
}

test('initialisiert genau zwei unabhängige Editoren mit zugänglicher lokaler Struktur', () => {
    const fixture = createFixture();
    const controller = initialize(fixture);

    assert.equal(controller.instances.length, 2);
    assert.notEqual(controller.instances[0], controller.instances[1]);
    for (const instance of controller.instances) {
        const sprachAdjektiv = instance.language === 'de' ? 'deutschen' : 'englischen';
        assert.equal(instance.ok, true);
        assert.match(instance.root.querySelector('[data-mgd-philosophy-role="editor"]').getAttribute('id'), new RegExp(`-${instance.language}-`, 'u'));
        assert.equal(instance.toolbar.element.getAttribute('role'), 'toolbar');
        assert.equal(instance.toolbar.element.getAttribute('aria-label'), `Werkzeuge für ${sprachAdjektiv} Philosophie-Text`);
        assert.equal(instance.visual.getAttribute('contenteditable'), 'true');
        assert.equal(instance.visual.getAttribute('role'), 'textbox');
        assert.equal(instance.visual.getAttribute('aria-multiline'), 'true');
        assert.equal(instance.visual.getAttribute('lang'), instance.language);
        assert.match(instance.html.getAttribute('aria-label'), /Deutsch|Englisch/u);
        assert.equal(instance.html.localName, 'textarea');
        assert.equal(instance.html.getAttribute('lang'), instance.language);
        assert.equal(instance.status.getAttribute('role'), 'status');
        assert.equal(instance.status.getAttribute('aria-live'), 'polite');
        assert.equal(instance.status.textContent, 'Visuelle Bearbeitung');
        assert.equal(instance.visual.hidden, false);
        assert.equal(instance.visual.getAttribute('aria-hidden'), 'false');
        assert.equal(instance.html.hidden, true);
        assert.equal(instance.html.getAttribute('aria-hidden'), 'true');
        assert.equal(instance.toolbar.visualButton.getAttribute('aria-pressed'), 'true');
        assert.equal(instance.toolbar.htmlButton.getAttribute('aria-pressed'), 'false');
        assert.equal(
            instance.toolbar.linkDialog.element.childNodes[0].textContent,
            `Link für ${sprachAdjektiv} Philosophie-Text einfügen`,
        );
        assert.equal(
            instance.toolbar.linkDialog.element.childNodes[1].textContent,
            `HTTPS-Adresse für ${sprachAdjektiv} Philosophie-Text`,
        );
    }
    assert.equal(new Set(controller.instances.flatMap((instance) => [
        instance.visual.getAttribute('id'),
        instance.html.getAttribute('id'),
        instance.status.getAttribute('id'),
    ])).size, 6);
});

test('versteckt den Originalwert erst nach Erfolg und isoliert einen Initialisierungsfehler', () => {
    const fixture = createFixture();
    const controller = initialize(fixture, {
        sanitize(value) {
            if (value.includes('Deutsch')) {
                throw new Error('Testfehler');
            }
            return sanitizeForTest(value);
        },
    });

    assert.equal(controller.instances.length, 1);
    assert.equal(fixture.roots.get('de').source.hidden, false);
    assert.equal(fixture.roots.get('de').source.style.display ?? '', '');
    assert.match(fixture.roots.get('de').root.querySelector('[data-mgd-philosophy-role="fallback-status"]').textContent, /nicht gestartet/u);
    assert.equal(fixture.roots.get('en').source.hidden, true);
    assert.equal(fixture.roots.get('en').source.style.display, 'none');
});

test('bewahrt fremde Fallback-Statusknoten bei erfolgreicher Initialisierung', () => {
    const fixture = createFixture();
    const fremderStatus = fixture.document.createElement('p');
    fremderStatus.setAttribute('data-mgd-philosophy-role', 'fallback-status');
    fremderStatus.textContent = 'Status eines fremden Moduls';
    fixture.roots.get('de').root.appendChild(fremderStatus);

    initialize(fixture);

    assert.equal(fixture.roots.get('de').root.contains(fremderStatus), true);
    assert.equal(fremderStatus.textContent, 'Status eines fremden Moduls');
});

test('isoliert einen Fehler beim Abfragen alter eigener Fallback-Statusknoten', () => {
    const fixture = createFixture();
    const deutscheRoot = fixture.roots.get('de').root;
    const querySelectorAll = deutscheRoot.querySelectorAll.bind(deutscheRoot);
    deutscheRoot.querySelectorAll = (selector) => {
        if (selector.includes('fallback-status')) {
            throw new Error('Statusabfrage fehlgeschlagen.');
        }
        return querySelectorAll(selector);
    };
    let controller;

    assert.doesNotThrow(() => { controller = initialize(fixture); });
    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(fixture.roots.get('de').source.hidden, false);
    assert.equal(fixture.roots.get('en').source.hidden, true);
});

test('isoliert einen Fehler beim Erzeugen des lokalen Fallback-Status', () => {
    const fixture = createFixture();
    const createElement = fixture.document.createElement.bind(fixture.document);
    let statusFehler = true;
    fixture.document.createElement = (name) => {
        if (name === 'p' && statusFehler) {
            statusFehler = false;
            throw new Error('Fallback-Status konnte nicht erzeugt werden.');
        }
        return createElement(name);
    };
    let controller;

    assert.doesNotThrow(() => {
        controller = initialize(fixture, {
            sanitize(value) {
                if (value.includes('Deutsch')) {
                    throw new Error('Deutsche Initialisierung fehlgeschlagen.');
                }
                return sanitizeForTest(value);
            },
        });
    });
    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(fixture.roots.get('de').source.hidden, false);
});

test('isoliert einen Fehler beim Anhängen des lokalen Fallback-Status', () => {
    const fixture = createFixture();
    const deutscheRoot = fixture.roots.get('de').root;
    const appendChild = deutscheRoot.appendChild.bind(deutscheRoot);
    deutscheRoot.appendChild = (node) => {
        if (node.getAttribute?.('data-mgd-philosophy-role') === 'fallback-status') {
            throw new Error('Fallback-Status konnte nicht angehängt werden.');
        }
        return appendChild(node);
    };
    let controller;

    assert.doesNotThrow(() => {
        controller = initialize(fixture, {
            sanitize(value) {
                if (value.includes('Deutsch')) {
                    throw new Error('Deutsche Initialisierung fehlgeschlagen.');
                }
                return sanitizeForTest(value);
            },
        });
    });
    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(fixture.roots.get('de').source.hidden, false);
});

test('rollt einen späten Initialisierungsfehler lokal zurück und lässt die Source sichtbar', () => {
    const fixture = createFixture();
    const deutscheSource = fixture.roots.get('de').source;
    const urspruenglicherWert = '<p onclick="x()">Roh</p><script>bleibt im Fallback</script>';
    deutscheSource.value = urspruenglicherWert;
    Object.defineProperty(deutscheSource.style, 'display', {
        configurable: true,
        get() { return ''; },
        set() { throw new Error('Darstellung kann nicht gesetzt werden.'); },
    });

    const controller = initialize(fixture);

    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(deutscheSource.hidden, false);
    assert.equal(deutscheSource.value, urspruenglicherWert);
    assert.equal(fixture.roots.get('de').root.querySelector('[data-mgd-philosophy-role="editor"]'), null);
    assert.match(fixture.roots.get('de').root.querySelector('[data-mgd-philosophy-role="fallback-status"]').textContent, /nicht gestartet/u);
    assert.equal(fixture.document.querySelectorAll('[data-mgd-philosophy-role="link-dialog"]').length, 1);
    assert.equal(fixture.form.listeners.get('submit').length, 1);
});

test('rollt den Sourcewert auch bei einem Fehler direkt nach der ersten Kanonisierung zurück', () => {
    const fixture = createFixture();
    const deutscheSource = fixture.roots.get('de').source;
    const urspruenglicherWert = '<p onclick="x()">Früh</p>';
    deutscheSource.value = urspruenglicherWert;
    let deutscheAufrufe = 0;

    const controller = initialize(fixture, {
        sanitize(value) {
            if (String(value).includes('Früh')) {
                deutscheAufrufe += 1;
                if (deutscheAufrufe === 2) {
                    throw new Error('Zweite Bereinigung fehlgeschlagen.');
                }
            }
            return sanitizeForTest(value);
        },
    });

    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(deutscheSource.value, urspruenglicherWert);
    assert.equal(deutscheSource.hidden, false);
    assert.equal(fixture.roots.get('de').root.querySelector('[data-mgd-philosophy-role="editor"]'), null);
});

test('räumt einen Standarddialog auf, wenn der Toolbar-Aufbau nach dessen Append scheitert', () => {
    const fixture = createFixture();
    const deutscheSource = fixture.roots.get('de').source;
    const urspruenglicherWert = '<p onclick="x()">Dialogfehler</p>';
    deutscheSource.value = urspruenglicherWert;
    const createElement = fixture.document.createElement.bind(fixture.document);
    const dialogs = [];
    let einmalWerfen = true;
    fixture.document.createElement = (name) => {
        if (name === 'button'
            && einmalWerfen
            && dialogs.length > 0
            && fixture.document.body.contains(dialogs[0])) {
            einmalWerfen = false;
            throw new Error('Toolbar-Aufbau fehlgeschlagen.');
        }
        const element = createElement(name);
        if (name === 'dialog') {
            dialogs.push(element);
        }
        return element;
    };

    const controller = initialize(fixture);

    assert.equal(controller.instances.length, 1);
    assert.equal(controller.instances[0].language, 'en');
    assert.equal(deutscheSource.value, urspruenglicherWert);
    assert.equal(deutscheSource.hidden, false);
    assert.equal(fixture.document.querySelectorAll('[data-mgd-philosophy-role="link-dialog"]').length, 1);
    assert.equal([...dialogs[0].listeners.values()].flat().length, 0);
});

test('initialisiert source, HTML und Visual vor SourceSync mit demselben kanonischen Wert', () => {
    const fixture = createFixture();
    fixture.roots.get('de').source.value = '<p onclick="x()">Sicher</p><script>weg()</script>';
    const controller = initialize(fixture);
    const deutsch = controller.instances.find((instance) => instance.language === 'de');

    assert.equal(deutsch.source.value, '<p>Sicher</p>');
    assert.equal(deutsch.html.value, '<p>Sicher</p>');
    assert.equal(deutsch.visual.textContent, 'Sicher');
    assert.equal(deutsch.sync.currentMode(), 'visual');
});

test('Visual-Input synchronisiert ohne den aktiven DOM-Zweig unnötig zu ersetzen', () => {
    const fixture = createFixture();
    const [instance] = initialize(fixture).instances;
    const paragraph = instance.visual.childNodes[0];

    instance.visual.dispatch('input');

    /* Boolescher Vergleich verhindert bei Rot ein riesiges Dumpen des zyklischen Test-DOMs. */
    assert.equal(instance.visual.childNodes[0] === paragraph, true);
    assert.equal(instance.source.value, '<p>Deutsch</p>');
});

test('Visual-Input ersetzt einen nicht kanonischen DOM-Zweig durch sichere explizite Knoten', () => {
    const fixture = createFixture();
    const [instance] = initialize(fixture).instances;
    const unsafeParagraph = instance.visual.childNodes[0];
    unsafeParagraph.setAttribute('onclick', 'x()');

    instance.visual.dispatch('input');

    assert.equal(instance.source.value, '<p>Deutsch</p>');
    assert.equal(instance.visual.childNodes[0] === unsafeParagraph, false);
    assert.equal(instance.visual.childNodes[0].getAttribute('onclick'), null);
});

test('HTML-Input hält die aktive Eingabe roh, aber Source und inaktive Visualansicht sicher', () => {
    const fixture = createFixture();
    const [instance] = initialize(fixture).instances;
    instance.toolbar.htmlButton.dispatch('click');
    const rawHtml = '<h2 onclick="x()">Neu</h2><script>weg()</script>';
    instance.html.value = rawHtml;

    instance.html.dispatch('input');

    assert.equal(instance.source.value, '<h2>Neu</h2>');
    assert.equal(instance.html.value, rawHtml);
    assert.equal(instance.visual.textContent, 'Neu');
    assert.equal(instance.status.textContent, 'HTML-Quelltext');

    fixture.form.dispatch('submit');
    assert.equal(instance.html.value, '<h2>Neu</h2>');
});

test('HTML-Input lässt ein schrittweise begonnenes Tag bis zum Moduswechsel bearbeitbar', () => {
    const fixture = createFixture();
    const [instance] = initialize(fixture, {
        sanitize(value) {
            return value === '<' ? '&lt;' : sanitizeForTest(value);
        },
    }).instances;
    instance.toolbar.htmlButton.dispatch('click');
    instance.html.value = '<';

    instance.html.dispatch('input');

    assert.equal(instance.html.value, '<');
    assert.equal(instance.source.value, '&lt;');
    instance.toolbar.visualButton.dispatch('click');
    assert.equal(instance.html.value, '&lt;');
});

test('bereinigt HTML-Paste, escaped Text-Paste und verwirft aktiven Inhalt', () => {
    const fixture = createFixture();
    const [instance] = initialize(fixture).instances;
    fixture.document.selection.addRange(new TestRange(instance.visual));

    const htmlPaste = instance.visual.dispatch('paste', {
        clipboardData: {
            getData(type) {
                return type === 'text/html'
                    ? '<p onclick="x()">Neu</p><script>weg()</script>'
                    : '<strong>Darf wegen HTML nicht verwendet werden</strong>';
            },
        },
    });
    assert.equal(htmlPaste.defaultPrevented, true);
    assert.equal(instance.source.value.includes('script'), false);
    assert.equal(instance.source.value.includes('onclick'), false);
    assert.match(instance.source.value, /<p>Neu<\/p>/u);
    assert.equal(instance.source.value.includes('Darf wegen HTML'), false);

    fixture.document.selection.addRange(new TestRange(instance.visual));
    instance.visual.dispatch('paste', {
        clipboardData: { getData(type) { return type === 'text/plain' ? '<strong>Nur Text</strong>' : ''; } },
    });
    assert.match(instance.source.value, /&lt;strong&gt;Nur Text&lt;\/strong&gt;/u);
});

test('Paste mit fremder Selection bleibt fail-closed und verändert keinen Formularwert', () => {
    const fixture = createFixture();
    const [deutsch, englisch] = initialize(fixture).instances;
    const sourceVorher = deutsch.source.value;
    fixture.document.selection.addRange(new TestRange(englisch.visual));

    const paste = deutsch.visual.dispatch('paste', {
        clipboardData: { getData() { return '<p>Fremd</p>'; } },
    });

    assert.equal(paste.defaultPrevented, true);
    assert.equal(deutsch.source.value, sourceVorher);
    assert.match(deutsch.status.textContent, /sichere Auswahl/u);
    assert.ok(deutsch.visual.focusCalls > 0);
});

test('wechselt Modus konsistent und synchronisiert beide Instanzen beim einzigen Submit-Handler', () => {
    const fixture = createFixture();
    const controller = initialize(fixture);
    const [deutsch, englisch] = controller.instances;
    deutsch.toolbar.htmlButton.dispatch('click');

    assert.equal(deutsch.html.hidden, false);
    assert.equal(deutsch.html.style.display, 'block');
    assert.equal(deutsch.visual.hidden, true);
    assert.equal(deutsch.status.textContent, 'HTML-Quelltext');

    let deutschSubmit = 0;
    let englischSubmit = 0;
    const deutschPrepare = deutsch.prepareSubmit;
    const englischPrepare = englisch.prepareSubmit;
    deutsch.prepareSubmit = () => { deutschSubmit += 1; return deutschPrepare(); };
    englisch.prepareSubmit = () => { englischSubmit += 1; return englischPrepare(); };
    const submit = fixture.form.dispatch('submit');

    assert.equal(submit.defaultPrevented, false);
    assert.deepEqual([deutschSubmit, englischSubmit], [1, 1]);
    assert.equal(fixture.form.listeners.get('submit').length, 1);
});

test('blockiert Submit fail-closed, wenn eine Instanz nicht sicher vorbereitet werden kann', () => {
    const fixture = createFixture();
    const controller = initialize(fixture);
    const [deutsch, englisch] = controller.instances;
    let englishPrepared = false;
    deutsch.prepareSubmit = () => ({ ok: false, value: '' });
    englisch.prepareSubmit = () => { englishPrepared = true; return { ok: true, value: englisch.source.value }; };

    const submit = fixture.form.dispatch('submit');

    assert.equal(submit.defaultPrevented, true);
    assert.equal(englishPrepared, true);
    assert.match(deutsch.status.textContent, /nicht gespeichert/u);
    assert.ok(deutsch.visual.focusCalls > 0);
});

test('Selection-Snapshots bleiben vollständig auf den eigenen Visualroot begrenzt', () => {
    const fixture = createFixture();
    const controller = initialize(fixture);
    const [deutsch, englisch] = controller.instances;
    const adapter = createNativePhilosophySelection({
        visual: deutsch.visual,
        document: fixture.document,
    });

    fixture.document.selection.addRange(new TestRange(englisch.visual));
    assert.equal(adapter.capture(), null);

    const localRange = new TestRange(deutsch.visual);
    fixture.document.selection.addRange(localRange);
    const snapshot = adapter.capture();
    assert.notEqual(snapshot, localRange);
    assert.equal(adapter.restore(snapshot), true);
    assert.equal(fixture.document.selection.getRangeAt(0), snapshot);

    fixture.document.selection.ranges = [new TestRange(deutsch.visual), new TestRange(deutsch.visual)];
    assert.equal(adapter.capture(), null);

    snapshot.endContainer = englisch.visual;
    assert.equal(adapter.restore(snapshot), false);
});

test('Commandadapter akzeptiert nur feste Tags und arbeitet ausschließlich auf lokalen Ranges', () => {
    const fixture = createFixture();
    const [deutsch, englisch] = initialize(fixture).instances;
    const adapter = createPhilosophyCommandAdapter({
        visual: deutsch.visual,
        document: fixture.document,
    });

    const foreignRange = new TestRange(englisch.visual);
    foreignRange.collapsed = false;
    fixture.document.selection.addRange(foreignRange);
    assert.equal(adapter.toggleInlineFormat('strong'), false);

    const localRange = new TestRange(deutsch.visual);
    localRange.collapsed = false;
    fixture.document.selection.addRange(localRange);
    assert.equal(adapter.setBlockFormat('section'), false);
    assert.equal(adapter.toggleInlineFormat('span'), false);
    assert.equal(adapter.toggleList('menu'), false);
    assert.equal(adapter.insertLink('http://example.test'), false);
    assert.equal(adapter.toggleInlineFormat('strong'), true);
    assert.equal(deutsch.visual.childNodes.at(-1).localName, 'strong');

    fixture.document.queryCommandSupported = (command) => command === 'undo' || command === 'redo';
    const history = [];
    fixture.document.execCommand = (command, showUi, value) => {
        history.push([command, showUi, value]);
        return true;
    };
    fixture.document.selection.addRange(foreignRange);
    assert.equal(adapter.undo(), false);
    assert.deepEqual(history, []);
    fixture.document.selection.removeAllRanges();
    assert.equal(adapter.redo(), false);
    assert.deepEqual(history, []);
    fixture.document.selection.addRange(localRange);
    assert.equal(adapter.undo(), true);
    assert.equal(adapter.redo(), true);
    assert.deepEqual(history, [['undo', false, null], ['redo', false, null]]);
});

test('Blockformat verwendet die native DOM-Austausch-API statt Arraymethoden einer NodeList', () => {
    const fixture = createFixture();
    const [deutsch] = initialize(fixture).instances;
    const paragraph = deutsch.visual.childNodes[0];
    const text = paragraph.childNodes[0];
    const range = new TestRange(text);
    range.endContainer = text;
    range.collapsed = false;
    fixture.document.selection.addRange(range);
    deutsch.visual.childNodes.indexOf = undefined;
    const adapter = createPhilosophyCommandAdapter({
        visual: deutsch.visual,
        document: fixture.document,
    });

    assert.equal(adapter.setBlockFormat('h2'), true);
    assert.equal(deutsch.visual.childNodes[0].localName, 'h2');
    assert.equal(deutsch.visual.childNodes[0].textContent, 'Deutsch');
});

test('Blockformat meldet einen nicht ausführbaren nativen DOM-Austausch fail-closed', () => {
    const fixture = createFixture();
    const [deutsch] = initialize(fixture).instances;
    const paragraph = deutsch.visual.childNodes[0];
    const range = new TestRange(paragraph.childNodes[0]);
    range.endContainer = paragraph.childNodes[0];
    range.collapsed = false;
    fixture.document.selection.addRange(range);
    deutsch.visual.replaceChild = undefined;
    const adapter = createPhilosophyCommandAdapter({
        visual: deutsch.visual,
        document: fixture.document,
    });

    assert.equal(adapter.setBlockFormat('h3'), false);
    assert.equal(deutsch.visual.childNodes[0], paragraph);
    assert.equal(paragraph.textContent, 'Deutsch');
});

test('wiederholte Initialisierung ist idempotent und destroy räumt DOM, Dialoge und Listener auf', () => {
    const fixture = createFixture();
    const first = initialize(fixture);
    const second = initialize(fixture);

    assert.equal(second.instances[0], first.instances[0]);
    assert.equal(fixture.form.listeners.get('submit').length, 1);
    assert.equal(fixture.document.querySelectorAll('[data-mgd-philosophy-role="editor"]').length, 2);

    first.destroy();
    assert.equal(fixture.document.querySelectorAll('[data-mgd-philosophy-role="editor"]').length, 0);
    assert.equal(fixture.document.querySelectorAll('[data-mgd-philosophy-role="link-dialog"]').length, 0);
    assert.equal(fixture.form.listeners.get('submit').length, 0);
    assert.equal(fixture.roots.get('de').source.hidden, false);
    assert.equal(fixture.roots.get('en').source.hidden, false);

    const sourceNachDestroy = fixture.roots.get('de').source.value;
    first.instances[0].visual.textContent = 'Darf nicht mehr synchronisieren';
    first.instances[0].visual.dispatch('input');
    assert.equal(fixture.roots.get('de').source.value, sourceNachDestroy);
    second.destroy();
});

test('Editorintegration besitzt keine globale Netz- oder Speicheranbindung', async () => {
    const source = await readFile(new URL(
        '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs',
        import.meta.url,
    ), 'utf8');

    assert.doesNotMatch(source, /https?:\/\//iu);
    assert.doesNotMatch(source, /\b(?:fetch|XMLHttpRequest|WebSocket|sendBeacon|localStorage|sessionStorage)\b/u);
    assert.doesNotMatch(source, /\b(?:innerHTML|insertAdjacentHTML)\b/u);
    assert.doesNotMatch(source, /globalThis\.[A-Za-z_$][\w$]*\s*=/u);
});
