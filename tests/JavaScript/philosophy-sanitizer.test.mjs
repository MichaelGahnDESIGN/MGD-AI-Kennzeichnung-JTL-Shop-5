import assert from 'node:assert/strict';
import test from 'node:test';

import {
    ALLOWED_PHILOSOPHY_ELEMENTS,
    isSafeHttpsUrl,
    sanitizePhilosophyHtml,
} from '../../plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-sanitizer.mjs';

const ELEMENT_NODE = 1;
const TEXT_NODE = 3;
const COMMENT_NODE = 8;

/**
 * Kleiner DOM-Testadapter ohne externe Abhängigkeiten.
 *
 * Er bildet nur die Browser-Schnittstellen ab, die der Sanitizer benötigt.
 * Damit prüfen die Tests die echte Bereinigungslogik und bleiben trotzdem mit
 * dem in WordPress bereits vorhandenen Node-Testlauf ausführbar.
 */
class TestNode {
    constructor(nodeType) {
        this.nodeType = nodeType;
        this.childNodes = [];
        this.parentNode = null;
    }

    appendChild(child) {
        child.parentNode = this;
        this.childNodes.push(child);

        return child;
    }

    append(...children) {
        for (const child of children) {
            this.appendChild(child);
        }
    }
}

class TestTextNode extends TestNode {
    constructor(data) {
        super(TEXT_NODE);
        this.data = data;
        this.nodeValue = data;
    }
}

class TestCommentNode extends TestNode {
    constructor(data) {
        super(COMMENT_NODE);
        this.data = data;
    }
}

class TestElement extends TestNode {
    constructor(name) {
        super(ELEMENT_NODE);
        this.localName = name.toLowerCase();
        this.tagName = this.localName.toUpperCase();
        this.attributes = [];
    }

    getAttribute(name) {
        return this.attributes.find((attribute) => attribute.name === name)?.value ?? null;
    }

    setAttribute(name, value) {
        const vorhandenesAttribut = this.attributes.find((attribute) => attribute.name === name);
        if (vorhandenesAttribut) {
            vorhandenesAttribut.value = String(value);

            return;
        }

        this.attributes.push({ name, value: String(value) });
    }
}

class TestDocument extends TestNode {
    constructor() {
        super(9);
        this.body = new TestElement('body');
        this.appendChild(this.body);
        this.implementation = {
            createHTMLDocument: () => new TestDocument(),
        };
    }

    createElement(name) {
        return new TestElement(name);
    }

    createTextNode(data) {
        return new TestTextNode(data);
    }
}

const VOID_ELEMENTS = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source']);

/** Zerlegt Test-HTML quote-sensitiv wie ein Browser bis zum echten Tagende. */
function tokenizeTestHtml(html) {
    const teile = [];
    let cursor = 0;

    while (cursor < html.length) {
        if (html.startsWith('<!--', cursor)) {
            const ende = html.indexOf('-->', cursor + 4);
            if (ende === -1) {
                teile.push(html.slice(cursor));
                break;
            }
            teile.push(html.slice(cursor, ende + 3));
            cursor = ende + 3;

            continue;
        }

        if (html[cursor] !== '<') {
            const naechsterTag = html.indexOf('<', cursor);
            const ende = naechsterTag === -1 ? html.length : naechsterTag;
            teile.push(html.slice(cursor, ende));
            cursor = ende;

            continue;
        }

        let position = cursor + 1;
        let quote = null;
        while (position < html.length) {
            const zeichen = html[position];
            if (quote !== null) {
                if (zeichen === quote) {
                    quote = null;
                }
            } else if (zeichen === '"' || zeichen === "'") {
                quote = zeichen;
            } else if (zeichen === '>') {
                position += 1;
                break;
            }
            position += 1;
        }

        if (position > html.length || html[position - 1] !== '>') {
            teile.push('<');
            cursor += 1;
        } else {
            teile.push(html.slice(cursor, position));
            cursor = position;
        }
    }

    return teile;
}

function decodeAttributeEntities(value) {
    return decodeTextEntitiesOnce(value, true);
}

