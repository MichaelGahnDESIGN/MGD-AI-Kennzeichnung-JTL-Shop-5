{* Dieses Formular darf nur aus einem berechtigten, CSRF-geprüften Admin-Endpunkt eingebunden werden. *}
<section aria-labelledby="mgd-ai-philosophy-heading">
    <h1 id="mgd-ai-philosophy-heading">AI-Philosophie pflegen</h1>
    <p>Erlaubt sind Absätze, Überschriften, Listen, Hervorhebungen und sichere HTTPS-Links.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html':'UTF-8'}">
        <label for="mgd-ai-philosophy-de">Deutscher Inhalt</label>
        <textarea id="mgd-ai-philosophy-de" name="content_de" rows="14">{$contentDe|escape:'html':'UTF-8'}</textarea>
        <label for="mgd-ai-philosophy-en">Englischer Inhalt</label>
        <textarea id="mgd-ai-philosophy-en" name="content_en" rows="14">{$contentEn|escape:'html':'UTF-8'}</textarea>
        <button type="submit">Beide Sprachfassungen speichern</button>
    </form>
</section>
