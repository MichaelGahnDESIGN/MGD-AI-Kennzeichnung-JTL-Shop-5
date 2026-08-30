import assert from 'node:assert/strict';
import test from 'node:test';

import { createPhilosophySourceSync } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-source-sync.mjs';

/** Kleiner Ersatz für ein Textarea-Feld ohne Browser-Abhängigkeit. */
class TestTextfeld {
    constructor(value = '') {
        this.value = value;
    }
}

/** Simuliert einen fehlerhaften DOM-Setter, der Zuweisungen still verwirft. */
class StillesTestTextfeld {
    constructor(value = '') {
        this._value = value;
    }

    get value() {
        return this._value;
    }

    set value(_) {
        /* Der Browseradapter signalisiert den Fehler nicht durch eine Ausnahme. */
    }
}

/** Kontrollierter Visualadapter: Er nimmt nur bereits bereinigtes HTML an. */
class TestVisualadapter {
    constructor(serialisiert) {
        this.serialisiert = serialisiert;
        this.gerendert = '';
        this.renderAufrufe = [];
    }

    render(sicheresHtml) {
        this.gerendert = sicheresHtml;
        this.renderAufrufe.push(sicheresHtml);
    }

    serialize() {
        return this.serialisiert ?? this.gerendert;
    }
}

/** Entfernt für diese Synchronisationstests ausschließlich das aktive Skript-Beispiel. */
function bereinigeTestHtml(value) {
    if (typeof value !== 'string') {
        return '';
    }

    return value.replace(/<script[^>]*>[\s\S]*?<\/script>/giu, '');
}

/** Erzeugt eine isolierte Sprachinstanz mit drei klaren Repräsentationen. */
function erstelleInstanz({ source = '', html = '', visual } = {}, overrides = {}) {
    const sourceFeld = new TestTextfeld(source);
    const htmlFeld = new TestTextfeld(html);
    const visualAdapter = new TestVisualadapter(visual);
    const synchronisierung = createPhilosophySourceSync({
        source: sourceFeld,
        html: htmlFeld,
        visual: visualAdapter,
        sanitize: bereinigeTestHtml,
        ...overrides,
    });

    return { sourceFeld, htmlFeld, visualAdapter, synchronisierung };
}

test('showVisual rendert den autoritativen Formularwert visuell', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Sicher</p>',
        html: '<p>Sicher</p>',
    });

    assert.deepEqual(synchronisierung.showVisual(), {
        ok: true,
        mode: 'visual',
        value: '<p>Sicher</p>',
    });
    assert.equal(sourceFeld.value, '<p>Sicher</p>');
    assert.equal(htmlFeld.value, '<p>Sicher</p>');
    assert.equal(visualAdapter.gerendert, '<p>Sicher</p>');
    assert.equal(synchronisierung.currentMode(), 'visual');
});

test('prepareSubmit verwendet vor dem ersten Wechsel den sichtbaren HTML-Wert', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Alt</p>',
        html: '<p>Neu</p><script>unsicher()</script>',
    });

    assert.equal(synchronisierung.currentMode(), 'html');
    assert.deepEqual(synchronisierung.prepareSubmit(), {
        ok: true,
        mode: 'html',
        value: '<p>Neu</p>',
    });
    assert.equal(sourceFeld.value, '<p>Neu</p>');
    assert.equal(htmlFeld.value, '<p>Neu</p>');
    assert.equal(visualAdapter.gerendert, '<p>Neu</p>');
});

test('Wechsel von HTML zu Visual bereinigt und schreibt alle Repräsentationen', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Alt</p>',
    });
    synchronisierung.showHtml();
    htmlFeld.value = '<p>Neu <strong>formatiert</strong></p><script>x()</script>';

    assert.deepEqual(synchronisierung.showVisual(), {
        ok: true,
        mode: 'visual',
        value: '<p>Neu <strong>formatiert</strong></p>',
    });
    assert.equal(sourceFeld.value, '<p>Neu <strong>formatiert</strong></p>');
    assert.equal(htmlFeld.value, '<p>Neu <strong>formatiert</strong></p>');
    assert.equal(visualAdapter.gerendert, '<p>Neu <strong>formatiert</strong></p>');
});

test('prepareSubmit serialisiert den sichtbaren Visualmodus in den Formularwert', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Alt</p>',
        html: '<p>Alt</p>',
        visual: '<h2>Titel</h2>',
    });
    synchronisierung.showVisual();

    assert.deepEqual(synchronisierung.prepareSubmit(), {
        ok: true,
        mode: 'visual',
        value: '<h2>Titel</h2>',
    });
    assert.equal(sourceFeld.value, '<h2>Titel</h2>');
    assert.equal(htmlFeld.value, '<h2>Titel</h2>');
    assert.equal(visualAdapter.gerendert, '<h2>Titel</h2>');
});

