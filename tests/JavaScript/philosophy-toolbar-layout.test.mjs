import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import test from 'node:test';

// Prüft die ausgelieferte CSS-Datei ohne externe Bibliotheken oder Netzaufrufe.
// Die tatsächlichen Abmessungen werden zusätzlich im JTL-Backend geprüft.
const css = readFileSync(new URL('../../plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css', import.meta.url), 'utf8');

test('Die geänderte Werkzeugleiste erhält eine neue lokale CSS-Cachekennung', () => {
    const template = readFileSync(new URL('../../plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl', import.meta.url), 'utf8');
    const version = createHash('sha256').update(css).digest('hex').slice(0, 12);
    assert.ok(template.includes(`philosophy.css?v=${version}"`), 'CSS-Inhaltskennung im Template muss zum aktuellen Stylesheet passen.');
});

function declarations(selector) {
    const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const matches = [...css.matchAll(new RegExp(`${escaped}\\s*\\{([^}]+)\\}`, 'g'))];
    assert.ok(matches.length > 0, `CSS-Regel fehlt: ${selector}`);
    return matches.map((match) => match[1]).join('\n');
}

test('Editor-Werkzeuge bleiben kompakt statt große Formularbuttons zu übernehmen', () => {
    const buttons = declarations('.mgd-philosophy-toolbar button');
    assert.match(buttons, /min-height:\s*2rem;/);
    assert.match(buttons, /min-width:\s*2rem;/);
    assert.match(buttons, /padding:\s*0\.25rem 0\.5rem;/);
    assert.match(buttons, /font-size:\s*0\.875rem;/);
    assert.match(buttons, /line-height:\s*1\.25;/);
    assert.match(declarations('.mgd-philosophy-toolbar'), /gap:\s*0\.25rem;/);
});

test('Weiße Sprachkarten erhalten auch im dunklen Backend lesbare Überschriften', () => {
    assert.match(declarations('.mgd-philosophy-language h2'), /color:\s*#17202a;/);
});

test('Große Schreibfläche, Speichern-Button und Tastaturfokus bleiben erhalten', () => {
    assert.match(declarations('.mgd-philosophy-form button'), /min-height:\s*2\.75rem;/);
    assert.match(declarations('.mgd-philosophy-language textarea'), /min-height:\s*22\.5rem;/);
    assert.match(css, /button:focus-visible/);
    assert.match(declarations('.mgd-philosophy-toolbar'), /flex-wrap:\s*wrap;/);
});
