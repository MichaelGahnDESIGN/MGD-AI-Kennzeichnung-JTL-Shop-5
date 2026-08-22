/* Offizieller JTL-OPC-Einstieg: lädt ausschließlich das lokale Editor-Modul. */
'use strict';

(() => {
    if (document.currentScript === null) {
        return;
    }
    const scriptUrl = document.currentScript.src;
    if (typeof scriptUrl !== 'string' || scriptUrl === '') {
        return;
    }
    const moduleUrl = new URL('./editor/opc-integration.mjs', scriptUrl);
    import(moduleUrl.href).then(({ initializeOpcIntegration }) => {
        initializeOpcIntegration(new URL('./editor/', scriptUrl).href);
    }).catch(() => {
        /* Der OPC bleibt bei einem Ladeproblem vollständig unverändert bedienbar. */
    });
})();
