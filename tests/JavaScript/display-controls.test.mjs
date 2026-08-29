import assert from 'node:assert/strict';
import test from 'node:test';

import { initializeDisplayControls } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-controls.mjs';

/** Minimaler, browserunabhängiger Ersatz für classList der Vorschau. */
class TestClassList {
    constructor() {
        this.values = new Set();
    }

    add(...values) {
        for (const value of values) {
            this.values.add(value);
        }
    }

    remove(...values) {
        for (const value of values) {
            this.values.delete(value);
        }
    }
}

/** Minimaler Style-Speicher mit Zähler, um doppelte Vorschauupdates zu erkennen. */
class TestStyle {
    constructor() {
        this.values = new Map();
        this.writeCount = 0;
    }

    setProperty(property, value) {
        this.values.set(property, value);
        this.writeCount += 1;
    }
}

/** Repräsentiert ein fokussiertes Formular- oder Vorschau-Element. */
class TestElement {
    constructor(value = '') {
        this.value = value;
        this._textContent = '';
        this.textContentWrites = 0;
        this.innerHTMLWrites = 0;
        this.listeners = new Map();
        this.children = new Map();
        this.classList = new TestClassList();
        this.style = new TestStyle();
    }

    get textContent() {
        return this._textContent;
    }

    set textContent(value) {
        this._textContent = value;
        this.textContentWrites += 1;
    }

    set innerHTML(value) {
        this.innerHTMLWrites += 1;
        throw new Error(`innerHTML darf nicht gesetzt werden: ${value}`);
    }

    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) ?? [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }

    removeEventListener(type, listener) {
        this.listeners.set(type, (this.listeners.get(type) ?? []).filter((candidate) => candidate !== listener));
    }

    querySelector(selector) {
        return this.children.get(selector) ?? null;
    }

    dispatch(type) {
        for (const listener of this.listeners.get(type) ?? []) {
            listener();
        }
    }
}

/** Baut ausschließlich die vom Controller dokumentierte data-mgd-Struktur nach. */
function createDisplayRoot() {
    const root = new TestElement();
    const form = new TestElement();
    form.submitCalls = 0;
    form.submit = () => {
        form.submitCalls += 1;
    };
    form.requestSubmit = () => {
        form.submitCalls += 1;
    };
    const preview = new TestElement();
    const label = new TestElement();
    const language = new TestElement('de');
    const fontSize = new TestElement('12');
    const outerMargin = new TestElement('8');
    const innerPadding = new TestElement('6');
    const borderRadiusNumber = new TestElement('4');
    const borderRadiusRange = new TestElement('4');
    const blurNumber = new TestElement('0');
    const blurRange = new TestElement('0');
    const transparencyNumber = new TestElement('8');
    const transparencyRange = new TestElement('8');
    const position = new TestElement('top-right');
    const theme = new TestElement('auto');

    root.children.set('[data-mgd-display-form]', form);
    root.children.set('[data-mgd-display-preview]', preview);
    root.children.set('[data-mgd-display-label]', label);
    root.children.set('[data-mgd-display-preview-position]', position);
    root.children.set('[data-mgd-display-preview-theme]', theme);

    form.children.set('[data-mgd-display-control="language"]', language);
    form.children.set('[data-mgd-display-control="font_size"]', fontSize);
    form.children.set('[data-mgd-display-control="outer_margin"]', outerMargin);
    form.children.set('[data-mgd-display-control="inner_padding"]', innerPadding);
    form.children.set('[data-mgd-number][data-mgd-setting="borderRadius"]', borderRadiusNumber);
    form.children.set('[data-mgd-range][data-mgd-setting="borderRadius"]', borderRadiusRange);
    form.children.set('[data-mgd-number][data-mgd-setting="blur"]', blurNumber);
    form.children.set('[data-mgd-range][data-mgd-setting="blur"]', blurRange);
    form.children.set('[data-mgd-number][data-mgd-setting="transparency"]', transparencyNumber);
    form.children.set('[data-mgd-range][data-mgd-setting="transparency"]', transparencyRange);

    return {
        root,
        form,
        preview,
        label,
        theme,
        borderRadiusNumber,
        borderRadiusRange,
    };
}

test('Range und Zahlenfeld synchronisieren bei input und change jeweils genau einmal die Vorschau', () => {
    const { root, form, preview, borderRadiusNumber, borderRadiusRange } = createDisplayRoot();
    initializeDisplayControls(root);

    const initialWrites = preview.style.writeCount;
    borderRadiusRange.value = '8';
    borderRadiusRange.dispatch('input');
    assert.equal(borderRadiusNumber.value, '8');
    assert.equal(preview.style.values.get('--mgd-preview-border-radius'), '8px');
    assert.equal(preview.style.writeCount, initialWrites + 6);

    borderRadiusRange.value = '9';
    borderRadiusRange.dispatch('change');
    assert.equal(borderRadiusNumber.value, '9');
    assert.equal(preview.style.values.get('--mgd-preview-border-radius'), '9px');

    borderRadiusNumber.value = '10';
    borderRadiusNumber.dispatch('input');
    assert.equal(borderRadiusRange.value, '10');
    assert.equal(preview.style.values.get('--mgd-preview-border-radius'), '10px');

    borderRadiusNumber.value = '11';
    borderRadiusNumber.dispatch('change');
    assert.equal(borderRadiusRange.value, '11');
    assert.equal(preview.style.values.get('--mgd-preview-border-radius'), '11px');
    assert.equal(form.submitCalls, 0);
});

