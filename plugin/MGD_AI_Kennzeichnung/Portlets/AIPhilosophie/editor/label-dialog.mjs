import { renderPreviewLabel } from './label-preview.mjs';

const OPTIONEN = Object.freeze({
    status: [
        ['unreviewed', 'Ungeprüft'], ['none', 'Keine Kennzeichnung'],
        ['generated', 'KI-generiert'], ['partially-generated', 'Teilweise KI-generiert'],
        ['modified', 'KI-bearbeitet'], ['deepfake', 'Deepfake'],
    ],
    position: [
        ['top-left', 'Oben links'], ['top-right', 'Oben rechts'],
        ['bottom-left', 'Unten links'], ['bottom-right', 'Unten rechts'],
    ],
    theme: [['auto', 'Automatisch'], ['light', 'Hell'], ['dark', 'Dunkel']],
});

function element(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className !== '') {
        node.className = className;
    }
    if (text !== '') {
        node.textContent = text;
    }

    return node;
}

function selectField(name, labelText) {
    const wrapper = element('label', 'mgd-opc-dialog__field');
    wrapper.append(element('span', '', labelText));
    const select = element('select');
    select.name = name;
    for (const [value, label] of OPTIONEN[name]) {
        const option = element('option', '', label);
        option.value = value;
        select.append(option);
    }
    wrapper.append(select);

    return { wrapper, select };
}

/** Erstellt einen eigenständigen, explizit speichernden OPC-Dialog. */
export function createLabelDialog(client) {
    const overlay = element('div', 'mgd-opc-dialog');
    overlay.hidden = true;
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'mgd-opc-dialog-title');
    const panel = element('div', 'mgd-opc-dialog__panel');
    const title = element('h2', '', 'KI-Kennzeichnung bearbeiten');
    title.id = 'mgd-opc-dialog-title';
    const hint = element('p', '', 'Gespeichert wird erst nach Klick auf „Kennzeichnung speichern“.');
    const preview = element('div', 'mgd-opc-dialog__preview');
    const fields = {
        status: selectField('status', 'Status'),
        position: selectField('position', 'Position'),
        theme: selectField('theme', 'Darstellung'),
    };
    const form = element('div', 'mgd-opc-dialog__fields');
    Object.values(fields).forEach(({ wrapper }) => form.append(wrapper));
    const message = element('p', 'mgd-opc-dialog__message');
    message.setAttribute('aria-live', 'polite');
    const actions = element('div', 'mgd-opc-dialog__actions');
    const cancel = element('button', 'mgd-opc-button mgd-opc-button--secondary', 'Abbrechen');
    cancel.type = 'button';
    const save = element('button', 'mgd-opc-button mgd-opc-button--primary', 'Kennzeichnung speichern');
    save.type = 'button';
    actions.append(cancel, save);
    panel.append(title, hint, preview, form, message, actions);
    overlay.append(panel);
    document.body.append(overlay);

    let currentPath = null;
    let opener = null;
    const refresh = () => renderPreviewLabel(
        preview,
        fields.status.select.value,
        fields.position.select.value,
        fields.theme.select.value,
    );
    Object.values(fields).forEach(({ select }) => select.addEventListener('change', refresh));

    const close = () => {
        overlay.hidden = true;
        preview.replaceChildren();
        message.textContent = '';
        currentPath = null;
        opener?.focus();
        opener = null;
    };
    cancel.addEventListener('click', close);
    overlay.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });
    save.addEventListener('click', async () => {
        if (currentPath === null || save.disabled) {
            return;
        }
        save.disabled = true;
        message.textContent = 'Kennzeichnung wird gespeichert …';
        try {
            await client.save(
                currentPath,
                'opc',
                fields.status.select.value,
                fields.position.select.value,
                fields.theme.select.value,
            );
            close();
        } catch (error) {
            message.textContent = error instanceof Error ? error.message : 'Die Kennzeichnung konnte nicht gespeichert werden.';
        } finally {
            save.disabled = false;
        }
    });

    return Object.freeze({
        async open(localPath, button) {
            currentPath = localPath;
            opener = button;
            overlay.hidden = false;
            message.textContent = 'Kennzeichnung wird geladen …';
            try {
                const label = await client.load(localPath);
                fields.status.select.value = label.status;
                fields.position.select.value = label.position;
                fields.theme.select.value = label.theme;
                const image = element('img');
                image.alt = 'Vorschau des ausgewählten OPC-Bildes';
                image.src = new URL(`/${localPath}`, window.location.origin).href;
                preview.replaceChildren(image);
                refresh();
                message.textContent = '';
                fields.status.select.focus();
            } catch (error) {
                message.textContent = error instanceof Error ? error.message : 'Die Kennzeichnung konnte nicht geladen werden.';
            }
        },
        destroy() {
            overlay.remove();
        },
    });
}
