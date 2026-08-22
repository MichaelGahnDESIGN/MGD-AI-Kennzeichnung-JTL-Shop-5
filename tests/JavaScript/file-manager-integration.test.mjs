import assert from 'node:assert/strict';
import test from 'node:test';

import { installFileManagerIntegration } from '../../plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/file-manager-integration.mjs';

function environment(compatible = true) {
    const appended = [];
    const menu = {
        querySelector: () => appended.at(0) ?? null,
        append: (item) => appended.push(item),
    };
    const manager = {
        selected: () => ['hash'],
        file: () => ({ mime: 'image/png', url: 'https://shop.test/opc/test.png' }),
    };
    const document = {
        body: {},
        querySelector: (selector) => compatible && selector === '#elfinder' ? {} : null,
        querySelectorAll: () => compatible ? [menu] : [],
        createElement: () => ({ dataset: {}, addEventListener() {}, type: '', className: '', textContent: '' }),
    };
    const child = {
        closed: false,
        location: { origin: 'https://shop.test' },
        document,
        jQuery: () => ({ elfinder: () => manager }),
    };
    let closeCheck;
    let disconnects = 0;

    return {
        child,
        appended,
        options: {
            shopOrigin: 'https://shop.test',
            openLabelDialog() {},
            observerFactory: (callback) => ({ observe() { callback(); }, disconnect() { disconnects += 1; } }),
            intervalFactory: (callback) => { closeCheck = callback; return () => {}; },
        },
        close() { child.closed = true; closeCheck(); },
        disconnects: () => disconnects,
    };
}

test('unbekannte Struktur bleibt vollständig unverändert', () => {
    const fake = environment(false);
    assert.equal(installFileManagerIntegration(fake.child, fake.options), false);
    assert.equal(fake.appended.length, 0);
});

test('wiederholte Initialisierung erzeugt einen Menüpunkt und Schließen räumt auf', () => {
    const fake = environment(true);
    const first = installFileManagerIntegration(fake.child, fake.options);
    const second = installFileManagerIntegration(fake.child, fake.options);
    assert.notEqual(first, false);
    assert.strictEqual(first, second);
    assert.equal(fake.appended.length, 1);
    fake.close();
    assert.equal(fake.disconnects(), 1);
});
