import { createAdminIoClient } from './admin-io-client.mjs';
import { classifyDomField, detectSupportedImageField } from './image-field-detector.mjs';
import { createLabelDialog } from './label-dialog.mjs';

const INSTALLATION = Symbol.for('mgd.aiKennzeichnung.opcInstallation');

function isVisible(field) {
    return !field.disabled
        && field.type !== 'hidden'
        && field.getClientRects().length > 0
        && window.getComputedStyle(field).visibility !== 'hidden';
}

function descriptor(field) {
    return {
        context: classifyDomField(field),
        value: field.value,
        visible: isVisible(field),
        ambiguous: false,
    };
}

/**
 * Ergänzt nur eindeutig erkannte, sichtbare OPC-Bildfelder. Bei jeder Aktion
 * wird der aktuelle Feldwert erneut geprüft, damit kein vorheriges Bild wirkt.
 */
export function initializeOpcIntegration(editorBaseUrl) {
    if (window[INSTALLATION]) {
        return window[INSTALLATION];
    }

    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = new URL('editor.css', editorBaseUrl).href;
    document.head.append(style);

    const dialog = createLabelDialog(createAdminIoClient());
    const connected = new WeakSet();
    let scanScheduled = false;

    const connectField = (field) => {
        if (connected.has(field)) {
            return;
        }
        const detected = detectSupportedImageField(descriptor(field), window.opc?.shopUrl ?? window.location.origin);
        if (detected === null) {
            return;
        }
        connected.add(field);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mgd-opc-label-button';
        button.textContent = 'KI-Kennzeichnung bearbeiten';
        button.addEventListener('click', () => {
            const current = detectSupportedImageField(descriptor(field), window.opc?.shopUrl ?? window.location.origin);
            if (current === null) {
                button.textContent = 'Aktuelles Bild ist nicht eindeutig';
                return;
            }
            dialog.open(current.localPath, button);
        });
        field.insertAdjacentElement('afterend', button);
    };

    const scan = () => {
        scanScheduled = false;
        document.querySelectorAll('input, select').forEach(connectField);
    };
    const scheduleScan = () => {
        if (scanScheduled) {
            return;
        }
        scanScheduled = true;
        window.requestAnimationFrame(scan);
    };
    const observer = new MutationObserver(scheduleScan);
    observer.observe(document.body, { childList: true, subtree: true });
    document.addEventListener('change', scheduleScan, true);
    scheduleScan();

    const installation = Object.freeze({
        destroy() {
            observer.disconnect();
            document.removeEventListener('change', scheduleScan, true);
            dialog.destroy();
            style.remove();
            delete window[INSTALLATION];
        },
    });
    window[INSTALLATION] = installation;

    return installation;
}
