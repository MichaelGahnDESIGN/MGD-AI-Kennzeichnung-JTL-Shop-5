import assert from 'node:assert/strict';
import test from 'node:test';

import { normalizeSelectedAssetIds } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/gallery-selection.mjs';

test('normalisiert leere, einzelne und mehrere eindeutige Bild-IDs', () => {
    assert.deepEqual(normalizeSelectedAssetIds([]), []);
    assert.deepEqual(normalizeSelectedAssetIds(['7']), [7]);
    assert.deepEqual(normalizeSelectedAssetIds([1, '2', '02', 3, 3]), [1, 2, 3]);
});

test('verwirft ungültige IDs vollständig', () => {
    assert.deepEqual(normalizeSelectedAssetIds([0, -1, 1.5, '4.2', 'text', '', null, {}, Number.NaN]), []);
});
