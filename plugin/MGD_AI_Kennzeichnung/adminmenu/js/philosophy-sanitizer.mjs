/**
 * Erlaubte HTML-Elemente für redaktionelle Philosophie-Texte.
 *
 * Diese Positivliste spiegelt bewusst den serverseitigen
 * `PhilosophySanitizer` wider. Der PHP-Sanitizer bleibt trotzdem die
 * maßgebliche Sicherheitsgrenze; diese Datei verbessert nur die direkte
 * Rückmeldung im Browser.
 */
export const ALLOWED_PHILOSOPHY_ELEMENTS = Object.freeze([
    'p',
    'h2',
    'h3',
    'ul',
    'ol',
    'li',
    'strong',
    'em',
    'a',
]);

const ACTIVE_PHILOSOPHY_ELEMENTS = new Set([
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
]);

const NON_NESTING_ACTIVE_ELEMENTS = new Set([
    'script',
    'style',
    'form',
]);

const PARSER_STATE_ELEMENTS = new Set([
    'plaintext',
    'xmp',
    'listing',
    'textarea',
    'title',
    'noembed',
    'noframes',
]);
const PASSIVE_PARSER_CONTAINER = 'mgd-ai-passive-container';

const ALLOWED_ELEMENT_SET = new Set(ALLOWED_PHILOSOPHY_ELEMENTS);
const MAXIMUM_INPUT_LENGTH = 10_000;
const SOURCE_ROOT_ID = 'mgd-ai-philosophy-client-root';
const EMPTY_HTML_TOKEN_BOUNDARY = '<!--mgd-ai-empty-token-->';
const ELEMENT_NODE = 1;
const TEXT_NODE = 3;

/**
 * Prüft eine Linkadresse ohne Basis-URL. Dadurch werden relative Adressen
 * nicht versehentlich in gültige absolute Links umgewandelt.
 *
 * @param {unknown} value Zu prüfende Linkadresse.
 * @returns {boolean} `true` ausschließlich für sichere absolute HTTPS-URLs.
 */
