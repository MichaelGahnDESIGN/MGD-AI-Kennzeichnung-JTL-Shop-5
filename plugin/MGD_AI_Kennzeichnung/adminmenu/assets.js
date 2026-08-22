/*
 * Kleiner Einstieg für die Bildverwaltung. Die eigentliche Logik liegt in
 * getrennten, lokal geladenen Modulen und wird pro URL genau einmal importiert.
 */
'use strict';

const moduleImports = new Map();

function importOnce(url) {
    if (typeof url !== 'string' || url === '') {
        return Promise.reject(new TypeError('Eine lokale Modul-URL fehlt.'));
    }
    if (!moduleImports.has(url)) {
        moduleImports.set(url, import(url));
    }

    return moduleImports.get(url);
}

function initializeBulkFields(root) {
    ['status', 'position', 'theme'].forEach((field) => {
        const checkbox = root.querySelector(`[name="mask[${field}]"]`);
        const target = root.querySelector(`[name="values[${field}]"]`);
        if (!(checkbox instanceof HTMLInputElement) || !(target instanceof HTMLSelectElement)) {
            return;
        }
        const synchronize = () => {
            target.disabled = !checkbox.checked;
        };
        checkbox.addEventListener('change', synchronize);
        synchronize();
    });
}

const root = document.querySelector('[data-mgd-assets]');
if (root instanceof HTMLElement) {
    initializeBulkFields(root);
    Promise.all([
        importOnce(root.dataset.previewModuleUrl),
        importOnce(root.dataset.dialogModuleUrl),
        importOnce(root.dataset.selectionModuleUrl),
    ]).then(([, dialogModule, selectionModule]) => {
        dialogModule.initializeLabelDialog(root);
        selectionModule.initializeGallerySelection(root);
    }).catch(() => {
        const message = root.querySelector('[data-label-message]');
        if (message) {
            message.textContent = 'Die Bedienmodule konnten nicht geladen werden. Bitte laden Sie die Seite neu.';
        }
    });
}
