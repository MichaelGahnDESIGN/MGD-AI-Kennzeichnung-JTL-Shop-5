{* Dieses Template erhält ausschließlich ein bereits geprüftes LabelView-Modell. *}
{if $mgdAiLabel.visible}
    <span
        class="mgd-ai-label mgd-ai-label--native {$mgdAiLabel.statusClass|escape:'html':'UTF-8'} {$mgdAiLabel.positionClass|escape:'html':'UTF-8'} {$mgdAiLabel.themeClass|escape:'html':'UTF-8'} {$mgdAiLabel.sourceClass|escape:'html':'UTF-8'}"
        role="note"
        aria-label="{$mgdAiLabel.assistiveText|escape:'html':'UTF-8'}"
        style="--mgd-ai-font-size:{$mgdAiLabel.fontSize|escape:'html':'UTF-8'}px;--mgd-ai-outer-margin:{$mgdAiLabel.outerMargin|escape:'html':'UTF-8'}px;--mgd-ai-inner-padding:{$mgdAiLabel.innerPadding|escape:'html':'UTF-8'}px;--mgd-ai-border-radius:{$mgdAiLabel.borderRadius|escape:'html':'UTF-8'}px;--mgd-ai-blur:{$mgdAiLabel.blur|escape:'html':'UTF-8'}px"
    >{$mgdAiLabel.visibleText|escape:'html':'UTF-8'}</span>
{/if}