test('leere Inhalte bleiben in allen Repräsentationen leer', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz();

    assert.deepEqual(synchronisierung.showVisual(), { ok: true, mode: 'visual', value: '' });
    assert.deepEqual(synchronisierung.showHtml(), { ok: true, mode: 'html', value: '' });
    assert.deepEqual(synchronisierung.prepareSubmit(), { ok: true, mode: 'html', value: '' });
    assert.equal(sourceFeld.value, '');
    assert.equal(htmlFeld.value, '');
    assert.equal(visualAdapter.gerendert, '');
});

test('wiederholte Moduswechsel bewahren den Inhalt ohne Duplikation oder Verlust', () => {
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Einmal</p>',
        html: '<p>Einmal</p>',
    });

    synchronisierung.showVisual();
    synchronisierung.showHtml();
    synchronisierung.showVisual();
    synchronisierung.showHtml();

    assert.equal(sourceFeld.value, '<p>Einmal</p>');
    assert.equal(htmlFeld.value, '<p>Einmal</p>');
    assert.equal(visualAdapter.gerendert, '<p>Einmal</p>');
    assert.deepEqual(visualAdapter.renderAufrufe, [
        '<p>Einmal</p>',
        '<p>Einmal</p>',
        '<p>Einmal</p>',
        '<p>Einmal</p>',
    ]);
});

test('zwei Sprachinstanzen teilen keinen Zustand', () => {
    const deutsch = erstelleInstanz({ source: '<p>Deutsch</p>', html: '<p>Deutsch</p>' });
    const englisch = erstelleInstanz({ source: '<p>English</p>', html: '<p>English</p>' });

    deutsch.synchronisierung.showVisual();
    englisch.synchronisierung.showHtml();
    englisch.htmlFeld.value = '<p>Updated</p>';
    englisch.synchronisierung.showVisual();

    assert.equal(deutsch.sourceFeld.value, '<p>Deutsch</p>');
    assert.equal(deutsch.visualAdapter.gerendert, '<p>Deutsch</p>');
    assert.equal(englisch.sourceFeld.value, '<p>Updated</p>');
    assert.equal(englisch.visualAdapter.gerendert, '<p>Updated</p>');
});

test('Sanitizer- und Adapterfehler schreiben keinen unsicheren Rohwert in das Formular', () => {
    const sanitizerFehler = erstelleInstanz({
        source: '<script>unsicher()</script>',
        html: '<script>unsicher()</script>',
    }, {
        sanitize() {
            throw new Error('Sanitizerfehler');
        },
    });
    assert.deepEqual(sanitizerFehler.synchronisierung.showVisual(), {
        ok: false,
        mode: 'html',
        value: '',
    });
    assert.equal(sanitizerFehler.sourceFeld.value, '');
    assert.equal(sanitizerFehler.htmlFeld.value, '');

    const renderFehler = erstelleInstanz({
        source: '<p>Alt</p>',
        html: '<p>Sicher</p><script>unsicher()</script>',
    }, {
        visual: {
            render() {
                throw new Error('Visualfehler');
            },
            serialize() {
                return '';
            },
        },
    });
    assert.deepEqual(renderFehler.synchronisierung.showVisual(), {
        ok: false,
        mode: 'html',
        value: '<p>Sicher</p>',
    });
    assert.equal(renderFehler.sourceFeld.value, '<p>Sicher</p>');
    assert.equal(renderFehler.htmlFeld.value, '<p>Sicher</p>');

    const sourceBeiHtmlFehler = new TestTextfeld('<p>Sicher</p><script>unsicher()</script>');
    const htmlMitDefektemSetter = {
        get value() {
            return '<p>Sicher</p><script>unsicher()</script>';
        },
        set value(_) {
            throw new Error('HTML-Setterfehler');
        },
    };
    const visualBeiHtmlFehler = new TestVisualadapter();
    const htmlFehler = createPhilosophySourceSync({
        source: sourceBeiHtmlFehler,
        html: htmlMitDefektemSetter,
        visual: visualBeiHtmlFehler,
        sanitize: bereinigeTestHtml,
    });
    assert.deepEqual(htmlFehler.showVisual(), {
        ok: false,
        mode: 'html',
        value: '<p>Sicher</p>',
    });
    assert.equal(sourceBeiHtmlFehler.value, '<p>Sicher</p>');
    assert.equal(visualBeiHtmlFehler.gerendert, '<p>Sicher</p>');

    const serializeFehler = erstelleInstanz({ source: '<p>Alt</p>' }, {
        visual: {
            render() {},
            serialize() {
                throw new Error('Serialisierungsfehler');
            },
        },
    });
    serializeFehler.synchronisierung.showVisual();
    assert.deepEqual(serializeFehler.synchronisierung.prepareSubmit(), {
        ok: false,
        mode: 'visual',
        value: '',
    });
    assert.equal(serializeFehler.sourceFeld.value, '');
});

