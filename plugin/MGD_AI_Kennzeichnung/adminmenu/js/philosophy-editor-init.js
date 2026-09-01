/*
 * JTL-AJAX-Starter für den lokalen Philosophie-Editor.
 *
 * JTL lädt die Inhalte eines Plugin-Tabs nachträglich in die Administrations-
 * seite. Ein direkt im Fragment stehendes `type="module"`-Skript wird dabei
 * nicht zuverlässig ausgeführt. Dieses klassische Einstiegsskript wird von
 * JTL dagegen ausgewertet und lädt anschließend ausschließlich die beiden am
 * Formular hinterlegten, lokalen Plugin-Assets.
 */
'use strict';

(() => {
    const documentAdapter = globalThis.document;
    const locationAdapter = globalThis.location;
    if (!documentAdapter || !locationAdapter || typeof documentAdapter.querySelectorAll !== 'function') {
        return;
    }

    const expectedModuleSuffix = '/plugins/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs';
    const expectedStylesheetSuffix = '/plugins/MGD_AI_Kennzeichnung/adminmenu/philosophy.css';

    /**
     * Akzeptiert ausschließlich eine Ressource derselben Shop-Domain und mit
     * dem erwarteten Pluginpfad. Manipulierte data-Attribute können dadurch
     * weder fremde Skripte noch fremde Stylesheets nachladen.
     */
    const resolveOwnedUrl = (value, expectedSuffix) => {
        if (typeof value !== 'string' || value === '') {
            return null;
        }
        try {
            const url = new URL(value, documentAdapter.baseURI);
            if (
                url.origin !== locationAdapter.origin
                || url.username !== ''
                || url.password !== ''
                || !url.pathname.endsWith(expectedSuffix)
            ) {
                return null;
            }
            return url;
        } catch {
            return null;
        }
    };

    /** Zeigt einen lokalen Fallback an; die normalen Textfelder bleiben offen. */
    const showFallback = (form) => {
        if (form.querySelector('[data-mgd-philosophy-role="loader-fallback"]')) {
            return;
        }
        const status = documentAdapter.createElement('p');
        status.setAttribute('role', 'status');
        status.setAttribute('data-mgd-philosophy-role', 'loader-fallback');
        status.textContent = 'Der erweiterte Editor konnte nicht gestartet werden. Die Textfelder bleiben nutzbar.';
        form.prepend(status);
    };

    /** Bindet das lokale Stylesheet höchstens einmal in die Adminseite ein. */
    const ensureStylesheet = (stylesheetUrl) => {
        const existing = Array.from(documentAdapter.querySelectorAll('link[rel="stylesheet"]'))
            .some((link) => link.href === stylesheetUrl.href);
        if (existing) {
            return;
        }
        const link = documentAdapter.createElement('link');
        link.rel = 'stylesheet';
        link.href = stylesheetUrl.href;
        link.setAttribute('data-mgd-philosophy-asset', 'stylesheet');
        documentAdapter.head.appendChild(link);
    };

    for (const form of Array.from(documentAdapter.querySelectorAll('[data-philosophy-form]'))) {
        if (form.getAttribute('data-mgd-philosophy-loader') !== null) {
            continue;
        }

        const moduleUrl = resolveOwnedUrl(form.getAttribute('data-philosophy-module'), expectedModuleSuffix);
        const stylesheetUrl = resolveOwnedUrl(
            form.getAttribute('data-philosophy-stylesheet'),
            expectedStylesheetSuffix,
        );
        if (!moduleUrl || !stylesheetUrl) {
            showFallback(form);
            continue;
        }

        form.setAttribute('data-mgd-philosophy-loader', 'pending');
        ensureStylesheet(stylesheetUrl);
        import(moduleUrl.href).then((editorModule) => {
            if (typeof editorModule.initializePhilosophyEditors !== 'function') {
                throw new Error('Das lokale Editor-Modul besitzt keinen gültigen Einstieg.');
            }
            editorModule.initializePhilosophyEditors({ document: documentAdapter });
            form.setAttribute('data-mgd-philosophy-loader', 'ready');
        }).catch(() => {
            form.setAttribute('data-mgd-philosophy-loader', 'failed');
            showFallback(form);
        });
    }
})();
