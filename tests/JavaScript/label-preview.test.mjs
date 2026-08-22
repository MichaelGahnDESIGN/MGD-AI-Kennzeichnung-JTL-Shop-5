import assert from 'node:assert/strict';
import test from 'node:test';

import { createPreviewClassNames } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/label-preview.mjs';

test('erzeugt ausschließlich freigegebene Klassen für Position und Darstellung', () => {
    const positions = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
    const themes = ['auto', 'light', 'dark'];

    for (const position of positions) {
        for (const theme of themes) {
            assert.deepEqual(createPreviewClassNames(position, theme), [
                'mgd-preview-label',
                `mgd-preview-label--${position}`,
                `mgd-preview-label--theme-${theme}`,
            ]);
        }
    }
});

test('weist unbekannte Positionen und Darstellungen zurück', () => {
    assert.throws(() => createPreviewClassNames('center', 'auto'), /Position/);
    assert.throws(() => createPreviewClassNames('top-left', 'transparent'), /Darstellung/);
});
