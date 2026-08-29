{* Lokaler, CSRF-geschützter Darstellungstab ohne externe Ressourcen oder persistente Vorschauoptionen. *}
<link rel="stylesheet" href="{$adminUrl|escape:'html':'UTF-8'}display.css">
<script type="module" src="{$adminUrl|escape:'html':'UTF-8'}js/display-controls.mjs"></script>
<section class="mgd-display container-fluid" aria-labelledby="mgd-display-heading" data-mgd-display-root>
    <header class="mgd-display__header">
        <p class="mgd-display__eyebrow">MGD AI Kennzeichnung</p>
        <h1 id="mgd-display-heading">Darstellung</h1>
        <p class="mgd-display__intro">Globale Einstellungen für die KI-Kennzeichnung sicher und nachvollziehbar anpassen.</p>
    </header>

    <div class="mgd-display__notice" aria-live="polite">
        {if $message !== ''}<p class="alert alert-success" role="status">{$message|escape:'html':'UTF-8'}</p>{/if}
    </div>

    <div class="mgd-display-layout">
        <form method="post" data-mgd-display-form>
            <div class="card-body">
                <input type="hidden" name="kPlugin" value="{$pluginId|escape:'html':'UTF-8'}">
                <input type="hidden" name="kPluginAdminMenu" value="{$adminMenuId|escape:'html':'UTF-8'}">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html':'UTF-8'}">

                <fieldset class="mgd-display__fieldset">
                    <legend>Globale Einstellungen</legend>
                    <p class="mgd-display__help">Diese Werte gelten für alle vom Plugin ausgegebenen Kennzeichnungen.</p>

                    <div class="mgd-display__field">
                        <label for="mgd-display-language">Sprache</label>
                        <select id="mgd-display-language" name="language" data-mgd-display-control="language">
                            <option value="auto"{if $language === 'auto'} selected{/if}>Automatisch</option>
                            <option value="de"{if $language === 'de'} selected{/if}>Deutsch</option>
                            <option value="en"{if $language === 'en'} selected{/if}>Englisch</option>
                        </select>
                    </div>

                    <div class="mgd-display__field">
                        <label for="mgd-display-font_size">Schriftgröße <span>in px</span></label>
                        <input id="mgd-display-font_size" name="font_size" type="number" min="8" max="48" step="1" value="{$fontSize|escape:'html':'UTF-8'}" data-mgd-display-control="font_size">
                        <small>8–48 px</small>
                    </div>
                    <div class="mgd-display__field">
                        <label for="mgd-display-outer_margin">Außenabstand <span>in px</span></label>
                        <input id="mgd-display-outer_margin" name="outer_margin" type="number" min="0" max="64" step="1" value="{$outerMargin|escape:'html':'UTF-8'}" data-mgd-display-control="outer_margin">
                        <small>0–64 px</small>
                    </div>
                    <div class="mgd-display__field">
                        <label for="mgd-display-inner_padding">Innenabstand <span>in px</span></label>
                        <input id="mgd-display-inner_padding" name="inner_padding" type="number" min="0" max="32" step="1" value="{$innerPadding|escape:'html':'UTF-8'}" data-mgd-display-control="inner_padding">
                        <small>0–32 px</small>
                    </div>

                    <fieldset class="mgd-display__range-fieldset">
                        <legend id="mgd-display-border-radius-legend">Eckenradius <span>in px</span></legend>
                        <div class="mgd-display__range-pair">
                            <input id="mgd-display-border_radius-number" name="border_radius" type="number" min="0" max="32" step="1" value="{$borderRadius|escape:'html':'UTF-8'}" data-mgd-number data-mgd-setting="borderRadius" aria-labelledby="mgd-display-border-radius-legend" aria-describedby="mgd-display-border-radius-help">
                            <input id="mgd-display-border_radius-range" type="range" min="0" max="32" step="1" value="{$borderRadius|escape:'html':'UTF-8'}" data-mgd-range data-mgd-setting="borderRadius" aria-labelledby="mgd-display-border-radius-legend" aria-describedby="mgd-display-border-radius-help">
                        </div>
                        <small id="mgd-display-border-radius-help">0–32 px</small>
                    </fieldset>
                    <fieldset class="mgd-display__range-fieldset">
                        <legend id="mgd-display-blur-legend">Hintergrundunschärfe <span>in px</span></legend>
                        <div class="mgd-display__range-pair">
                            <input id="mgd-display-blur-number" name="blur" type="number" min="0" max="24" step="1" value="{$blur|escape:'html':'UTF-8'}" data-mgd-number data-mgd-setting="blur" aria-labelledby="mgd-display-blur-legend" aria-describedby="mgd-display-blur-help">
                            <input id="mgd-display-blur-range" type="range" min="0" max="24" step="1" value="{$blur|escape:'html':'UTF-8'}" data-mgd-range data-mgd-setting="blur" aria-labelledby="mgd-display-blur-legend" aria-describedby="mgd-display-blur-help">
                        </div>
                        <small id="mgd-display-blur-help">0–24 px</small>
                    </fieldset>
                    <fieldset class="mgd-display__range-fieldset">
                        <legend id="mgd-display-transparency-legend">Transparenz <span>in %</span></legend>
                        <div class="mgd-display__range-pair">
                            <input id="mgd-display-transparency-number" name="transparency" type="number" min="0" max="90" step="1" value="{$transparency|escape:'html':'UTF-8'}" data-mgd-number data-mgd-setting="transparency" aria-labelledby="mgd-display-transparency-legend" aria-describedby="mgd-display-transparency-help">
                            <input id="mgd-display-transparency-range" type="range" min="0" max="90" step="1" value="{$transparency|escape:'html':'UTF-8'}" data-mgd-range data-mgd-setting="transparency" aria-labelledby="mgd-display-transparency-legend" aria-describedby="mgd-display-transparency-help">
                        </div>
                        <small id="mgd-display-transparency-help">0–90 %</small>
                    </fieldset>
                </fieldset>

                <button type="submit">Speichern</button>
            </div>
        </form>

        <aside class="mgd-display__preview card" aria-labelledby="mgd-display-preview-heading">
            <div class="card-body">
                <p class="mgd-display__eyebrow">Lokale Vorschau</p>
                <h2 id="mgd-display-preview-heading" class="h4">Kennzeichnung am Beispielbild</h2>
                <div class="mgd-display__image-wrap mgd-display-preview--bottom-right mgd-display-preview--theme-auto" data-mgd-display-preview>
                    <img src="{$adminUrl|escape:'html':'UTF-8'}images/michael-gahn-design-schuh.png" alt="Fiktiver Michael Gahn DESIGN Schuh">
                    <span class="mgd-display__label" data-mgd-display-label aria-live="polite">KI-GENERIERT</span>
                </div>
                <p class="mgd-display__help">Die folgenden Optionen ändern nur diese Vorschau und werden nicht gespeichert.</p>
                <div class="mgd-display__preview-controls">
                    <label for="mgd-display-preview-position">Position <span>Nur Vorschau</span></label>
                    <select id="mgd-display-preview-position" name="preview_position" data-mgd-display-preview-position>
                        <option value="bottom-right" selected>Unten rechts</option>
                        <option value="bottom-left">Unten links</option>
                        <option value="top-right">Oben rechts</option>
                        <option value="top-left">Oben links</option>
                    </select>
                    <label for="mgd-display-preview-theme">Farbschema <span>Nur Vorschau</span></label>
                    <select id="mgd-display-preview-theme" name="preview_theme" data-mgd-display-preview-theme>
                        <option value="auto">Automatisch</option>
                        <option value="light">Hell</option>
                        <option value="dark">Dunkel</option>
                    </select>
                </div>
            </div>
        </aside>
    </div>
</section>
