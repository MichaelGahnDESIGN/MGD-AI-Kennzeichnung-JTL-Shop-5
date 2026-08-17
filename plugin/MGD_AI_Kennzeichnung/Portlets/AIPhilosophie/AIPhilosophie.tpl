{* Der Methodenwert wurde beim Speichern und nochmals beim Lesen bereinigt. *}
<section
    class="mgd-ai-philosophy"
    aria-label="AI-Philosophie"
    {if $isPreview}data-portlet="{$instance->getDataAttribute()}"{/if}
>
    {assign var=mgdAiPhilosophy value=$portlet->getSanitizedContent()}
    {if $mgdAiPhilosophy !== ''}
        {$mgdAiPhilosophy}
    {elseif $isPreview}
        <p>Für die aktuelle Shopsprache ist noch keine AI-Philosophie hinterlegt.</p>
    {/if}
</section>
