import { isSafeHttpsUrl } from './philosophy-sanitizer.mjs';

/**
 * Übernimmt eine Linkadresse nur, wenn sie exakt dem bereits servernah
 * geprüften HTTPS-Vertrag des Philosophie-Sanitizers entspricht.
 *
 * Die Funktion trimmt absichtlich nicht: Leerzeichen, relative Adressen,
 * Zugangsdaten und fremde Ports dürfen nicht durch eine Browser-Normalisierung
 * zu scheinbar sicheren Links werden.
 *
 * @param {unknown} value Unvertrauenswürdige Eingabe aus dem URL-Feld.
 * @returns {string|null} Die unveränderte sichere URL oder `null`.
 */
export function normalizeSecureLink(value) {
    return isSafeHttpsUrl(value) ? value : null;
}

/**
 * Erstellt einen rein lokalen Linkdialog. Die Auswahl im visuellen Editor wird
 * nicht manipuliert, solange der Mensch nicht eine gültige Adresse bestätigt.
 *
 * @param {{
 *   document?: Document|{createElement?: Function, body?: {append?: Function}},
 *   onInsert?: (url: string) => void,
 *   normalizeLink?: (value: unknown) => string|null,
 * }} [options] Kontrollierte Browser- und Callback-Abhängigkeiten.
 * @returns {{
 *   element: HTMLDialogElement|null,
 *   supported: boolean,
 *   open: () => boolean,
 *   close: () => boolean,
 *   destroy: () => void,
 * }} Dialog-Steuerung ohne globale Browser- oder Netzabhängigkeiten.
 */
