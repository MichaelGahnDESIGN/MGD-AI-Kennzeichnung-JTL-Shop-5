import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

/**
 * Lokale Sichtprüfung des echten Darstellungstemplates ohne JTL-Anmeldung oder
 * Shopdaten. Bewusst kein allgemeiner Smarty-Interpreter: Nur die bekannten
 * statischen Testwerte werden eingesetzt; unbekannte Direktiven brechen ab.
 * Start: node tests/Browser/display-preview-server.mjs
 * Der Server lauscht ausschließlich auf Loopback und nimmt keine POSTs an.
 */
const admin = new URL('../../plugin/MGD_AI_Kennzeichnung/adminmenu/', import.meta.url);
const assets = new Map([
    ['display.css', 'text/css'],
    ['display-preview-pattern.css', 'text/css'],
    ['js/display-controls.mjs', 'text/javascript'],
    ['js/display-preview.mjs', 'text/javascript'],
    ['js/display-range-sync.mjs', 'text/javascript'],
    ['images/michael-gahn-design-schuh.png', 'image/png'],
]);
const values = Object.freeze({
    adminUrl: '/assets/', pluginId: '1', adminMenuId: '1', csrfToken: 'lokaler-test-ohne-speicherfunktion',
    language: 'auto', fontSize: '12', outerMargin: '8', innerPadding: '6', borderRadius: '4', blur: '0', transparency: '8',
});

async function renderFixture(url) {
    // Der iframe erzeugt einen echten schmalen Viewport für die Media-Queries.
    if (url.searchParams.get('width') === 'narrow') {
        return `<!doctype html><html lang="de"><meta charset="utf-8"><title>Schmaler Darstellungstest</title>
<style>body{margin:0;background:#24272d}iframe{display:block;border:0;width:360px;height:720px;margin:auto;max-width:100%}output{display:block;background:#fff;padding:12px;font:14px monospace}</style>
<output id="local-measurements">Messung wird geladen.</output>
<iframe title="Pluginvorschau mit 360 Pixeln Breite" src="/?embedded=true${url.searchParams.get('extreme') === 'yes' ? '&extreme=yes' : ''}#local-test-label"></iframe></html>`;
    }
    const testValues = url.searchParams.get('extreme') === 'yes'
        ? { ...values, fontSize: '48', outerMargin: '64', innerPadding: '32', borderRadius: '32', blur: '24', transparency: '90' }
        : values;
    let template = await readFile(new URL('templates/display.tpl', admin), 'utf8');
    template = template.replace(/\{\*[\s\S]*?\*\}/g, '');
    template = template.replace(/\{if \$message !== ''\}[\s\S]*?\{\/if\}/g, '');
    template = template.replace(/\{if \$updateNotice !== null\}[\s\S]*?\{\/if\}/g, '');
    template = template.replace(/\{if \$language === '(auto|de|en)'\} selected\{\/if\}/g,
        (_, language) => language === 'auto' ? ' selected' : '');
    template = template.replace(/\{\$(\w+)\|escape:'html':'UTF-8'\}/g, (_, name) => {
        if (!(name in testValues)) throw new Error(`Unbekannter Testwert: ${name}`);
        return testValues[name];
    });
    if (/[{}]/.test(template)) throw new Error('Nicht unterstützte Smarty-Direktive in der Browserfixture.');
    template = template.replace('<span class="mgd-display__label"', '<span id="local-test-label" class="mgd-display__label"');
    const light = url.searchParams.get('theme') === 'light';
    // Die kleine CSS-Hülle bildet nur die umgebende Adminfläche ab, nicht das Plugin.
    return `<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lokaler Darstellungstest – keine Shopverbindung</title><style>
*{box-sizing:border-box}body{margin:0;background:${light ? '#eef1ef' : '#24272d'};font-family:system-ui,sans-serif;font-size:16px;line-height:1.5}
main{max-width:1250px;margin:24px auto;padding:16px}.card-body{padding:1.25rem}h1,h2,p{margin-top:0}h1{font-size:2rem}h2{font-size:1.25rem}nav{padding:12px;background:#fff}nav a{margin-right:16px;color:#14572c}
</style>${url.searchParams.has('embedded') ? '<script type="module" src="/measure.mjs"></script>' : '<nav aria-label="Testumgebung"><a href="/">Dunkel</a><a href="/?theme=light">Hell</a><a href="/?width=narrow">Schmal</a><span>Nur lokaler Test · Speichern ist gesperrt</span></nav>'}<main>${template}</main></html>`;
}

const server = createServer(async (request, response) => {
    response.setHeader('Cache-Control', 'no-store');
    response.setHeader('Content-Security-Policy', "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self'; script-src 'self'; connect-src 'none'; form-action 'none'");
    try {
        if (request.method !== 'GET') { response.writeHead(405); response.end('Keine Speicherung im lokalen Test.'); return; }
        const url = new URL(request.url, 'http://127.0.0.1');
        if (url.pathname === '/measure.mjs') {
            response.setHeader('Content-Type', 'text/javascript');
            response.end(await readFile(new URL('display-preview-measure.mjs', import.meta.url)));
            return;
        }
        if (url.pathname === '/') {
            response.setHeader('Content-Type', 'text/html; charset=utf-8');
            response.end(await renderFixture(url));
            return;
        }
        const asset = url.pathname.replace(/^\/assets\//, '');
        if (!url.pathname.startsWith('/assets/') || !assets.has(asset)) { response.writeHead(404); response.end(); return; }
        response.setHeader('Content-Type', assets.get(asset));
        response.end(await readFile(fileURLToPath(new URL(asset, admin))));
    } catch (error) {
        console.error(error.message);
        response.writeHead(500);
        response.end('Lokale Testfixture konnte nicht geladen werden.');
    }
});
server.listen(0, '127.0.0.1', () => console.log(`Lokaler Darstellungstest: http://127.0.0.1:${server.address().port}/`));
