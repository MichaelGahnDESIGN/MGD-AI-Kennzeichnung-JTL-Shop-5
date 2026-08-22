import assert from 'node:assert/strict';
import test from 'node:test';

import {
    inspectFileManagerWindow,
    resolveSelectedLocalImage,
} from '../../plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/file-manager-compatibility.mjs';

function managerWindow(origin = 'https://shop.test') {
    const manager = {
        selected: () => ['hash-1'],
        file: () => ({ mime: 'image/png', url: 'https://shop.test/opc/banner/test.png' }),
    };
    const root = {};

    return {
        closed: false,
        location: { origin },
        document: { querySelector: (selector) => selector === '#elfinder' ? root : null },
        jQuery: () => ({ elfinder: (command) => command === 'instance' ? manager : null }),
        manager,
    };
}

test('akzeptiert ausschließlich das eindeutig erkannte gleichnamige elFinder-Fenster', () => {
    const valid = managerWindow();
    assert.equal(inspectFileManagerWindow(valid, 'https://shop.test')?.manager, valid.manager);
    assert.equal(inspectFileManagerWindow(managerWindow('https://fremd.example'), 'https://shop.test'), null);
    assert.equal(inspectFileManagerWindow({ ...valid, closed: true }, 'https://shop.test'), null);
    assert.equal(inspectFileManagerWindow({ ...valid, document: { querySelector: () => null } }, 'https://shop.test'), null);
});

test('liefert nur genau eine lokale Rasterbilddatei', () => {
    const valid = managerWindow().manager;
    assert.equal(resolveSelectedLocalImage(valid, 'https://shop.test')?.localPath, 'opc/banner/test.png');

    const invalid = [
        { selected: () => [], file: () => null },
        { selected: () => ['1', '2'], file: () => null },
        { selected: () => ['1'], file: () => ({ mime: 'directory', url: 'https://shop.test/opc/banner/' }) },
        { selected: () => ['1'], file: () => ({ mime: 'text/plain', url: 'https://shop.test/opc/test.txt' }) },
        { selected: () => ['1'], file: () => ({ mime: 'image/png', url: 'https://fremd.example/opc/test.png' }) },
    ];
    invalid.forEach((manager) => assert.equal(resolveSelectedLocalImage(manager, 'https://shop.test'), null));
});