/** Ersetzt eine globale API mit einem Spy und stellt ihren ursprünglichen Deskriptor wieder her. */
function replaceGlobalProperty(name, value) {
    const originalDescriptor = Object.getOwnPropertyDescriptor(globalThis, name);
    Object.defineProperty(globalThis, name, { configurable: true, value, writable: true });

    return () => {
        if (originalDescriptor) {
            Object.defineProperty(globalThis, name, originalDescriptor);
        } else {
            delete globalThis[name];
        }
    };
}

test('Designänderungen kündigen keinen unveränderten Text an und verwenden keine globalen APIs', () => {
    const fetchCalls = { count: 0 };
    const storageCalls = { get: 0, set: 0, setItem: 0 };
    const storageSpy = new Proxy({}, {
        get(target, property, receiver) {
            storageCalls.get += 1;
            if (property === 'setItem') {
                return () => {
                    storageCalls.setItem += 1;
                };
            }

            return Reflect.get(target, property, receiver);
        },
        set(target, property, value, receiver) {
            storageCalls.set += 1;

            return Reflect.set(target, property, value, receiver);
        },
    });
    const restoreFetch = replaceGlobalProperty('fetch', () => {
        fetchCalls.count += 1;
        throw new Error('fetch darf nicht aufgerufen werden.');
    });
    const restoreStorage = replaceGlobalProperty('localStorage', storageSpy);
    const { root, label, theme } = createDisplayRoot();

    try {
        initializeDisplayControls(root);
        const textWritesAfterInitialization = label.textContentWrites;

        theme.value = 'dark';
        theme.dispatch('input');

        assert.equal(label.textContent, 'KI-GENERIERT');
        assert.equal(label.textContentWrites, textWritesAfterInitialization);
        assert.equal(label.innerHTMLWrites, 0);
        assert.equal(fetchCalls.count, 0);
        assert.deepEqual(storageCalls, { get: 0, set: 0, setItem: 0 });
    } finally {
        restoreStorage();
        restoreFetch();
    }
});

test('fehlende Strukturen bleiben ohne Crash, Submit oder globale Nebenwirkungen', () => {
    assert.doesNotThrow(() => initializeDisplayControls(null));
    assert.doesNotThrow(() => initializeDisplayControls({}));
    assert.doesNotThrow(() => initializeDisplayControls(new TestElement()));

    const incompleteRoot = new TestElement();
    incompleteRoot.children.set('[data-mgd-display-form]', new TestElement());
    assert.doesNotThrow(() => initializeDisplayControls(incompleteRoot));

    const rootWithoutPairs = new TestElement();
    rootWithoutPairs.children.set('[data-mgd-display-form]', new TestElement());
    rootWithoutPairs.children.set('[data-mgd-display-preview]', new TestElement());
    rootWithoutPairs.children.set('[data-mgd-display-label]', new TestElement());
    assert.doesNotThrow(() => initializeDisplayControls(rootWithoutPairs));

});

test('Cleanup entfernt alle lokalen Listener der Paar- und Vorschau-Steuerung', () => {
    const { root, preview, borderRadiusNumber, borderRadiusRange, theme } = createDisplayRoot();
    const removeListeners = initializeDisplayControls(root);
    const writesBeforeCleanup = preview.style.writeCount;

    removeListeners();
    borderRadiusRange.value = '12';
    borderRadiusRange.dispatch('input');
    theme.value = 'dark';
    theme.dispatch('change');

    assert.equal(borderRadiusNumber.value, '4');
    assert.equal(preview.style.writeCount, writesBeforeCleanup);
});

test('Modul startet lokale Wurzeln erst nach DOMContentLoaded', async () => {
    const { root, label, preview } = createDisplayRoot();
    const listeners = new Map();
    const fakeDocument = {
        readyState: 'loading',
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
        querySelectorAll(selector) {
            return selector === '[data-mgd-display-root]' ? [root] : [];
        },
    };
    const restoreDocument = replaceGlobalProperty('document', fakeDocument);

    try {
        const moduleUrl = new URL('../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-controls.mjs', import.meta.url);
        await import(`${moduleUrl.href}?dom-ready-test=${Date.now()}`);

        assert.equal(preview.style.writeCount, 0);
        assert.equal(typeof listeners.get('DOMContentLoaded'), 'function');
        listeners.get('DOMContentLoaded')();
        assert.equal(label.textContent, 'KI-GENERIERT');
        assert.equal(preview.style.writeCount, 6);
    } finally {
        restoreDocument();
    }
});