export function createPhilosophyLinkDialog(options = {}) {
    const configuration = isObject(options) ? options : {};
    const documentAdapter = configuration.document ?? globalThis.document;
    const normalizeLink = typeof configuration.normalizeLink === 'function'
        ? configuration.normalizeLink
        : normalizeSecureLink;
    const onInsert = typeof configuration.onInsert === 'function' ? configuration.onInsert : () => {};

    if (!documentAdapter || typeof documentAdapter.createElement !== 'function') {
        return createUnsupportedDialog();
    }

    const dialog = documentAdapter.createElement('dialog');
    if (!dialog || typeof dialog.showModal !== 'function' || typeof dialog.close !== 'function') {
        return createUnsupportedDialog();
    }

    dialog.setAttribute('aria-labelledby', 'mgd-philosophy-link-title');
    dialog.setAttribute('class', 'mgd-philosophy-link-dialog');
    dialog.setAttribute('data-mgd-philosophy-role', 'link-dialog');

    const title = createElement(documentAdapter, 'h2', 'Link einfügen');
    title.setAttribute('id', 'mgd-philosophy-link-title');
    const label = createElement(documentAdapter, 'label', 'HTTPS-Adresse');
    const input = createElement(documentAdapter, 'input');
    input.setAttribute('type', 'url');
    input.setAttribute('inputmode', 'url');
    input.setAttribute('autocomplete', 'url');
    input.setAttribute('aria-describedby', 'mgd-philosophy-link-error');
    input.setAttribute('data-mgd-philosophy-role', 'link-url');
    label.setAttribute('for', 'mgd-philosophy-link-url');
    input.setAttribute('id', 'mgd-philosophy-link-url');
    const error = createElement(documentAdapter, 'p', '');
    error.setAttribute('id', 'mgd-philosophy-link-error');
    error.setAttribute('role', 'alert');
    error.setAttribute('data-mgd-philosophy-role', 'link-error');
    const actions = createElement(documentAdapter, 'div');
    actions.setAttribute('class', 'mgd-philosophy-link-dialog__actions');
    const cancel = createButton(documentAdapter, 'Abbrechen', 'link-cancel');
    const submit = createButton(documentAdapter, 'Link einfügen', 'link-submit');

    actions.append(cancel, submit);
    dialog.append(title, label, input, error, actions);
    if (documentAdapter.body && typeof documentAdapter.body.append === 'function') {
        documentAdapter.body.append(dialog);
    }

    let insertionInProgress = false;
    let open = false;

    /** Öffnet den Dialog nur einmal; die bestehende Editor-Auswahl bleibt dabei unangetastet. */
    const openDialog = () => {
        if (open || dialog.open) {
            return false;
        }

        error.textContent = '';
        input.value = '';
        try {
            dialog.showModal();
            open = true;
            if (typeof input.focus === 'function') {
                input.focus();
            }

            return true;
        } catch {
            /* Ein nicht öffnbarer nativer Dialog ist sicherheitshalber unbenutzbar. */
            return false;
        }
    };

    /** Schließt ohne Callback und bewahrt somit Auswahl sowie Editorinhalt. */
    const closeDialog = (releaseInsertionLock = true) => {
        if (!open && !dialog.open) {
            return true;
        }
        try {
            dialog.close();
        } catch {
            /* Browserfehler dürfen nie einen Einfügevorgang auslösen. */
            return false;
        }
        if (dialog.open) {
            return false;
        }
        open = false;
        if (releaseInsertionLock) {
            insertionInProgress = false;
        }

        return true;
    };

    /** Bestätigt nur eine einmalige, unveränderte, positiv geprüfte HTTPS-Adresse. */
    const submitLink = (event) => {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (insertionInProgress || (!open && !dialog.open)) {
            return;
        }

        let normalized;
        try {
            normalized = normalizeLink(input.value);
        } catch {
            normalized = null;
        }
        if (typeof normalized !== 'string' || !isSafeHttpsUrl(normalized)) {
            error.textContent = 'Bitte geben Sie eine vollständige sichere HTTPS-Adresse ohne Zugangsdaten oder fremden Port ein.';
            return;
        }

        insertionInProgress = true;
        /* Ein modal geöffneter Dialog macht den übrigen Editor inert. */
        if (!closeDialog(false)) {
            insertionInProgress = false;
            error.textContent = 'Der Linkdialog konnte nicht sicher geschlossen werden.';
            return;
        }
        try {
            onInsert(normalized);
        } catch {
            error.textContent = 'Der Link konnte nicht eingefügt werden. Bitte versuchen Sie es erneut.';
        } finally {
            insertionInProgress = false;
        }
    };

    const cancelLink = (event) => {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        closeDialog();
    };
    const handleClose = () => {
        open = false;
    };

    cancel.addEventListener('click', cancelLink);
    submit.addEventListener('click', submitLink);
    dialog.addEventListener('close', handleClose);

    return {
        element: dialog,
        supported: true,
        open: openDialog,
        close: closeDialog,
        destroy() {
            cancel.removeEventListener('click', cancelLink);
            submit.removeEventListener('click', submitLink);
            dialog.removeEventListener('close', handleClose);
            closeDialog();
        },
    };
}

/** Liefert eine explizit nicht unterstützte Steuerung statt unsicherer Ersatzdialoge. */
function createUnsupportedDialog() {
    return {
        element: null,
        supported: false,
        open: () => false,
        close: () => {},
        destroy: () => {},
    };
}

/** Erstellt Textknoten ausschließlich über `textContent`, niemals über HTML-Zeichenketten. */
function createElement(documentAdapter, tagName, text = null) {
    const element = documentAdapter.createElement(tagName);
    if (text !== null) {
        element.textContent = text;
    }

    return element;
}

/** Alle Aktionen sind echte Nicht-Submit-Buttons und damit formularneutral. */
function createButton(documentAdapter, text, role) {
    const button = createElement(documentAdapter, 'button', text);
    button.setAttribute('type', 'button');
    button.setAttribute('data-mgd-philosophy-role', role);

    return button;
}

function isObject(value) {
    return value !== null && typeof value === 'object';
}
