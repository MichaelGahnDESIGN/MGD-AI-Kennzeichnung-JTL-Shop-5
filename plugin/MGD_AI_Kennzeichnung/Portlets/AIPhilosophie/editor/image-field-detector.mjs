const KONTEXTE = new Set(['image-portlet', 'container-background-static', 'banner', 'slider']);
const WURZELN = ['media/image/', 'bilder/', 'opc/', 'templates/'];
const DATEIENDUNGEN = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif']);

function normalizeLocalPath(value, shopOrigin) {
    if (typeof value !== 'string' || value.trim() === '' || typeof shopOrigin !== 'string') {
        return null;
    }
    let path = value.trim();
    try {
        if (/^https?:\/\//i.test(path)) {
            const url = new URL(path);
            if (url.origin !== new URL(shopOrigin).origin || url.search !== '' || url.hash !== '') {
                return null;
            }
            path = decodeURIComponent(url.pathname);
        } else {
            if (path.startsWith('//') || path.includes('?') || path.includes('#')) {
                return null;
            }
            path = decodeURIComponent(path);
        }
    } catch {
        return null;
    }

    path = path.replace(/^\/+/, '');
    if (path === '' || path.includes('\\') || path.includes('\0') || path.includes('//')) {
        return null;
    }
    const segments = path.split('/');
    if (segments.some((segment) => segment === '' || segment === '.' || segment === '..')) {
        return null;
    }
    if (!WURZELN.some((root) => path.startsWith(root))) {
        return null;
    }
    const extension = path.slice(path.lastIndexOf('.') + 1).toLowerCase();
    if (!DATEIENDUNGEN.has(extension)) {
        return null;
    }

    return path;
}

/**
 * Prüft die bereits aus dem sichtbaren JTL-Feld extrahierten Daten. Die
 * endgültige serverseitige Prüfung bleibt trotz dieser Vorprüfung maßgeblich.
 */
export function detectSupportedImageField(field, shopOrigin) {
    if (!field || field.visible !== true || field.ambiguous === true || !KONTEXTE.has(field.context)) {
        return null;
    }
    const localPath = normalizeLocalPath(field.value, shopOrigin);
    if (localPath === null) {
        return null;
    }

    return Object.freeze({ localPath, source: 'opc', context: field.context });
}

/** Ermittelt konservativ den Kontext eines sichtbaren JTL-Eingabefeldes. */
export function classifyDomField(field) {
    const fieldGroup = field.closest('.form-group, .form-row, [class*="property"], [class*="prop-"]')
        ?? field.parentElement;
    const text = [field.name, field.id, field.dataset?.property, field.getAttribute('aria-label'), fieldGroup?.textContent]
        .filter((value) => typeof value === 'string')
        .join(' ')
        .toLowerCase();
    if (/(video|youtube|vimeo)/.test(text)) {
        return null;
    }
    if (/(hintergrund|background)/.test(text) && !/(parallax|video)/.test(text)) {
        return 'container-background-static';
    }
    if (/(slider|slide)/.test(text)) {
        return 'slider';
    }
    if (/(banner)/.test(text)) {
        return 'banner';
    }
    if (/(bild|image|src|datei|file)/.test(text)) {
        return 'image-portlet';
    }

    return null;
}
