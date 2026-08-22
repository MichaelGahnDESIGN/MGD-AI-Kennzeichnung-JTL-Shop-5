import assert from 'node:assert/strict';
import test from 'node:test';

import { detectSupportedImageField } from '../../plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/image-field-detector.mjs';

const origin = 'https://shop.test';

test('erkennt lokale Bilder in den vier unterstützten OPC-Kontexten', () => {
    const examples = [
        ['image-portlet', 'bilder/inhalt/produkt.webp'],
        ['container-background-static', '/opc/banner/startseite.jpg'],
        ['banner', 'https://shop.test/media/image/banner/aktion.png'],
        ['slider', 'templates/NOVAChild/img/slide.avif'],
    ];

    for (const [context, value] of examples) {
        const result = detectSupportedImageField({ context, value, visible: true, ambiguous: false }, origin);
        assert.equal(result?.source, 'opc');
        assert.match(result?.localPath ?? '', /\.(?:webp|jpg|png|avif)$/);
    }
});

test('verwirft externe, leere, versteckte, mehrdeutige und aktive Dateien', () => {
    const examples = [
        { context: 'image-portlet', value: 'https://fremd.example/bild.jpg', visible: true },
        { context: 'image-portlet', value: '', visible: true },
        { context: 'image-portlet', value: 'bilder/test.jpg', visible: false },
        { context: 'image-portlet', value: 'bilder/test.jpg', visible: true, ambiguous: true },
        { context: 'image-portlet', value: 'bilder/aktiv.svg', visible: true },
        { context: 'video', value: 'bilder/test.jpg', visible: true },
    ];

    for (const example of examples) {
        assert.equal(detectSupportedImageField(example, origin), null);
    }
});