test('ungültige Sanitizer-Rückgaben und Moduswechsel werden fail-closed behandelt', () => {
    const modeAenderungen = [];
    const { sourceFeld, htmlFeld, visualAdapter, synchronisierung } = erstelleInstanz({
        source: '<p>Alt</p>',
    }, {
        sanitize() {
            return null;
        },
        onModeChange(mode) {
            modeAenderungen.push(mode);
        },
    });

    assert.deepEqual(synchronisierung.showVisual(), { ok: false, mode: 'html', value: '' });
    assert.deepEqual(synchronisierung.showHtml(), { ok: false, mode: 'html', value: '' });
    assert.deepEqual(modeAenderungen, []);
    assert.equal(sourceFeld.value, '');
    assert.equal(htmlFeld.value, '');
    assert.equal(visualAdapter.gerendert, '');
});

test('fehlerhafte Adapter veröffentlichen weder Modus noch Callback und bewahren den sicheren kanonischen Wert', () => {
    const modeAenderungen = [];
    let renderAufrufe = 0;
    let serialisiert = '<p>Frisch</p>';
    const sourceFeld = new TestTextfeld('<p>Alt</p>');
    const htmlFeld = new TestTextfeld('<p>Alt</p>');
    const synchronisierung = createPhilosophySourceSync({
        source: sourceFeld,
        html: htmlFeld,
        sanitize: bereinigeTestHtml,
        visual: {
            render() {
                renderAufrufe += 1;
                if (renderAufrufe > 1) {
                    throw new Error('Visualadapter ist nicht verfügbar');
                }
            },
            serialize() {
                return serialisiert;
            },
        },
        onModeChange(mode) {
            modeAenderungen.push(mode);
        },
    });

    assert.equal(synchronisierung.showVisual().ok, true);
    assert.deepEqual(synchronisierung.showHtml(), {
        ok: false,
        mode: 'visual',
        value: '<p>Frisch</p>',
    });
    assert.equal(synchronisierung.currentMode(), 'visual');
    assert.deepEqual(modeAenderungen, ['visual']);
    assert.equal(sourceFeld.value, '<p>Frisch</p>');
    assert.equal(htmlFeld.value, '<p>Frisch</p>');

    serialisiert = '<p>Veraltet</p><script>unsicher()</script>';
    assert.deepEqual(synchronisierung.prepareSubmit(), {
        ok: false,
        mode: 'visual',
        value: '<p>Frisch</p>',
    });
    assert.equal(sourceFeld.value, '<p>Frisch</p>');
});

test('reentrante Callbacks und Visualadapter werden fail-closed ohne Rekursion abgewiesen', () => {
    const callbackErgebnisse = [];
    let adapterErgebnis;
    let synchronisierung;
    const visual = {
        render() {
            adapterErgebnis = synchronisierung.showHtml();
        },
        serialize() {
            return '<p>Visual</p>';
        },
    };
    synchronisierung = createPhilosophySourceSync({
        source: new TestTextfeld('<p>Start</p>'),
        html: new TestTextfeld('<p>Start</p>'),
        visual,
        sanitize: bereinigeTestHtml,
        onModeChange() {
            callbackErgebnisse.push(synchronisierung.showHtml());
        },
    });

    assert.deepEqual(synchronisierung.showVisual(), {
        ok: true,
        mode: 'visual',
        value: '<p>Start</p>',
    });
    assert.deepEqual(adapterErgebnis, { ok: false, mode: 'html', value: '<p>Start</p>' });
    assert.deepEqual(callbackErgebnisse, [{ ok: false, mode: 'visual', value: '<p>Start</p>' }]);
    assert.equal(synchronisierung.currentMode(), 'visual');
});

test('stille Source- und HTML-Setter werden durch Read-back erkannt und hinterlassen keinen rohen Skriptwert in source', () => {
    const sourceFeld = new StillesTestTextfeld('<p>Alt</p>');
    const htmlFeld = new TestTextfeld('<p>Neu</p><script>unsicher()</script>');
    const visualAdapter = new TestVisualadapter();
    const sourceSetterFehler = createPhilosophySourceSync({
        source: sourceFeld,
        html: htmlFeld,
        visual: visualAdapter,
        sanitize: bereinigeTestHtml,
    });

    assert.deepEqual(sourceSetterFehler.prepareSubmit(), {
        ok: false,
        mode: 'html',
        value: '<p>Neu</p>',
    });
    assert.equal(sourceFeld.value.includes('<script>'), false);

    const sichererSource = new TestTextfeld('<p>Alt</p>');
    const stillesHtml = new StillesTestTextfeld('<p>Neu</p><script>unsicher()</script>');
    const htmlSetterFehler = createPhilosophySourceSync({
        source: sichererSource,
        html: stillesHtml,
        visual: new TestVisualadapter(),
        sanitize: bereinigeTestHtml,
    });

    assert.deepEqual(htmlSetterFehler.prepareSubmit(), {
        ok: false,
        mode: 'html',
        value: '<p>Neu</p>',
    });
    assert.equal(sichererSource.value, '<p>Neu</p>');
    assert.equal(sichererSource.value.includes('<script>'), false);
});
