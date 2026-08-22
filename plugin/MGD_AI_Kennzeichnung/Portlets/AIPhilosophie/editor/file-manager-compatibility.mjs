import { detectSupportedImageField } from './image-field-detector.mjs';

/**
 * Kapselt alle versionsabhängigen elFinder-Annahmen. Jede Abweichung führt zu
 * einem unveränderten Dateimanager statt zu einer teilweise aktiven Funktion.
 */
export function inspectFileManagerWindow(windowRef, expectedOrigin) {
    try {
        if (!windowRef || windowRef.closed === true || windowRef.location.origin !== new URL(expectedOrigin).origin) {
            return null;
        }
        const root = windowRef.document?.querySelector('#elfinder');
        if (!root || typeof windowRef.jQuery !== 'function') {
            return null;
        }
        const binding = windowRef.jQuery(root);
        const manager = binding?.elfinder?.('instance');
        if (!manager || typeof manager.selected !== 'function' || typeof manager.file !== 'function') {
            return null;
        }

        return Object.freeze({ document: windowRef.document, manager });
    } catch {
        return null;
    }
}

/** Gibt nur genau eine ausgewählte lokale Rasterbilddatei zurück. */
export function resolveSelectedLocalImage(manager, shopOrigin) {
    try {
        const selected = manager.selected();
        if (!Array.isArray(selected) || selected.length !== 1) {
            return null;
        }
        const file = manager.file(selected[0]);
        if (!file || typeof file.mime !== 'string' || !file.mime.startsWith('image/') || typeof file.url !== 'string') {
            return null;
        }

        return detectSupportedImageField({
            context: 'image-portlet',
            value: file.url,
            visible: true,
            ambiguous: false,
        }, shopOrigin);
    } catch {
        return null;
    }
}

export const FILE_MANAGER_SELECTORS = Object.freeze({
    contextMenus: '.elfinder-contextmenu',
    ownItem: '[data-mgd-ai-file-label]',
});