export function isSafeHttpsUrl(value) {
    if (typeof value !== 'string' || value === '' || value.trim() !== value) {
        return false;
    }

    /*
     * WHATWG-URL-Parser reparieren unter anderem fehlende Schrägstriche,
     * Backslashes und die Groß-/Kleinschreibung des Schemas. Der Server tut
     * das nicht. Deshalb muss die rohe Syntax bereits vor dem Parser dem
     * strengeren PHP-Vertrag entsprechen.
     */
    if (!value.startsWith('https://')
        || value.includes('\\')
        || /[\u0000-\u0020\u007f]/u.test(value)) {
        return false;
    }

    const authority = value.slice('https://'.length).split(/[/?#]/u, 1)[0];
    if (authority === ''
        || authority.includes('@')
        || authority.includes('%')
        || !hasSafeRawPortSyntax(authority)) {
        return false;
    }

    try {
        const url = new URL(value);

        return url.protocol === 'https:'
            && url.hostname !== ''
            && url.username === ''
            && url.password === ''
            && (url.port === '' || url.port === '443');
    } catch {
        return false;
    }
}

/**
 * Verhindert, dass der WHATWG-Parser abweichende Portschreibweisen vor der
 * Prüfung normalisiert. Bei IPv6 zählen nur Doppelpunkte hinter der schließenden
 * Klammer als Porttrenner.
 */
function hasSafeRawPortSyntax(authority) {
    if (authority.startsWith('[')) {
        const bracketEnd = authority.indexOf(']');
        if (bracketEnd === -1) {
            return false;
        }

        const suffix = authority.slice(bracketEnd + 1);

        return suffix === '' || suffix === ':443';
    }

    const firstColon = authority.indexOf(':');
    if (firstColon === -1) {
        return true;
    }

    return firstColon === authority.lastIndexOf(':')
        && authority.slice(firstColon) === ':443';
}

/**
 * Bereinigt HTML für die Vorschau des Philosophie-Editors.
 *
 * DOMParser und Dokument sind injizierbar, damit dieselbe Logik ohne externe
 * DOM-Bibliothek getestet werden kann. Das Zieldokument muss getrennt vom
 * geparsten Quelldokument sein: Kein Quellknoten und kein freies Attribut wird
 * übernommen.
 *
 * @param {unknown} input Nicht vertrauenswürdige Editor-Eingabe.
 * @param {{domParser?: object, document?: object}} [adapters] DOM-Adapter.
 * @returns {string} Kontrolliert serialisiertes, bereinigtes HTML.
 */
export function sanitizePhilosophyHtml(input, adapters = {}) {
    if (typeof input !== 'string') {
        return '';
    }

    try {
        const begrenzt = limitInput(input);
        if (begrenzt === '') {
            return '';
        }

        /* libxml behandelt diese abrupten Formen als Kommentar bis zum EOF. */
        if (/<!--(?:>|->)/u.test(begrenzt)) {
            return '';
        }

        const leereTagsBereinigt = removeEmptyHtmlTokens(begrenzt);
        const kommentareNormalisiert = normalizeAlternativeCommentEnds(leereTagsBereinigt);
        const parserzustaendeNeutralisiert = neutralizeParserStateElements(kommentareNormalisiert);
        let vorbereitet = parserzustaendeNeutralisiert;
        for (const elementName of ACTIVE_PHILOSOPHY_ELEMENTS) {
            vorbereitet = removeExplicitActiveContent(vorbereitet, elementName);
        }
        const parser = resolveDomParser(adapters);
        const quellHtml = `<div id="${SOURCE_ROOT_ID}">${vorbereitet}</div>`;
        const quellDokument = parser.parseFromString(quellHtml, 'text/html');
        if (!quellDokument || !quellDokument.body) {
            return '';
        }

        const quellWurzel = findSourceRoot(quellDokument.body);
        if (quellWurzel === null) {
            return '';
        }

        const zielDokument = resolveTargetDocument(adapters, quellDokument);
        if (zielDokument === quellDokument) {
            return '';
        }
        const zielWurzel = zielDokument.createElement('div');
        copySanitizedChildren(quellWurzel, zielWurzel, zielDokument);

        return serializeChildren(zielWurzel).trim();
    } catch {
        /*
         * Parser- und Adapterfehler führen absichtlich zu keiner Ausgabe.
         * Unsicherer Originalinhalt darf niemals als Rückfallwert erscheinen.
         */
        return '';
    }
}

/** Entfernt Nullbytes und zählt Unicode-Zeichen statt UTF-16-Codeeinheiten. */
function limitInput(input) {
    const ohneNullbytes = input.replaceAll('\0', '');

    return Array.from(ohneNullbytes).slice(0, MAXIMUM_INPUT_LENGTH).join('');
}

function resolveDomParser(adapters) {
    if (Object.hasOwn(adapters, 'domParser')) {
        if (adapters.domParser && typeof adapters.domParser.parseFromString === 'function') {
            return adapters.domParser;
        }

        throw new Error('Ungültiger DOMParser-Adapter.');
    }

    if (typeof globalThis.DOMParser === 'function') {
        return new globalThis.DOMParser();
    }

    throw new Error('Kein DOMParser verfügbar.');
}

/**
 * Ersetzt leere `<>`-Tokens außerhalb echter Tags und Kommentare durch eine
 * feste, unsichtbare Knotengrenze. libxml verwirft diese Tokens ebenfalls;
 * die Grenze verhindert zusätzlich, dass getrennte Tagfragmente zu neuem
 * Markup zusammengefügt werden. Attribute und Kommentare bleiben bytegenau.
 */
function removeEmptyHtmlTokens(input) {
    let ausgabe = '';
    let cursor = 0;

    while (cursor < input.length) {
        if (input.startsWith('<!--', cursor)) {
            const kommentarEnde = findHtmlCommentEnd(input, cursor);
            if (kommentarEnde === null) {
                return ausgabe + input.slice(cursor);
            }

            ausgabe += input.slice(cursor, kommentarEnde.end);
            cursor = kommentarEnde.end;

            continue;
        }

        if (input.startsWith('<>', cursor)) {
            ausgabe += EMPTY_HTML_TOKEN_BOUNDARY;
            cursor += 2;

            continue;
        }

        if (input[cursor] === '<') {
            const tag = readHtmlTag(input, cursor);
            if (tag?.unterminated === true) {
                return ausgabe + input.slice(cursor);
            }
            if (tag !== null) {
                ausgabe += input.slice(cursor, tag.end);
                cursor = tag.end;

                continue;
            }
        }

        ausgabe += input[cursor];
        cursor += 1;
    }

    return ausgabe;
}

/**
 * Vereinheitlicht das alternative HTML-Kommentarende nur in echten
 * Kommentaren. Der Cursor läuft monoton, damit viele kurze Kommentare nicht
 * zu wiederholten Suchen über den gesamten Resttext führen.
 */
function normalizeAlternativeCommentEnds(input) {
    let ausgabe = '';
    let cursor = 0;

    while (cursor < input.length) {
        if (input.startsWith('<!--', cursor)) {
            const kommentarEnde = findHtmlCommentEnd(input, cursor);
            if (kommentarEnde === null) {
                return ausgabe + input.slice(cursor);
            }

            ausgabe += input.slice(cursor, kommentarEnde.start);
            ausgabe += '-->';
            cursor = kommentarEnde.end;

            continue;
        }

        if (input[cursor] === '<') {
            const tag = readHtmlTag(input, cursor);
            if (tag?.unterminated === true) {
                return ausgabe + input.slice(cursor);
            }
            if (tag !== null) {
                ausgabe += input.slice(cursor, tag.end);
                cursor = tag.end;

                continue;
            }
        }

        ausgabe += input[cursor];
        cursor += 1;
    }

    return ausgabe;
}

/** Findet das erste gültige Standard- oder Alternativende eines Kommentars. */
function findHtmlCommentEnd(input, commentStart) {
    for (let position = commentStart + 4; position < input.length; position += 1) {
        if (input.startsWith('-->', position)) {
            return { start: position, end: position + 3 };
        }
        if (input.startsWith('--!>', position)) {
            return { start: position, end: position + 4 };
        }
    }

    return null;
}

/**
 * Entfernt parserkritische aktive Bereiche noch vor dem Browser-Parser.
 *
 * Das betrifft `embed`, das Browser als inhaltslos behandeln, und `form`,
 * dessen Kinder beim HTML5-Foster-Parenting aus dem aktiven Element verschoben
 * werden können. Der kleine Scanner berücksichtigt Anführungszeichen in
 * Attributen, Kommentare und verschachtelte gleichnamige Bereiche.
 */
function removeExplicitActiveContent(input, elementName) {
    let ausgabe = '';
    let cursor = 0;

    while (cursor < input.length) {
        const startTag = findNextHtmlTag(input, cursor, elementName, false);
        if (startTag === null) {
            ausgabe += input.slice(cursor);
            break;
        }

        ausgabe += input.slice(cursor, startTag.start);
        const endPosition = startTag.selfClosing
            ? startTag.end
            : findMatchingActiveElementEnd(input, startTag.end, elementName);

        /* Der PHP-Parser ordnet bei fehlendem Endtag den gesamten Rest zu. */
        cursor = endPosition ?? input.length;
    }

    return ausgabe;
}

function findMatchingActiveElementEnd(input, startPosition, elementName) {
    let tiefe = 1;
    let cursor = startPosition;

    while (cursor < input.length) {
        const tag = findNextHtmlTag(input, cursor, elementName);
        if (tag === null) {
            return null;
        }

        cursor = tag.end;
        if (tag.closing) {
            tiefe -= 1;
            if (tiefe === 0) {
                return tag.end;
            }
        } else if (!tag.selfClosing && !NON_NESTING_ACTIVE_ELEMENTS.has(elementName)) {
            tiefe += 1;
        }
    }

    return null;
}

/**
 * Neutralisiert passive Elemente, die den HTML-Tokenizer in einen Textzustand
 * versetzen würden. Das attributlose Ersatzelement ist nicht freigegeben und
 * wird später wie jede passive unbekannte Formatierung ausgewickelt.
 */
function neutralizeParserStateElements(input) {
    let ausgabe = '';
    let cursor = 0;

    while (cursor < input.length) {
        const tagStart = input.indexOf('<', cursor);
        if (tagStart === -1) {
            ausgabe += input.slice(cursor);
            break;
        }

        ausgabe += input.slice(cursor, tagStart);
        if (input.startsWith('<!--', tagStart)) {
            const kommentarEnde = findHtmlCommentEnd(input, tagStart);
            if (kommentarEnde === null) {
                ausgabe += input.slice(tagStart);
                break;
            }

            ausgabe += input.slice(tagStart, kommentarEnde.end);
            cursor = kommentarEnde.end;

            continue;
        }

        const tag = readHtmlTag(input, tagStart);
        if (tag === null) {
            ausgabe += '<';
            cursor = tagStart + 1;

            continue;
        }
        if (tag.unterminated === true) {
            ausgabe += input.slice(tagStart);
            break;
        }

        if (!PARSER_STATE_ELEMENTS.has(tag.name)) {
            ausgabe += input.slice(tagStart, tag.end);
            cursor = tag.end;

            continue;
        }

        if (tag.closing) {
            ausgabe += `</${PASSIVE_PARSER_CONTAINER}>`;
        } else if (tag.selfClosing) {
            ausgabe += `<${PASSIVE_PARSER_CONTAINER}></${PASSIVE_PARSER_CONTAINER}>`;
        } else {
            ausgabe += `<${PASSIVE_PARSER_CONTAINER}>`;
        }
        cursor = tag.end;
    }

    return ausgabe;
}

/** Sucht echte HTML-Tags, ohne tagähnlichen Text in Attributen zu beachten. */
function findNextHtmlTag(input, startPosition, expectedName, includeClosing = true) {
    let cursor = startPosition;

    while (cursor < input.length) {
        const tagStart = input.indexOf('<', cursor);
        if (tagStart === -1) {
            return null;
        }

        if (input.startsWith('<!--', tagStart)) {
            const kommentarEnde = findHtmlCommentEnd(input, tagStart);
            if (kommentarEnde === null) {
                return null;
            }

            cursor = kommentarEnde.end;

            continue;
        }

        const tag = readHtmlTag(input, tagStart);
        if (tag === null) {
            cursor = tagStart + 1;
            continue;
        }
        if (tag.unterminated === true) {
            return null;
        }

        cursor = tag.end;
        if (tag.name !== expectedName || (!includeClosing && tag.closing)) {
            continue;
        }

        return tag;
    }

    return null;
}

/**
 * Liest einen HTML-Start- oder Endtag mit den für Attribute relevanten
 * Tokenizer-Zuständen. Quotes innerhalb unquotierter Werte und Slashes am Ende
 * solcher Werte werden dadurch nicht als Struktur fehlinterpretiert.
 */
function readHtmlTag(input, startPosition) {
    let position = startPosition + 1;

    const closing = input[position] === '/';
    if (closing) {
        position += 1;
    }

    const nameStart = position;
    if (!/[a-z]/iu.test(input[position] ?? '')) {
        return null;
    }
    position += 1;
    while (/[\w:-]/u.test(input[position] ?? '')) {
        position += 1;
    }

    if (!isHtmlWhitespace(input[position]) && !['/', '>'].includes(input[position])) {
        return null;
    }

    const name = input.slice(nameStart, position).toLowerCase();
    let state = 'beforeAttribute';
    let quote = null;

    while (position < input.length) {
        const character = input[position];

        if (state === 'quotedValue') {
            if (character === quote) {
                quote = null;
                state = 'afterQuotedValue';
            }
            position += 1;

            continue;
        }

        if (character === '>') {
            return {
                start: startPosition,
                end: position + 1,
                name,
                closing,
                selfClosing: false,
            };
        }

        if (state === 'beforeAttribute') {
            if (isHtmlWhitespace(character)) {
                position += 1;
            } else if (character === '/') {
                const selfClosingEnd = findSelfClosingEnd(input, position);
                if (selfClosingEnd !== null) {
                    return { start: startPosition, end: selfClosingEnd, name, closing, selfClosing: !closing };
                }
                state = 'attributeName';
                position += 1;
            } else {
                state = 'attributeName';
                position += 1;
            }

            continue;
        }

        if (state === 'attributeName') {
            if (isHtmlWhitespace(character)) {
                state = 'afterAttributeName';
            } else if (character === '=') {
                state = 'beforeAttributeValue';
            } else if (character === '/') {
                const selfClosingEnd = findSelfClosingEnd(input, position);
                if (selfClosingEnd !== null) {
                    return { start: startPosition, end: selfClosingEnd, name, closing, selfClosing: !closing };
                }
            }
            position += 1;

            continue;
        }

        if (state === 'afterAttributeName') {
            if (isHtmlWhitespace(character)) {
                position += 1;
            } else if (character === '=') {
                state = 'beforeAttributeValue';
                position += 1;
            } else if (character === '/') {
                const selfClosingEnd = findSelfClosingEnd(input, position);
                if (selfClosingEnd !== null) {
                    return { start: startPosition, end: selfClosingEnd, name, closing, selfClosing: !closing };
                }
                state = 'attributeName';
                position += 1;
            } else {
                state = 'attributeName';
                position += 1;
            }

            continue;
        }

        if (state === 'beforeAttributeValue') {
            if (isHtmlWhitespace(character)) {
                position += 1;
            } else if (character === '"' || character === "'") {
                quote = character;
                state = 'quotedValue';
                position += 1;
            } else {
                state = 'unquotedValue';
                position += 1;
            }

            continue;
        }

        if (state === 'unquotedValue') {
            if (isHtmlWhitespace(character)) {
                state = 'beforeAttribute';
            }
            position += 1;

            continue;
        }

        /* Zustand direkt hinter einem korrekt geschlossenen Quote. */
        if (isHtmlWhitespace(character)) {
            state = 'beforeAttribute';
            position += 1;
        } else if (character === '/') {
            const selfClosingEnd = findSelfClosingEnd(input, position);
            if (selfClosingEnd !== null) {
                return { start: startPosition, end: selfClosingEnd, name, closing, selfClosing: !closing };
            }
            state = 'attributeName';
            position += 1;
        } else {
            state = 'attributeName';
            position += 1;
        }
    }

    return { unterminated: true };
}

/** HTML kennt ausschließlich diese fünf ASCII-Zeichen als Whitespace. */
function isHtmlWhitespace(value) {
    return value === '\t'
        || value === '\n'
        || value === '\f'
        || value === '\r'
        || value === ' ';
}

function findSelfClosingEnd(input, slashPosition) {
    const endPosition = slashPosition + 1;

    return input[endPosition] === '>' ? endPosition + 1 : null;
}

/** Liefert garantiert ein vom Quelldokument getrenntes Zieldokument. */
function resolveTargetDocument(adapters, sourceDocument) {
    if (Object.hasOwn(adapters, 'document')) {
        if (!adapters.document) {
            throw new Error('Ungültiger Dokumentadapter.');
        }

        return adapters.document;
    }

    const browserDocument = globalThis.document;
    if (browserDocument?.implementation?.createHTMLDocument) {
        return browserDocument.implementation.createHTMLDocument('');
    }

    if (sourceDocument.implementation?.createHTMLDocument) {
        return sourceDocument.implementation.createHTMLDocument('');
    }

    throw new Error('Kein separates Zieldokument verfügbar.');
}

/** Findet ausschließlich den vom Sanitizer selbst vorangestellten Fragmentanker. */
function findSourceRoot(parent) {
    for (const child of Array.from(parent.childNodes ?? [])) {
        if (child.nodeType !== ELEMENT_NODE) {
            continue;
        }

        if (getElementName(child) === 'div' && child.getAttribute('id') === SOURCE_ROOT_ID) {
            return child;
        }

        const nestedRoot = findSourceRoot(child);
        if (nestedRoot !== null) {
            return nestedRoot;
        }
    }

    return null;
}

function copySanitizedChildren(sourceParent, targetParent, targetDocument) {
    for (const sourceChild of Array.from(sourceParent.childNodes ?? [])) {
        copySanitizedNode(sourceChild, targetParent, targetDocument);
    }
}

function copySanitizedNode(sourceNode, targetParent, targetDocument) {
    if (sourceNode.nodeType === TEXT_NODE) {
        const text = typeof sourceNode.data === 'string' ? sourceNode.data : sourceNode.nodeValue;
        if (typeof text === 'string') {
            targetParent.appendChild(targetDocument.createTextNode(text));
        }

        return;
    }

    if (sourceNode.nodeType !== ELEMENT_NODE) {
        return;
    }

    const elementName = getElementName(sourceNode);
    if (ACTIVE_PHILOSOPHY_ELEMENTS.has(elementName)) {
        return;
    }

    if (!ALLOWED_ELEMENT_SET.has(elementName)) {
        copySanitizedChildren(sourceNode, targetParent, targetDocument);

        return;
    }

    if (elementName === 'a') {
        const href = sourceNode.getAttribute('href');
        if (!isSafeHttpsUrl(href)) {
            copySanitizedChildren(sourceNode, targetParent, targetDocument);

            return;
        }

        const link = targetDocument.createElement('a');
        link.setAttribute('href', href);
        link.setAttribute('rel', 'noopener noreferrer');
        copySanitizedChildren(sourceNode, link, targetDocument);
        targetParent.appendChild(link);

        return;
    }

    const targetElement = targetDocument.createElement(elementName);
    copySanitizedChildren(sourceNode, targetElement, targetDocument);
    targetParent.appendChild(targetElement);
}

function getElementName(node) {
    const name = node.localName ?? node.tagName ?? '';

    return typeof name === 'string' ? name.toLowerCase() : '';
}

/**
 * Serialisiert ausschließlich die zuvor selbst erzeugte Positivlisten-Struktur.
 * Weder `innerHTML` noch `outerHTML` dienen dabei als Vertrauenssenke.
 */
function serializeChildren(parent) {
    return Array.from(parent.childNodes ?? []).map(serializeNode).join('');
}

function serializeNode(node) {
    if (node.nodeType === TEXT_NODE) {
        const text = typeof node.data === 'string' ? node.data : node.nodeValue;

        return typeof text === 'string' ? escapeText(text) : '';
    }

    if (node.nodeType !== ELEMENT_NODE) {
        return '';
    }

    const elementName = getElementName(node);
    if (!ALLOWED_ELEMENT_SET.has(elementName)) {
        return '';
    }

    let attributes = '';
    if (elementName === 'a') {
        const href = node.getAttribute('href');
        if (!isSafeHttpsUrl(href)) {
            return serializeChildren(node);
        }
        attributes = ` href="${escapeAttribute(href)}" rel="noopener noreferrer"`;
    }

    return `<${elementName}${attributes}>${serializeChildren(node)}</${elementName}>`;
}

function escapeText(value) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

function escapeAttribute(value) {
    return escapeText(value)
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
