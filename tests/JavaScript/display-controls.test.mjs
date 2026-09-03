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
        this.hidden = false;
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
    const detailPreview = new TestElement();
    const detailLabel = new TestElement();
    const detailTransparency = new TestElement();
    const detailBlur = new TestElement();
    const detailOpaque = new TestElement();

    /* Das Detail-Label liegt in seiner eigenen Bühne, Kennwerte liegen daneben. */
    detailPreview.children.set('[data-mgd-detail-label]', detailLabel);
    root.children.set('[data-mgd-detail-preview]', detailPreview);
    root.children.set('[data-mgd-detail-transparency]', detailTransparency);
    root.children.set('[data-mgd-detail-blur]', detailBlur);
    root.children.set('[data-mgd-detail-opaque]', detailOpaque);

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
        language,
        fontSize,
        outerMargin,
        innerPadding,
        blurNumber,
        blurRange,
        transparencyNumber,
        transparencyRange,
        position,
        detailPreview,
        detailLabel,
        detailTransparency,
        detailBlur,
        detailOpaque,
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
    const { root, label, theme, detailLabel, detailTransparency, detailBlur } = createDisplayRoot();

    try {
        initializeDisplayControls(root);
        const textWritesAfterInitialization = label.textContentWrites;
        const detailWritesAfterInitialization = [detailLabel, detailTransparency, detailBlur]
            .map((element) => element.textContentWrites);

        theme.value = 'dark';
        theme.dispatch('input');

        assert.equal(label.textContent, 'KI-GENERIERT');
        assert.equal(label.textContentWrites, textWritesAfterInitialization);
        assert.equal(label.innerHTMLWrites, 0);
        assert.equal(detailLabel.textContent, 'KI-GENERIERT');
        assert.deepEqual([detailLabel, detailTransparency, detailBlur].map((element) => element.textContentWrites),
            detailWritesAfterInitialization);
        assert.equal(detailLabel.innerHTMLWrites, 0);
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
    const { root, preview, borderRadiusNumber, borderRadiusRange, theme, detailPreview, detailLabel } = createDisplayRoot();
    const removeListeners = initializeDisplayControls(root);
    const writesBeforeCleanup = preview.style.writeCount;
    const detailWritesBeforeCleanup = detailPreview.style.writeCount;
    const detailTextWritesBeforeCleanup = detailLabel.textContentWrites;
    assert.equal(detailWritesBeforeCleanup, 6);

    removeListeners();
    borderRadiusRange.value = '12';
    borderRadiusRange.dispatch('input');
    theme.value = 'dark';
    theme.dispatch('change');

    assert.equal(borderRadiusNumber.value, '4');
    assert.equal(preview.style.writeCount, writesBeforeCleanup);
    assert.equal(detailPreview.style.writeCount, detailWritesBeforeCleanup);
    assert.equal(detailLabel.textContentWrites, detailTextWritesBeforeCleanup);
});

test('Detail-Vorschau übernimmt beim Start genau sechs sichere CSS-Werte ohne Positionsklasse', () => {
    const { root, preview, detailPreview, detailLabel, detailTransparency, detailBlur, detailOpaque } = createDisplayRoot();
    detailPreview.classList.add('mgd-detail-preview', 'mgd-display-preview--theme-dark');

    initializeDisplayControls(root);

    assert.deepEqual(Object.fromEntries(detailPreview.style.values), {
        '--mgd-preview-font-size': '12px',
        '--mgd-preview-outer-margin': '8px',
        '--mgd-preview-inner-padding': '6px',
        '--mgd-preview-border-radius': '4px',
        '--mgd-preview-blur': '0px',
        '--mgd-preview-background-opacity': '0.92',
    });
    assert.deepEqual(detailPreview.style.values, preview.style.values);
    assert.equal(detailPreview.style.writeCount, 6);
    assert.deepEqual([...detailPreview.classList.values].sort(), ['mgd-detail-preview', 'mgd-display-preview--theme-auto']);
    assert.equal(detailLabel.textContent, 'KI-GENERIERT');
    assert.equal(detailTransparency.textContent, '8 %');
    assert.equal(detailBlur.textContent, '0 px');
    assert.equal(detailOpaque.hidden, true);
});

test('Detail-Vorschau übernimmt alle sechs CSS-Werte auch nach tatsächlichen Formularevents', () => {
    const fixture = createDisplayRoot();
    initializeDisplayControls(fixture.root);

    for (const [name, value] of Object.entries({
        fontSize: '48', outerMargin: '64', innerPadding: '32', borderRadiusRange: '32',
        blurRange: '12', transparencyRange: '35',
    })) {
        fixture[name].value = value;
        fixture[name].dispatch('input');
    }

    assert.deepEqual(Object.fromEntries(fixture.detailPreview.style.values), {
        '--mgd-preview-font-size': '48px',
        '--mgd-preview-outer-margin': '64px',
        '--mgd-preview-inner-padding': '32px',
        '--mgd-preview-border-radius': '32px',
        '--mgd-preview-blur': '12px',
        '--mgd-preview-background-opacity': '0.65',
    });
    assert.deepEqual(fixture.detailPreview.style.values, fixture.preview.style.values);
    assert.equal(fixture.detailPreview.style.writeCount, 42);
    assert.equal(fixture.detailTransparency.textContent, '35 %');
    assert.equal(fixture.detailBlur.textContent, '12 px');
    assert.equal(fixture.form.submitCalls, 0);
});

