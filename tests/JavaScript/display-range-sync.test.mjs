import assert from 'node:assert/strict';
import test from 'node:test';

import { bindNumberAndRange, normalizeInteger } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-range-sync.mjs';

/** Ein minimales Eingabefeld, um die ereignisbasierte Kopplung ohne Browser zu prüfen. */
class TestInput {
    constructor(value) {
        this.value = value;
        this.listeners = new Map();
    }

    addEventListener(type, listener) {
        this.listeners.set(type, listener);
    }

    removeEventListener(type, listener) {
        if (this.listeners.get(type) === listener) {
            this.listeners.delete(type);
        }
    }

    dispatch(type) {
        this.listeners.get(type)?.();
    }
}

test('normalizeInteger akzeptiert ausschließlich ganze Zahlen im erlaubten Bereich', () => {
    assert.equal(normalizeInteger('5', 0, 32, 4), 5);
    assert.equal(normalizeInteger('0', 0, 32, 4), 0);
    assert.equal(normalizeInteger('32', 0, 32, 4), 32);
});

test('normalizeInteger verwendet bei ungültigen Eingaben den sicheren Standardwert', () => {
    assert.equal(normalizeInteger('-1', 0, 32, 4), 4);
    assert.equal(normalizeInteger('33', 0, 32, 4), 4);
    assert.equal(normalizeInteger('5px', 0, 32, 4), 4);
    assert.equal(normalizeInteger('', 0, 32, 4), 4);
    assert.equal(normalizeInteger('5.0', 0, 32, 4), 4);
    assert.equal(normalizeInteger('05', 0, 32, 4), 4);
    assert.equal(normalizeInteger(5, 0, 32, 4), 4);
});

test('normalizeInteger bereinigt auch fehlerhafte Grenzen und Standardwerte sicher', () => {
    assert.equal(normalizeInteger('5', 8, 4, 12), 8);
    assert.equal(normalizeInteger('5', 0, 32, 99), 0);
    assert.equal(normalizeInteger('5', -1, 32, 4), 0);
});

test('bindNumberAndRange gleicht beide Eingaben bei input und change ohne Seiteneffekte ab', () => {
    const numberInput = new TestInput('5');
    const rangeInput = new TestInput('2');
    const removeListeners = bindNumberAndRange(numberInput, rangeInput, 0, 32, 4);

    assert.equal(rangeInput.value, '5');

    rangeInput.value = '7';
    rangeInput.dispatch('input');
    assert.equal(numberInput.value, '7');

    numberInput.value = 'ungültig';
    numberInput.dispatch('change');
    assert.equal(numberInput.value, '4');
    assert.equal(rangeInput.value, '4');

    removeListeners();
    rangeInput.value = '8';
    rangeInput.dispatch('change');
    assert.equal(numberInput.value, '4');
});
