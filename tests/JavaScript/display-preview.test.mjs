import assert from 'node:assert/strict';
import test from 'node:test';

import { createPreviewModel } from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-preview.mjs';

test('createPreviewModel erstellt die fest definierte Vorschau für erlaubte Einstellungen', () => {
    assert.deepEqual(createPreviewModel({
        language: 'de', position: 'bottom-right', theme: 'dark',
        fontSize: '12', outerMargin: '8', innerPadding: '6',
        borderRadius: '5', blur: '15', transparency: '20',
    }), {
        text: 'KI-GENERIERT',
        positionClass: 'mgd-display-preview--bottom-right',
        themeClass: 'mgd-display-preview--theme-dark',
        styles: {
            '--mgd-preview-font-size': '12px',
            '--mgd-preview-outer-margin': '8px',
            '--mgd-preview-inner-padding': '6px',
            '--mgd-preview-border-radius': '5px',
            '--mgd-preview-blur': '15px',
            '--mgd-preview-background-opacity': '0.80',
        },
    });
});

test('createPreviewModel verwendet für manipulierte Werte ausschließlich feste Fallbacks', () => {
    assert.deepEqual(createPreviewModel({
        language: '<img>', position: 'bottom-center hacked', theme: 'neon',
        fontSize: '7', outerMargin: '65', innerPadding: 'x',
        borderRadius: '33', blur: '25', transparency: '91',
    }), {
        text: 'KI-GENERIERT',
        positionClass: 'mgd-display-preview--top-right',
        themeClass: 'mgd-display-preview--theme-auto',
        styles: {
            '--mgd-preview-font-size': '12px',
            '--mgd-preview-outer-margin': '8px',
            '--mgd-preview-inner-padding': '6px',
            '--mgd-preview-border-radius': '4px',
            '--mgd-preview-blur': '0px',
            '--mgd-preview-background-opacity': '0.92',
        },
    });
});

test('createPreviewModel übersetzt Englisch und berechnet transparente Hintergründe exakt', () => {
    const zeroPercent = createPreviewModel({ language: 'en', transparency: '0' });
    const ninetyPercent = createPreviewModel({ language: 'auto', transparency: '90' });

    assert.equal(zeroPercent.text, 'AI-GENERATED');
    assert.equal(zeroPercent.styles['--mgd-preview-background-opacity'], '1.00');
    assert.equal(ninetyPercent.text, 'KI-GENERIERT');
    assert.equal(ninetyPercent.styles['--mgd-preview-background-opacity'], '0.10');
});

test('createPreviewModel bleibt auch ohne Werteobjekt bei den sicheren Standardwerten', () => {
    const model = createPreviewModel(null);

    assert.equal(model.text, 'KI-GENERIERT');
    assert.equal(model.positionClass, 'mgd-display-preview--top-right');
    assert.equal(model.themeClass, 'mgd-display-preview--theme-auto');
});