/** Bildet genau einen nativen HTML-Entity-Dekodierdurchlauf im Test-DOM ab. */
function decodeTextEntitiesOnce(value, attribut = false) {
    const named = {
        amp: '&',
        apos: "'",
        colon: ':',
        gt: '>',
        lt: '<',
        ltimes: '⋉',
        notin: '∉',
        quot: '"',
        sol: '/',
    };

    return value.replace(
        /&(?:#x([0-9a-f]+);?|#([0-9]+);?|(AMP|GT|LT|QUOT|amp|apos|colon|gt|ltimes|lt|notin|quot|sol)(;?))/gu,
        (treffer, hexadezimal, dezimal, name, semikolon, position, eingabe) => {
            if (name !== undefined) {
                const legacyOhneSemikolon = ['amp', 'gt', 'lt', 'quot'].includes(name.toLowerCase());
                const folgezeichen = eingabe[position + treffer.length] ?? '';
                if (semikolon === '' && (!legacyOhneSemikolon
                    || (attribut && /^[A-Za-z0-9=]$/u.test(folgezeichen)))) {
                    return treffer;
                }

                return named[name.toLowerCase()] ?? treffer;
            }

            const codepoint = Number.parseInt(hexadezimal ?? dezimal, hexadezimal === undefined ? 10 : 16);
            try {
                return String.fromCodePoint(codepoint);
            } catch {
                return '\ufffd';
            }
        },
    );
}

/**
 * Absichtlich begrenzter HTML-Parser für die festen Sicherheitsbeispiele.
 * Im Browser übernimmt dieselbe Adaptergrenze der native DOMParser.
 */
class TestDomParser {
    parseFromString(html) {
        const document = new TestDocument();
        const stapel = [document.body];
        const teile = tokenizeTestHtml(html);

        for (const teil of teile) {
            if (teil.startsWith('<!--')) {
                stapel.at(-1).appendChild(new TestCommentNode(teil.slice(4, -3)));

                continue;
            }

            /* Browser verlangen den Namen unmittelbar nach `</`. */
            const ende = teil.match(/^<\/([a-z][\w:-]*)[^>]*>$/iu);
            if (ende) {
                const name = ende[1].toLowerCase();
                const passendePosition = stapel.findLastIndex((element) => element.localName === name);
                if (passendePosition > 0) {
                    stapel.length = passendePosition;
                }

                continue;
            }

            const anfang = teil.match(/^<([a-z][\w:-]*)([^]*)>$/iu);
            if (anfang) {
                const name = anfang[1].toLowerCase();
                const element = new TestElement(name);
                const attributText = anfang[2];
                const attributMuster = /([^\t\n\f\r =/>]+)(?:[\t\n\f\r ]*=[\t\n\f\r ]*(?:"([^"]*)"|'([^']*)'|([^\t\n\f\r "'=<>`]+)))?/gu;
                let treffer;
                while ((treffer = attributMuster.exec(attributText)) !== null) {
                    element.setAttribute(
                        treffer[1].toLowerCase(),
                        decodeAttributeEntities(treffer[2] ?? treffer[3] ?? treffer[4] ?? ''),
                    );
                }
                stapel.at(-1).appendChild(element);

                if (!VOID_ELEMENTS.has(name) && !attributText.trimEnd().endsWith('/')) {
                    stapel.push(element);
                }

                continue;
            }

            stapel.at(-1).appendChild(new TestTextNode(decodeTextEntitiesOnce(teil)));
        }

        return document;
    }
}

/**
 * Bildet die relevante Browser-Sonderregel nach: Ein unverpacktes `title`
 * landet im Dokumentkopf und wäre beim Kopieren des Bodys verloren. Innerhalb
 * eines bereits begonnenen Body-Fragments bleibt sein Text erreichbar.
 */
class BrowsernaherTestDomParser extends TestDomParser {
    parseFromString(html) {
        const document = super.parseFromString(html);
        if (!/^<div[\t\n\f\r ]+id=/iu.test(html)) {
            document.body.childNodes = document.body.childNodes.filter((child) => child.localName !== 'title');
        }

        return document;
    }
}

const ACTIVE_TEST_ELEMENTS = [
    'script',
    'style',
    'iframe',
    'object',
    'embed',
    'svg',
    'math',
    'template',
    'noscript',
    'form',
];

const NON_VOID_ACTIVE_TEST_ELEMENTS = ACTIVE_TEST_ELEMENTS.filter((name) => name !== 'embed');

/**
 * Gespeicherte Gegenproben aus Chrome-HTML-Tokenizer und PHP-DOMDocument:
 * Diese Unicode-Zeichen sind JavaScript-Whitespace, aber kein HTML-Whitespace.
 */
const NON_HTML_WHITESPACE_COUNTERPROBES = [
    ['VT', '\u000b'],
    ['NBSP', '\u00a0'],
    ['OGHAM', '\u1680'],
    ['LS', '\u2028'],
    ['BOM', '\ufeff'],
];

/**
 * Simuliert das HTML5-Foster-Parenting, das Tabelleninhalt aus einem dort
 * ungültigen Formular heraus vor das Formular verschiebt.
 */
class FosterParentingTestDomParser extends TestDomParser {
    parseFromString(html) {
        let browserStruktur = html;
        for (const name of ACTIVE_TEST_ELEMENTS) {
            browserStruktur = browserStruktur.replace(
                `<table><${name}><tr><td>bad</td></tr></${name}></table>`,
                `<${name}></${name}>bad`,
            );
        }

        return super.parseFromString(browserStruktur);
    }
}

const PARSER_STATE_ELEMENTS = ['plaintext', 'xmp', 'listing', 'textarea', 'title', 'noembed', 'noframes'];

function findFirstElementByName(parent, name) {
    for (const child of parent.childNodes) {
        if (child.localName === name) {
            return child;
        }

        const nested = findFirstElementByName(child, name);
        if (nested !== null) {
            return nested;
        }
    }

    return null;
}

/**
 * Bildet Browser-Tokenizerzustände nach, in denen Markup als Text gelesen wird.
 * PHP/libxml behandelt diese passiven Elemente dagegen wie normale Container.
 */
class ParserzustandTestDomParser extends TestDomParser {
    parseFromString(html) {
        for (const name of PARSER_STATE_ELEMENTS) {
            const startMatch = new RegExp(`<${name}(?:[\\t\\n\\f\\r ][^>]*)?>`, 'iu').exec(html);
            if (startMatch === null) {
                continue;
            }

            const contentStart = startMatch.index + startMatch[0].length;
            let rawContent = html.slice(contentStart);
            let rest = '';

            if (name !== 'plaintext') {
                const closingMatch = new RegExp(`</${name}[\\t\\n\\f\\r ]*>`, 'iu').exec(rawContent);
                if (closingMatch !== null) {
                    rest = rawContent.slice(closingMatch.index + closingMatch[0].length);
                    rawContent = rawContent.slice(0, closingMatch.index);
                }
            }

            const strukturHtml = `${html.slice(0, startMatch.index)}<${name}></${name}>${rest}`;
            const document = super.parseFromString(strukturHtml);
            const element = findFirstElementByName(document.body, name);
            if (element !== null) {
                element.appendChild(new TestTextNode(rawContent));
            }

            return document;
        }

        return super.parseFromString(html);
    }
}

function sanitize(input, overrides = {}) {
    return sanitizePhilosophyHtml(input, {
        domParser: new TestDomParser(),
        document: new TestDocument(),
        ...overrides,
    });
}

test('spiegelt die serverseitige Positivliste', () => {
    assert.deepEqual(
        ALLOWED_PHILOSOPHY_ELEMENTS,
        ['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'a'],
    );
});

test('entfernt Attribute erlaubter Elemente und aktive Skripte samt Inhalt', () => {
    assert.equal(
        sanitize('<h2 style="color:red">Hallo</h2><script>alert(1)</script>'),
        '<h2>Hallo</h2>',
    );
});

test('behält bei sicheren HTTPS-Links ausschließlich href und festes rel', () => {
    assert.equal(
        sanitize('<a href="https://example.org/path" onclick="x()">Text</a>'),
        '<a href="https://example.org/path" rel="noopener noreferrer">Text</a>',
    );
});

test('wickelt Links mit aktiven Schemes oder Zugangsdaten aus', () => {
    assert.equal(sanitize('<a href="javascript:alert(1)">Text</a>'), 'Text');
    assert.equal(sanitize('<a href="https://user:pass@example.org">Text</a>'), 'Text');
});

test('wickelt nicht erlaubte Bildelemente aus und verwirft deren Attribute', () => {
    assert.equal(sanitize('<img src=x onerror=alert(1)>Text'), 'Text');
});

test('lehnt HTTP, relative URLs und fremde HTTPS-Ports ab', () => {
    assert.equal(sanitize('<a href="http://example.org">Text</a>'), 'Text');
    assert.equal(sanitize('<a href="/intern">Text</a>'), 'Text');
    assert.equal(sanitize('<a href="https://example.org:8443/path">Text</a>'), 'Text');
    assert.equal(
        sanitize('<a href="https://example.org:443/path">Text</a>'),
        '<a href="https://example.org:443/path" rel="noopener noreferrer">Text</a>',
    );
});

test('entfernt aktive Container vollständig', () => {
    assert.equal(
        sanitize('<p>Vorher</p><iframe><p>Frame</p></iframe><svg><text>Vektor</text></svg><style>p{color:red}</style><p>Nachher</p>'),
        '<p>Vorher</p><p>Nachher</p>',
    );
});

test('entfernt auch Inhalte aller weiteren aktiven Container vollständig', () => {
    assert.equal(
        sanitize('<object>Objekt</object><embed>Einbettung</embed><math>Mathematik</math><template>Vorlage</template><noscript>Fallback</noscript><form>Formular</form><p>Sicher</p>'),
        '<p>Sicher</p>',
    );
});

test('entfernt Inhalte aller aktiven Elemente vor browserseitigem Foster-Parenting', () => {
    for (const name of ACTIVE_TEST_ELEMENTS) {
        assert.equal(sanitize(
            `<table><${name}><tr><td>bad</td></tr></${name}></table><p>end</p>`,
            { domParser: new FosterParentingTestDomParser() },
        ), '<p>end</p>', name);
    }
});

test('ignoriert verschachtelte Form-Starts wie der PHP-Parser', () => {
    assert.equal(
        sanitize('<form>outer<form>inner</form>tail</form><p>end</p>'),
        'tail<p>end</p>',
    );
});

test('entfernt Tail-Inhalt gleichnamig verschachtelter aktiver Container PHP-paritätisch', () => {
    for (const name of ['iframe', 'embed', 'noscript']) {
        assert.equal(
            sanitize(`<${name}>outer<${name}>inner</${name}>tail</${name}><p>end</p>`),
            '<p>end</p>',
            name,
        );
    }
});

test('wertet Slash mit folgendem Whitespace bei aktiven Tags nicht als selbstschließend', () => {
    for (const name of ACTIVE_TEST_ELEMENTS) {
        assert.equal(
            sanitize(`<${name}/ >bad</${name}><p>end</p>`),
            '<p>end</p>',
            name,
        );
    }

    assert.equal(
        sanitize('<object data="lokal" / >bad</object><p>end</p>'),
        '<p>end</p>',
    );
});

test('akzeptiert Whitespace nach einem Endtag-Slash nicht als aktives Ende', () => {
    for (const name of ACTIVE_TEST_ELEMENTS) {
        for (const whitespace of [' ', '\t', '\n', '\f']) {
            assert.equal(
                sanitize(
                    `<${name}>BAD</${whitespace}${name}>`
                    + '<a href="https://example.org/leak">LEAK</a>'
                    + `</${name}><p>end</p>`,
                ),
                '<p>end</p>',
                `${name} / ${JSON.stringify(whitespace)}`,
            );
        }
    }
});

test('akzeptiert Whitespace vor einem Endtag-Slash nicht als aktives Ende', () => {
    for (const name of ACTIVE_TEST_ELEMENTS) {
        for (const whitespace of [' ', '\t', '\n', '\f']) {
            assert.equal(
                sanitize(
                    `<${name}>BAD<${whitespace}/${name}>`
                    + '<a href="https://example.org/leak">LEAK</a>'
                    + `</${name}><p>end</p>`,
                ),
                '<p>end</p>',
                `${name} / ${JSON.stringify(whitespace)}`,
            );
        }
    }
});

test('erzeugt beim Angleichen leerer Tags keine aktiven Endtags neu', () => {
    for (const name of ACTIVE_TEST_ELEMENTS) {
        const trennstelle = Math.max(1, Math.floor(name.length / 2));
        const fragmentiert = `${name.slice(0, trennstelle)}<>${name.slice(trennstelle)}`;

        assert.equal(
            sanitize(
                `<${name}>BAD</${fragmentiert}>`
                + '<a href="https://example.org/leak">LEAK</a>'
                + `</${name}><p>end</p>`,
            ),
            '<p>end</p>',
            name,
        );
    }
});

test('behandelt Unicode-Whitespace außerhalb der HTML-Positivliste nicht als Taggrammatik', () => {
    for (const name of NON_VOID_ACTIVE_TEST_ELEMENTS) {
        for (const [bezeichnung, whitespace] of NON_HTML_WHITESPACE_COUNTERPROBES) {
            assert.equal(
                sanitize(
                    `<${name}>BAD</${name}${whitespace}>`
                    + '<a href="https://example.org/leak">LEAK</a>'
                    + `</${name}><p>end</p>`,
                ),
                '<p>end</p>',
                `${name} / ${bezeichnung}`,
            );
        }
    }
});

test('erzeugt aus Entities weder Endtags noch selbstschließende aktive Starttags', () => {
    for (const name of NON_VOID_ACTIVE_TEST_ELEMENTS) {
        assert.equal(
            sanitize(
                `<${name}>BAD</${name}&#32;>`
                + '<a href="https://example.org/leak">LEAK</a>'
                + `</${name}><p>end</p>`,
            ),
            '<p>end</p>',
            name,
        );
    }

    const weitereFaelle = [
        '<script>BAD</script&amp;#32;><a href="https://example.org/leak">LEAK</a></script><p>end</p>',
        '<script &#47;>BAD</script><p>end</p>',
        '<script &amp;#47;>BAD</script><p>end</p>',
        '<script &sol;>BAD</script><p>end</p>',
    ];

    for (const html of weitereFaelle) {
        assert.equal(sanitize(html), '<p>end</p>', html);
    }
});

test('behandelt selbstschließende und ähnlich benannte Embed-Tags wie der Server', () => {
    assert.equal(
        sanitize('<embed />Text</embed><p>Sicher</p>'),
        'Text<p>Sicher</p>',
    );
    assert.equal(
        sanitize('<embed.foo>Passiv</embed.foo><p>Sicher</p>'),
        'Passiv<p>Sicher</p>',
    );
});

test('entfernt malformed und ungeschlossene Embed-Inhalte wie der Server', () => {
    assert.equal(sanitize('<embed>Text<p>Sicher</p>'), '');
    assert.equal(
        sanitize('<embed data=x/>Text</embed><p>Sicher</p>'),
        '<p>Sicher</p>',
    );
    assert.equal(
        sanitize('<embed data=x">Text</embed><p>Sicher</p>'),
        '<p>Sicher</p>',
    );
});

test('entfernt Kommentare und Event-Attribute', () => {
    assert.equal(
        sanitize('<!-- geheim --><p onmouseover="x()">Sicher</p>'),
        '<p>Sicher</p>',
    );
});

test('erkennt alternative und abrupte Kommentarenden PHP-kompatibel', () => {
    assert.equal(
        sanitize('<!-- foo --!><embed>Text</embed><p>Sicher</p>'),
        '<p>Sicher</p>',
    );
    assert.equal(sanitize('<!--><embed>Text</embed><p>Sicher</p>'), '');
    assert.equal(sanitize('<!---><embed>Text</embed><p>Sicher</p>'), '');
});

test('normalisiert alternative Kommentarenden nur innerhalb echter Kommentare', () => {
    assert.equal(
        sanitize(
            '<!-- Kommentar --!><p>Sicher</p> --!> '
            + '<a href="https://example.org/a--!>b">Link</a>',
        ),
        '<p>Sicher</p> --!&gt; '
        + '<a href="https://example.org/a--!&gt;b" rel="noopener noreferrer">Link</a>',
    );
    assert.equal(
        sanitize('<a href="https://example.org/a<!--x--!>b">Link</a>'),
        '<a href="https://example.org/a&lt;!--x--!&gt;b" rel="noopener noreferrer">Link</a>',
    );
});

test('verarbeitet viele Kommentare innerhalb der Eingabegrenze in linearer Zeit', () => {
    const html = `${'<!--x-->'.repeat(1_200)}<p>Sicher</p>`;
    const gestartet = performance.now();

    assert.equal(sanitize(html), '<p>Sicher</p>');
    const laufzeit = performance.now() - gestartet;
    assert.ok(laufzeit < 150, `Kommentarverarbeitung dauerte ${laufzeit.toFixed(1)} ms`);
});

test('wickelt unbekannte passive Format-Tags aus', () => {
    assert.equal(
        sanitize('<section><span class="hinweis">Hallo <b>Welt</b></span></section>'),
        'Hallo Welt',
    );
});

test('behält passive Metadaten-Tags durch einen gekapselten Body-Fragmentkontext', () => {
    assert.equal(sanitize('<title>Titel</title><p>Sicher</p>', {
        domParser: new BrowsernaherTestDomParser(),
    }), 'Titel<p>Sicher</p>');
});

test('neutralisiert parserzustandsverändernde passive Elemente vor dem Fragment-Wrapper', () => {
    const faelle = [
        '<plaintext>text<p>more</p>',
        '<xmp>text<p>more</p></xmp>',
        '<listing>text<p>more</p></listing>',
        '<textarea>text<p>more</p></textarea>',
        '<title>text<p>more</p></title>',
        '<noembed>text<p>more</p></noembed>',
        '<noframes>text<p>more</p></noframes>',
    ];

    for (const html of faelle) {
        assert.equal(sanitize(html, {
            domParser: new ParserzustandTestDomParser(),
        }), 'text<p>more</p>', html);
    }
});

test('entfernt Nullbytes vor der Längenbegrenzung', () => {
    assert.equal(sanitize('<p>Hal\0lo</p>'), '<p>Hallo</p>');
    assert.equal(sanitize(`${'a'.repeat(9_999)}\0bc`).length, 10_000);
    assert.equal(sanitize(`${'a'.repeat(9_999)}\0bc`).endsWith('b'), true);
});

test('begrenzt Eingaben vor dem Parsen auf 10.000 Unicode-Zeichen', () => {
    assert.equal(sanitize('a'.repeat(10_001)), 'a'.repeat(10_000));
    assert.equal(sanitize(`${'a'.repeat(9_999)}😀x`), `${'a'.repeat(9_999)}😀`);
});

test('serialisiert kombinierte Entitäten sichtbar PHP-paritätisch', () => {
    const gegenproben = [
        ['<p>&amp;&lt;&gt;…</p>', '<p>&amp;&lt;&gt;…</p>'],
        ['<p>A&lt; &gt;B</p>', '<p>A&lt; &gt;B</p>'],
        ['<p>A&lt;/&gt;B</p>', '<p>A&lt;/&gt;B</p>'],
        ['<p>A&#60;&#62;B</p>', '<p>A&lt;&gt;B</p>'],
        ['<p>A&#x3c;&#x3e;B</p>', '<p>A&lt;&gt;B</p>'],
        ['<p>A&amp;lt;&amp;gt;B</p>', '<p>A&amp;lt;&amp;gt;B</p>'],
    ];

    for (const [html, erwartet] of gegenproben) {
        assert.equal(sanitize(html), erwartet, html);
    }
    assert.equal(
        sanitize('<a href="https://example.org/?a=1&amp;b=2">Link</a>'),
        '<a href="https://example.org/?a=1&amp;b=2" rel="noopener noreferrer">Link</a>',
    );
});

test('spiegelt semikolonlose HTML5-Legacy-Entitäten in Text und Attributen', () => {
    const textfaelle = [
        ['<p>&amp </p>', '<p>&amp; </p>'],
        ['<p>&lt Text &gt; &quot </p>', '<p>&lt; Text &gt; " </p>'],
        ['<p>&ampx</p>', '<p>&amp;x</p>'],
        ['<p>&AMP &Amp</p>', '<p>&amp; &amp;Amp</p>'],
        ['<p>&ltimes; &notin;</p>', '<p>⋉ ∉</p>'],
    ];

    for (const [html, erwartet] of textfaelle) {
        assert.equal(sanitize(html), erwartet, html);
    }

    assert.equal(
        sanitize('<a href="https://example.org/a&amp/b">Link</a>'),
        '<a href="https://example.org/a&amp;/b" rel="noopener noreferrer">Link</a>',
    );
    assert.equal(
        sanitize('<a href="https://example.org/a&amp=b">Link</a>'),
        '<a href="https://example.org/a&amp;amp=b" rel="noopener noreferrer">Link</a>',
    );
    assert.equal(
        sanitize('<a href="https://example.org/a&ampb">Link</a>'),
        '<a href="https://example.org/a&amp;ampb" rel="noopener noreferrer">Link</a>',
    );
});

test('prüft HTTPS-URLs unabhängig von der HTML-Bereinigung', () => {
    assert.equal(isSafeHttpsUrl('https://example.org/path'), true);
    assert.equal(isSafeHttpsUrl('https://example.org:443/path'), true);
    assert.equal(isSafeHttpsUrl('http://example.org/path'), false);
    assert.equal(isSafeHttpsUrl('javascript:alert(1)'), false);
    assert.equal(isSafeHttpsUrl('/path'), false);
    assert.equal(isSafeHttpsUrl('https://user@example.org/path'), false);
    assert.equal(isSafeHttpsUrl('https://example.org:444/path'), false);
    assert.equal(isSafeHttpsUrl('https://example.org:000443/path'), false);
    assert.equal(isSafeHttpsUrl('https://[::1]/path'), true);
    assert.equal(isSafeHttpsUrl('https://[::1]:443/path'), true);
    assert.equal(isSafeHttpsUrl('https://[::1]:000443/path'), false);
});

test('lehnt von WHATWG normalisierte, serverseitig ungültige URL-Schreibweisen ab', () => {
    const serverseitigUngueltigeUrls = [
        'HTTPS://example.org/path',
        'https:example.org',
        String.raw`https:\example.org`,
        'https:////example.org',
        String.raw`https://example.org\@evil.example`,
        'https://@example.org/path',
        'https://:@example.org/path',
    ];

    for (const url of serverseitigUngueltigeUrls) {
        assert.equal(isSafeHttpsUrl(url), false, url);
        assert.equal(sanitize(`<a href="${url}">Text</a>`), 'Text', url);
    }
});

test('lehnt jedes Prozentzeichen in der Authority fail-closed ab', () => {
    const urls = [
        ...['23', '01', '25', '3f', '5b', '7f'].map((code) => `https://exa%${code}mple.org/path`),
        'https://exa%mple.org/path',
        'https://exa%ggmple.org/path',
        'https://exa%252fmple.org/path',
    ];

    for (const url of urls) {
        assert.equal(isSafeHttpsUrl(url), false, url);
        assert.equal(sanitize(`<a href="${url}">Text</a>`), 'Text', url);
    }

    assert.equal(
        sanitize('<a href="https://example.org/%2f/%25">Pfad</a>'),
        '<a href="https://example.org/%2f/%25" rel="noopener noreferrer">Pfad</a>',
    );
});

test('behandelt einfach und mehrfach kodiertes Markup ausschließlich als Text', () => {
    assert.equal(
        sanitize('&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;<p>Sicher</p>'),
        '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;<p>Sicher</p>',
    );
    assert.equal(
        sanitize('&lt;script&gt;alert(1)&lt;/script&gt;<p>Sicher</p>'),
        '&lt;script&gt;alert(1)&lt;/script&gt;<p>Sicher</p>',
    );
});

test('liefert bei Parser- und Dokumentadapterfehlern fail-closed eine leere Ausgabe', () => {
    assert.equal(sanitize('<p>Text</p>', {
        domParser: {
            parseFromString() {
                throw new Error('Parserfehler');
            },
        },
    }), '');

    assert.equal(sanitize('<p>Text</p>', {
        document: {
            createElement() {
                throw new Error('Dokumentfehler');
            },
        },
    }), '');
});

test('lehnt einen ausdrücklich injizierten ungültigen Parser statt eines globalen Fallbacks ab', () => {
    const vorherigerDomParser = globalThis.DOMParser;
    globalThis.DOMParser = TestDomParser;

    try {
        assert.equal(sanitize('<p>Text</p>', { domParser: null }), '');
        assert.equal(sanitize('<p>Text</p>', { domParser: {} }), '');
        assert.equal(sanitize('<p>Text</p>', { domParser: { parseFromString: 'ungültig' } }), '');
    } finally {
        globalThis.DOMParser = vorherigerDomParser;
    }
});

test('lehnt dasselbe Dokument als Quell- und Zieladapter fail-closed ab', () => {
    const gemeinsamesDokument = new TestDomParser().parseFromString('<p>Text</p>');

    assert.equal(sanitizePhilosophyHtml('<p>Text</p>', {
        domParser: {
            parseFromString() {
                return gemeinsamesDokument;
            },
        },
        document: gemeinsamesDokument,
    }), '');
});
