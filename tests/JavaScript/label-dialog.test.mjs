import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createExclusiveSaveHandler,
    createSingleUpdatePayload,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/label-dialog.mjs';

test('erzeugt genau das geschützte Formular für einen Datensatz', () => {
    const payload = createSingleUpdatePayload(
        { assetId: '17', status: 'generated', position: 'bottom-right', theme: 'auto' },
        { csrfToken: 'csrf', pluginId: '9', adminMenuId: '4' },
    );

    assert.deepEqual(Object.fromEntries(payload), {
        action: 'single-update',
        csrf_token: 'csrf',
        asset_id: '17',
        'mask[status]': '1',
        'mask[position]': '1',
        'mask[theme]': '1',
        'values[status]': 'generated',
        'values[position]': 'bottom-right',
        'values[theme]': 'auto',
        kPlugin: '9',
        kPluginAdminMenu: '4',
    });
});

test('Doppelklick sendet einmal und Erfolg aktualisiert genau einmal', async () => {
    let resolveRequest;
    let requests = 0;
    let updates = 0;
    const send = () => {
        requests += 1;
        return new Promise((resolve) => { resolveRequest = resolve; });
    };
    const save = createExclusiveSaveHandler(send, () => { updates += 1; });
    const values = { assetId: '17', status: 'generated', position: 'bottom-right', theme: 'auto' };

    const first = save(values);
    const second = save(values);
    assert.strictEqual(first, second);
    assert.equal(requests, 1);
    resolveRequest({ ok: true });
    await first;
    assert.equal(updates, 1);
});

test('Fehler verändert die Kartendaten nicht', async () => {
    let updates = 0;
    const save = createExclusiveSaveHandler(
        async () => { throw new Error('Serverfehler'); },
        () => { updates += 1; },
    );

    await assert.rejects(() => save({ assetId: '17' }), /Serverfehler/);
    assert.equal(updates, 0);
});
