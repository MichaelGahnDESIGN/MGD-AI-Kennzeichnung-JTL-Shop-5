<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Presentation;

use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;
use Plugin\MGD_AI_Kennzeichnung\Service\LabelViewResolver;

/**
 * Ergänzt ausschließlich lokale Plugin-Assets im bereits erzeugten JTL-Dokument.
 *
 * Die lose Dokumentgrenze ist notwendig, weil JTL 5.7.2 im Outputfilter ein
 * phpQuery-Dokument übergibt. Vor jedem Methodenaufruf wird der Laufzeitvertrag
 * geprüft; ein unerwarteter Hook-Kontext bleibt dadurch ohne Shopfehler.
 */
final class FrontendDocumentIntegrator
{
    /** @param array<mixed> $hookArguments Unformatierter JTL-Hook-Kontext */
    public function integrate(array $hookArguments, string $frontendUrl, bool $showCredit): void
    {
        $dokument = $hookArguments['document'] ?? null;
        if (!is_object($dokument) || !$this->isSafeFrontendUrl($frontendUrl)) {
            return;
        }

        $basis = rtrim($frontendUrl, '/');
        $css = $this->escape($basis . '/css/mgd-ai-labels.css');
        $javascript = $this->escape($basis . '/js/mgd-ai-marked-elements.js');
        $this->append($dokument, 'head', '<link rel="stylesheet" href="' . $css . '" data-mgd-ai-asset="labels">');

        $bodyMarkup = '<script src="' . $javascript . '" defer data-mgd-ai-asset="marked-elements"></script>';
        if ($showCredit) {
            $bodyMarkup .= (new FooterCreditRenderer())->render(true);
        }
        $this->append($dokument, 'body', $bodyMarkup);
    }

    /**
     * Ergänzt native Kennzeichnungen ausschließlich an Bildern, deren geprüfter
     * Dateiname in einem sichtbaren Plugin-Datensatz vorkommt.
     *
     * Die Datenbankwerte werden an dieser Ausgabegrenze erneut normalisiert.
     * Unsichere Pfade, unbekannte Zeilentypen und doppelte Dateinamen werden
     * verworfen. So entsteht weder ein frei formulierter CSS-Selektor noch eine
     * pauschale Suche über alle Bilder des Shops.
     *
     * @param array<mixed> $hookArguments Unformatierter JTL-Hook-Kontext
     * @param list<array{local_path: string, status: string, position: string, theme: string, source_type: string}> $labels
     */
    public function integrateLabels(
        array $hookArguments,
        array $labels,
        DisplaySettings $settings,
        string $locale,
    ): void {
        $dokument = $hookArguments['document'] ?? null;
        if (!is_object($dokument)) {
            return;
        }

        $verwendeteDateinamen = [];
        $resolver = new LabelViewResolver();
        $renderer = new LabelRenderer();
        $locator = new FrontendLabelTargetLocator();

        foreach ($labels as $label) {
            $dateiname = basename(str_replace('\\', '/', $label['local_path']));
            if (!$this->isSafeFilename($dateiname) || isset($verwendeteDateinamen[$dateiname])) {
                continue;
            }
            $verwendeteDateinamen[$dateiname] = true;

            $view = $resolver->resolve(
                status: $label['status'],
                position: $label['position'],
                theme: $label['theme'],
                language: $settings->language,
                locale: $locale,
                assetSource: $label['source_type'],
                fontSize: $settings->fontSize,
                outerMargin: $settings->outerMargin,
                innerPadding: $settings->innerPadding,
                borderRadius: $settings->borderRadius,
                blur: $settings->blur,
            );
            $markup = $renderer->render($view);
            if ($markup === '') {
                continue;
            }

            $bilder = $this->find($dokument, $locator->imageSelector($dateiname));
            $direkteEltern = $this->parentOf($bilder);
            $pictureElemente = $this->callObjectMethod($direkteEltern, 'filter', 'picture');
            $direkteRahmen = $this->callObjectMethod($direkteEltern, 'not', 'picture');

            // Ein <picture> darf außer <source> und <img> keine Label-Elemente
            // enthalten. Deshalb wird sein äußerer Link oder Block verwendet.
            $pictureRahmen = $this->parentOf($pictureElemente);
            $this->decorateImageHosts($pictureRahmen, $markup);
            $this->decorateImageHosts($direkteRahmen, $markup);

            // OPC-Container geben statische und Parallax-Bilder nicht als
            // <img>, sondern als style- oder data-image-src-Attribut aus.
            $hintergruende = $this->find($dokument, $locator->backgroundSelector($dateiname));
            $this->decorateHosts($hintergruende, $markup, false);
        }
    }

    /**
     * Trennt echte Inline-Links von bereits blockförmigen JTL-Rahmen.
     *
     * Nur Links benötigen eine display-Korrektur. Ein vorhandener Block darf
     * seine Breite, Zentrierung oder Rastereigenschaften unverändert behalten.
     */
    private function decorateImageHosts(?object $hosts, string $markup): void
    {
        if ($hosts === null) {
            return;
        }

        $links = $this->callObjectMethod($hosts, 'filter', 'a');
        $blocks = $this->callObjectMethod($hosts, 'not', 'a');
        $this->decorateHosts($links, $markup, true);
        $this->decorateHosts($blocks, $markup, false);
    }

    /** Fügt ein Label genau einmal in die gefundenen Positionsrahmen ein. */
    private function decorateHosts(?object $hosts, string $markup, bool $inline): void
    {
        if ($hosts === null) {
            return;
        }

        $neueHosts = $this->callObjectMethod($hosts, 'not', '.mgd-ai-label-host');
        if ($neueHosts === null) {
            return;
        }

        $this->callVoidMethod($neueHosts, 'addClass', 'mgd-ai-label-host');
        if ($inline) {
            $this->callVoidMethod($neueHosts, 'addClass', 'mgd-ai-label-host--inline');
        }
        $this->callVoidMethod($neueHosts, 'append', $markup);
    }

    private function parentOf(?object $elements): ?object
    {
        return $elements === null ? null : $this->callObjectMethod($elements, 'parent');
    }

    private function append(object $dokument, string $selector, string $markup): void
    {
        $ziel = $this->find($dokument, $selector);
        if ($ziel === null) {
            return;
        }
        $this->callVoidMethod($ziel, 'append', $markup);
    }

    private function find(object $dokument, string $selector): ?object
    {
        return $this->callObjectMethod($dokument, 'find', $selector);
    }

    private function callObjectMethod(?object $objekt, string $methode, ?string $argument = null): ?object
    {
        if ($objekt === null) {
            return null;
        }

        $aufruf = [$objekt, $methode];
        if (!is_callable($aufruf)) {
            return null;
        }
        $ergebnis = $argument === null ? $aufruf() : $aufruf($argument);

        return is_object($ergebnis) ? $ergebnis : null;
    }

    private function callVoidMethod(object $objekt, string $methode, string $argument): void
    {
        $aufruf = [$objekt, $methode];
        if (is_callable($aufruf)) {
            $aufruf($argument);
        }
    }

    private function isSafeFilename(string $dateiname): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/D', $dateiname) === 1;
    }

    private function isSafeFrontendUrl(string $url): bool
    {
        $teile = parse_url($url);

        return is_array($teile)
            && ($teile['scheme'] ?? null) === 'https'
            && is_string($teile['host'] ?? null)
            && $teile['host'] !== ''
            && !isset($teile['user'])
            && !isset($teile['pass']);
    }

    private function escape(string $wert): string
    {
        return htmlspecialchars($wert, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