test('Transparenz synchronisiert beide Eingaberichtungen an 0 und 90 Prozent samt Deckkraft-Hinweis', () => {
    const { root, preview, detailPreview, transparencyNumber, transparencyRange, detailTransparency, detailOpaque } = createDisplayRoot();
    initializeDisplayControls(root);

    for (const [input, value, alpha, hidden] of [
        [transparencyNumber, '0', '1.00', false],
        [transparencyRange, '90', '0.10', true],
        [transparencyRange, '0', '1.00', false],
        [transparencyNumber, '90', '0.10', true],
    ]) {
        input.value = value;
        input.dispatch(input === transparencyNumber ? 'change' : 'input');
        assert.equal(transparencyNumber.value, value);
        assert.equal(transparencyRange.value, value);
        assert.equal(detailTransparency.textContent, `${value} %`);
        assert.equal(detailOpaque.hidden, hidden);
        assert.equal(detailPreview.style.values.get('--mgd-preview-background-opacity'), alpha);
        assert.deepEqual(detailPreview.style.values, preview.style.values);
    }
});

test('Weichzeichnung synchronisiert beide Eingaberichtungen an 0 und 24 Pixeln', () => {
    const { root, preview, detailPreview, blurNumber, blurRange, detailBlur } = createDisplayRoot();
    initializeDisplayControls(root);

    for (const [input, value] of [[blurRange, '24'], [blurNumber, '0'], [blurNumber, '24'], [blurRange, '0']]) {
        input.value = value;
        input.dispatch(input === blurNumber ? 'input' : 'change');
        assert.equal(blurNumber.value, value);
        assert.equal(blurRange.value, value);
        assert.equal(detailBlur.textContent, `${value} px`);
        assert.equal(detailPreview.style.values.get('--mgd-preview-blur'), `${value}px`);
        assert.deepEqual(detailPreview.style.values, preview.style.values);
    }
});

test('Detail-Text wechselt mit der Sprache zwischen Deutsch und Englisch', () => {
    const { root, label, language, detailLabel } = createDisplayRoot();
    initializeDisplayControls(root);

    for (const [value, text] of [['en', 'AI-GENERATED'], ['de', 'KI-GENERIERT']]) {
        language.value = value;
        language.dispatch('change');
        assert.equal(detailLabel.textContent, text);
        assert.equal(detailLabel.textContent, label.textContent);
    }

    assert.equal(detailLabel.textContentWrites, 3);
    assert.equal(detailLabel.innerHTMLWrites, 0);
});

test('Detail-Vorschau wechselt ausschließlich erlaubte Themes und bleibt unabhängig von der Position', () => {
    const { root, theme, position, detailPreview } = createDisplayRoot();
    detailPreview.classList.add('mgd-detail-preview');
    initializeDisplayControls(root);

    for (const value of ['light', 'dark', 'auto']) {
        theme.value = value;
        theme.dispatch('change');
        position.value = 'bottom-left';
        position.dispatch('input');
        assert.deepEqual([...detailPreview.classList.values].sort(), ['mgd-detail-preview', `mgd-display-preview--theme-${value}`]);
    }
});

test('Manipulierte Detail-Eingaben verwenden nur das validierte gemeinsame Modell', () => {
    const fixture = createDisplayRoot();
    initializeDisplayControls(fixture.root);

    for (const name of ['language', 'theme', 'position', 'fontSize', 'outerMargin', 'innerPadding',
        'borderRadiusNumber', 'blurNumber', 'transparencyNumber']) {
        fixture[name].value = '<img src=x onerror=alert(1)>;background:url(https://invalid.example)';
        fixture[name].dispatch('change');
    }

    assert.equal(fixture.detailLabel.textContent, 'KI-GENERIERT');
    assert.equal(fixture.detailLabel.innerHTMLWrites, 0);
    assert.deepEqual([...fixture.detailPreview.classList.values], ['mgd-display-preview--theme-auto']);
    assert.deepEqual(Object.fromEntries(fixture.detailPreview.style.values), {
        '--mgd-preview-font-size': '12px',
        '--mgd-preview-outer-margin': '8px',
        '--mgd-preview-inner-padding': '6px',
        '--mgd-preview-border-radius': '4px',
        '--mgd-preview-blur': '0px',
        '--mgd-preview-background-opacity': '0.92',
    });
    assert.deepEqual(fixture.detailPreview.style.values, fixture.preview.style.values);
    assert.equal(fixture.detailTransparency.textContent, '8 %');
    assert.equal(fixture.detailBlur.textContent, '0 px');
    assert.equal(fixture.detailOpaque.hidden, true);
    assert.equal(fixture.form.submitCalls, 0);
});

test('Fehlende optionale Detail-Elemente unterbrechen weder Initialisierung noch Produkt-Updates', () => {
    for (const missingSelector of ['[data-mgd-detail-preview]', '[data-mgd-detail-label]',
        '[data-mgd-detail-transparency]', '[data-mgd-detail-blur]', '[data-mgd-detail-opaque]']) {
        const fixture = createDisplayRoot();
        fixture.root.children.delete(missingSelector);
        fixture.detailPreview.children.delete(missingSelector);

        assert.doesNotThrow(() => initializeDisplayControls(fixture.root));
        fixture.blurRange.value = '24';
        assert.doesNotThrow(() => fixture.blurRange.dispatch('input'));
        assert.equal(fixture.preview.style.values.get('--mgd-preview-blur'), '24px');
        assert.equal(fixture.label.textContent, 'KI-GENERIERT');
        assert.equal(fixture.form.submitCalls, 0);

        if (!['[data-mgd-detail-preview]', '[data-mgd-detail-label]'].includes(missingSelector)) {
            assert.equal(fixture.detailPreview.style.values.get('--mgd-preview-blur'), '24px');
            assert.equal(fixture.detailLabel.textContent, 'KI-GENERIERT');
        }
    }
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
