{* Dieses Formular darf nur aus einem berechtigten, CSRF-geprüften Admin-Endpunkt eingebunden werden. *}
<link rel="stylesheet" href="{$adminUrl|escape:'html':'UTF-8'}philosophy.css">
<section aria-labelledby="mgd-ai-philosophy-heading">
    <h1 id="mgd-ai-philosophy-heading">AI-Philosophie pflegen</h1>
    <p>Erlaubt sind Absätze, Überschriften, Listen, Hervorhebungen und sichere HTTPS-Links.</p>
    {if $message !== ''}<p role="status">{$message|escape:'html':'UTF-8'}</p>{/if}
    <form method="post" class="mgd-philosophy-form" data-philosophy-form>
        <input type="hidden" name="kPlugin" value="{$pluginId|escape:'html':'UTF-8'}">
        <input type="hidden" name="kPluginAdminMenu" value="{$adminMenuId|escape:'html':'UTF-8'}">
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html':'UTF-8'}">
        <section class="mgd-philosophy-language" data-philosophy-language="de">
            <h2>Deutsch</h2>
            <label for="mgd-ai-philosophy-de" data-philosophy-source-label>Deutscher Inhalt</label>
            <textarea id="mgd-ai-philosophy-de" name="content_de" rows="18" data-philosophy-source>{$contentDe|escape:'html':'UTF-8'}</textarea>
        </section>
        <section class="mgd-philosophy-language" data-philosophy-language="en">
            <h2>English</h2>
            <label for="mgd-ai-philosophy-en" data-philosophy-source-label>Englischer Inhalt</label>
            <textarea id="mgd-ai-philosophy-en" name="content_en" rows="18" data-philosophy-source>{$contentEn|escape:'html':'UTF-8'}</textarea>
        </section>
        <button type="submit">Beide Sprachfassungen speichern</button>
    </form>
</section>
<script type="module" src="{$adminUrl|escape:'html':'UTF-8'}js/philosophy-editor.mjs"></script>
