import { renderPreviewLabel, statusText } from './label-preview.mjs';

const FOKUS_ELEMENTE = 'button:not([disabled]), select:not([disabled]), input:not([disabled]), a[href]';

/** Erstellt das exakt begrenzte POST-Formular für eine Einzelkennzeichnung. */
export function createSingleUpdatePayload(values, context) {
    return new URLSearchParams([
        ['action', 'single-update'],
        ['csrf_token', context.csrfToken],
        ['asset_id', values.assetId],
        ['mask[status]', '1'],
        ['mask[position]', '1'],
        ['mask[theme]', '1'],
        ['values[status]', values.status],
        ['values[position]', values.position],
        ['values[theme]', values.theme],
        ['kPlugin', context.pluginId],
        ['kPluginAdminMenu', context.adminMenuId],
    ]);
}

/**
 * Lässt während einer laufenden Anfrage keine zweite Mutation zu. Mehrere
 * Klicks erhalten dasselbe Promise und lösen daher genau eine Anfrage aus.
 */
export function createExclusiveSaveHandler(send, applySuccess) {
    let inFlight = null;

    return (values) => {
        if (inFlight !== null) {
            return inFlight;
        }
        let request;
        try {
            request = Promise.resolve(send(values));
        } catch (error) {
            request = Promise.reject(error);
        }
        const operation = request.then((result) => {
            if (!result || result.ok !== true) {
                throw new Error('Der Server hat die Speicherung nicht bestätigt.');
            }
            applySuccess(values, result);

            return result;
        }).finally(() => {
            if (inFlight === operation) {
                inFlight = null;
            }
        });
        inFlight = operation;

        return operation;
    };
}

async function sendSingleUpdate(values, context) {
    const response = await fetch(window.location.href, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: createSingleUpdatePayload(values, context),
    });
    if (response.status === 401 || response.status === 403) {
        throw new Error('Die Admin-Sitzung ist abgelaufen oder nicht berechtigt.');
    }
    if (!response.ok) {
        throw new Error('Der Server konnte die Anfrage nicht verarbeiten.');
    }
    const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
    if (!documentCopy.querySelector('[data-mgd-result="success"]')) {
        throw new Error('Der Server hat die Speicherung nicht bestätigt.');
    }

    return { ok: true };
}

function applySavedLabel(values) {
    const card = values.card;
    if (!card) {
        return;
    }
    card.dataset.status = values.status;
    card.dataset.position = values.position;
    card.dataset.theme = values.theme;
    const status = card.querySelector('.mgd-status');
    const statusLabel = card.querySelector('[data-status-text]');
    if (status && statusLabel) {
        [...status.classList].filter((name) => name.startsWith('mgd-status--')).forEach((name) => status.classList.remove(name));
        status.classList.add(`mgd-status--${values.status}`);
        statusLabel.textContent = statusText(values.status);
    }
}

/**
 * Initialisiert genau einen Dialog. Ohne übergebenen Speicheradapter verändert
 * der Speichern-Button bewusst noch keine Daten; die Anbindung folgt separat.
 */
export function initializeLabelDialog(root, saveLabel = null) {
    const dialog = root.querySelector('[data-label-dialog]');
    const form = dialog?.querySelector('[data-label-form]');
    const preview = dialog?.querySelector('[data-label-preview]');
    const message = dialog?.querySelector('[data-label-message]');
    const saveButton = dialog?.querySelector('[data-label-save]');
    if (!dialog || !form || !preview || !message || !saveButton) {
        return;
    }

    let opener = null;
    let activeCard = null;

    const field = (name) => form.elements.namedItem(name);
    const context = {
        csrfToken: field('csrf_token').value,
        pluginId: field('kPlugin').value,
        adminMenuId: field('kPluginAdminMenu').value,
    };
    const persist = saveLabel ?? createExclusiveSaveHandler(
        (values) => sendSingleUpdate(values, context),
        applySavedLabel,
    );
    const refreshPreview = () => {
        renderPreviewLabel(preview, field('status').value, field('position').value, field('theme').value);
    };
    const close = () => {
        dialog.hidden = true;
        preview.replaceChildren();
        message.textContent = '';
        document.body.classList.remove('mgd-dialog-open');
        opener?.focus();
        opener = null;
        activeCard = null;
    };
    const open = (button) => {
        const card = button.closest('[data-asset-card]');
        if (!card) {
            return;
        }
        opener = button;
        activeCard = card;
        field('asset_id').value = card.dataset.assetId ?? '';
        field('status').value = card.dataset.status ?? 'unreviewed';
        field('position').value = card.dataset.position ?? 'bottom-right';
        field('theme').value = card.dataset.theme ?? 'auto';
        preview.replaceChildren();
        const image = card.querySelector('.mgd-asset-card__preview img');
        if (image) {
            const copy = image.cloneNode(true);
            copy.removeAttribute('loading');
            copy.removeAttribute('id');
            preview.append(copy);
        }
        refreshPreview();
        dialog.hidden = false;
        document.body.classList.add('mgd-dialog-open');
        field('status').focus();
    };

    root.querySelectorAll('[data-label-open]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });
    dialog.querySelectorAll('[data-label-close]').forEach((button) => button.addEventListener('click', close));
    ['status', 'position', 'theme'].forEach((name) => field(name).addEventListener('change', refreshPreview));

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        const focusable = [...dialog.querySelectorAll(FOKUS_ELEMENTE)];
        const first = focusable.at(0);
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    saveButton.addEventListener('click', async () => {
        if (saveButton.disabled || !activeCard) {
            return;
        }
        saveButton.disabled = true;
        try {
            await persist({
                assetId: field('asset_id').value,
                status: field('status').value,
                position: field('position').value,
                theme: field('theme').value,
                card: activeCard,
            });
            close();
        } catch (error) {
            message.textContent = error instanceof Error
                ? error.message
                : 'Die Kennzeichnung konnte nicht gespeichert werden.';
        } finally {
            saveButton.disabled = false;
        }
    });
}
