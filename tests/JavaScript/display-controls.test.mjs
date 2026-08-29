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
        this.textContent = '';
        this.listeners = new Map();
        this.children = new Map();
        this.classList = new TestClassList();
        this.style = new TestStyle();
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

test('fehlende Strukturen bleiben ohne Crash, Submit oder globale Nebenwirkungen', () => {
    const originalFetch = globalThis.fetch;
    const hadStorage = Object.hasOwn(globalThis, 'localStorage');
    const originalStorage = globalThis.localStorage;

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

    assert.equal(globalThis.fetch, originalFetch);
    assert.equal(Object.hasOwn(globalThis, 'localStorage'), hadStorage);
    assert.equal(globalThis.localStorage, originalStorage);
});
