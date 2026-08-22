import { renderPreviewLabel } from './label-preview.mjs';

const FOKUS_ELEMENTE = 'button:not([disabled]), select:not([disabled]), input:not([disabled]), a[href]';

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
        if (typeof saveLabel !== 'function') {
            message.textContent = 'Die Speicherfunktion wird vorbereitet.';
            return;
        }
        saveButton.disabled = true;
        try {
            await saveLabel({
                assetId: field('asset_id').value,
                status: field('status').value,
                position: field('position').value,
                theme: field('theme').value,
                card: activeCard,
            });
            close();
        } catch {
            message.textContent = 'Die Kennzeichnung konnte nicht gespeichert werden.';
        } finally {
            saveButton.disabled = false;
        }
    });
}
