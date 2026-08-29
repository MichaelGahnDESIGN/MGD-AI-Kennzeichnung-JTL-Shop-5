<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/** Prüft die technische Eignung des lokalen Vorschaubildes im Administrationsbereich. */
final class DisplayAdminContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../';
    private const MAXIMALE_DATEIGROESSE = 2_000_000;

    #[Test]
    public function lokales_vorschaubild_ist_ein_kompaktes_png_mit_ausreichender_aufloesung(): void
    {
        $image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
        self::assertFileExists($image);

        $dateigroesse = filesize($image);
        self::assertIsInt($dateigroesse);
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, $dateigroesse);

        $size = getimagesize($image);
        self::assertIsArray($size);
        self::assertSame('image/png', $size['mime']);
        self::assertGreaterThanOrEqual(800, $size[0]);
        self::assertGreaterThanOrEqual(800, $size[1]);
    }

    #[Test]
    public function lokales_vorschaubild_enthaelt_keine_generierungs_oder_textmetadaten(): void
    {
        $image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
        self::assertFileExists($image);

        $chunkTypes = $this->lesePngChunkTypen($image);

        foreach (['caBX', 'eXIf', 'iTXt', 'tEXt', 'zTXt'] as $verbotenerChunkTyp) {
            self::assertNotContains(
                $verbotenerChunkTyp,
                $chunkTypes,
                sprintf('Das Vorschaubild darf keinen %s-Metadatenchunk enthalten.', $verbotenerChunkTyp),
            );
        }
    }

    #[Test]
    public function png_vertrag_lehnt_unbekannten_ancillary_chunk_mit_generator_url_ab(): void
    {
        $png = $this->erstelleSynthetischesPng([
            ['ruLE', 'https://generator.example.invalid/manifest'],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->pruefePngChunkStruktur($png);
    }

    #[Test]
    public function png_vertrag_lehnt_daten_hinter_dem_iend_chunk_ab(): void
    {
        $png = $this->erstelleSynthetischesPng([], 'angehaengte-daten');

        $this->expectException(AssertionFailedError::class);
        $this->pruefePngChunkStruktur($png);
    }

    #[Test]
    public function darstellungstab_bietet_ein_barrierearmes_lokales_zweispaltenformular(): void
    {
        $root = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/';
        $template = (string) file_get_contents($root . 'templates/display.tpl');
        $stylesheet = (string) file_get_contents($root . 'display.css');
        $controls = (string) file_get_contents($root . 'js/display-controls.mjs');
        $previewModel = (string) file_get_contents($root . 'js/display-preview.mjs');

        self::assertFileExists($root . 'display.php');
        self::assertNotSame('', $template);
        self::assertNotSame('', $stylesheet);
        self::assertStringContainsString('<form method="post" data-mgd-display-form>', $template);
        self::assertStringContainsString('name="csrf_token"', $template);
        self::assertStringContainsString('name="kPlugin"', $template);
        self::assertStringContainsString('name="kPluginAdminMenu"', $template);
        self::assertStringContainsString('<fieldset', $template);
        self::assertStringContainsString('<legend>', $template);
        self::assertStringContainsString('aria-live="polite"', $template);
        self::assertStringContainsString('<button type="submit">Speichern</button>', $template);
        self::assertStringContainsString('images/michael-gahn-design-schuh.png', $template);
        self::assertStringContainsString('alt="Fiktiver Michael Gahn DESIGN Schuh"', $template);
        self::assertStringContainsString('KI-GENERIERT', $template);
        self::assertStringContainsString('Nur Vorschau', $template);
        self::assertStringContainsString('display.css', $template);
        self::assertStringContainsString('data-mgd-display-root', $template);
        self::assertStringContainsString('data-mgd-display-form', $template);
        self::assertStringContainsString('data-mgd-display-label aria-live="polite"', $template);
        self::assertStringContainsString('type="module"', $template);
        self::assertStringContainsString('js/display-controls.mjs', $template);
        self::assertFileExists($root . 'js/display-range-sync.mjs');
        self::assertFileExists($root . 'js/display-preview.mjs');
        self::assertFileExists($root . 'js/display-controls.mjs');
        self::assertStringContainsString("[data-mgd-display-form]", $controls);
        self::assertStringContainsString('Object.prototype.hasOwnProperty.call', $previewModel);
        self::assertStringNotContainsString('Object.hasOwn(', $previewModel);
        self::assertDoesNotMatchRegularExpression('~(?:src|href)="https?://~i', $template);
        self::assertDoesNotMatchRegularExpression('~\bon\w+\s*=~i', $template);
        self::assertStringNotContainsString('<style', strtolower($template));
        self::assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)~i', $template);

        foreach (['language', 'font_size', 'outer_margin', 'inner_padding', 'border_radius', 'blur', 'transparency'] as $field) {
            self::assertStringContainsString('name="' . $field . '"', $template);
            self::assertStringContainsString('id="mgd-display-' . $field, $template);
        }
        self::assertStringContainsString('data-mgd-display-control', $template);
        self::assertStringContainsString('px', $template);
        self::assertStringContainsString('%', $template);
        self::assertStringContainsString('name="preview_position"', $template);
        self::assertStringContainsString('name="preview_theme"', $template);
        self::assertStringContainsString('<option value="bottom-right" selected>', $template);
        self::assertStringContainsString('mgd-display-preview--bottom-right mgd-display-preview--theme-auto', $template);

        foreach ([
            'font_size' => '<input id="mgd-display-font_size" name="font_size" type="number" min="8" max="48" step="1"',
            'outer_margin' => '<input id="mgd-display-outer_margin" name="outer_margin" type="number" min="0" max="64" step="1"',
            'inner_padding' => '<input id="mgd-display-inner_padding" name="inner_padding" type="number" min="0" max="32" step="1"',
            'border_radius' => '<input id="mgd-display-border_radius-number" name="border_radius" type="number" min="0" max="32" step="1"',
            'blur' => '<input id="mgd-display-blur-number" name="blur" type="number" min="0" max="24" step="1"',
            'transparency' => '<input id="mgd-display-transparency-number" name="transparency" type="number" min="0" max="90" step="1"',
        ] as $field => $expectedInput) {
            self::assertStringContainsString($expectedInput, $template, sprintf('Das Zahlenfeld %s benötigt seine exakten Grenzen.', $field));
        }
        foreach ([
            'border_radius' => ['borderRadius', '0', '32'],
            'blur' => ['blur', '0', '24'],
            'transparency' => ['transparency', '0', '90'],
        ] as $field => [$setting, $minimum, $maximum]) {
            self::assertStringContainsString(
                sprintf('<input id="mgd-display-%s-range" type="range" min="%s" max="%s" step="1"', $field, $minimum, $maximum),
                $template,
                sprintf('Der Regler %s muss exakt zum Zahlenfeld passen.', $field),
            );
            self::assertStringContainsString(
                sprintf('data-mgd-number data-mgd-setting="%s"', $setting),
                $template,
                sprintf('Das Zahlenfeld %s braucht einen stabilen gemeinsamen Paar-Schlüssel.', $field),
            );
            self::assertStringContainsString(
                sprintf('data-mgd-range data-mgd-setting="%s"', $setting),
                $template,
                sprintf('Der Regler %s braucht einen stabilen gemeinsamen Paar-Schlüssel.', $field),
            );
        }
        foreach (['border-radius', 'blur', 'transparency'] as $field) {
            self::assertStringContainsString('aria-describedby="mgd-display-' . $field . '-help"', $template);
            self::assertStringContainsString('id="mgd-display-' . $field . '-help"', $template);
            self::assertStringContainsString('aria-labelledby="mgd-display-' . $field . '-legend"', $template);
        }

        self::assertStringContainsString('.mgd-display-layout {', $stylesheet);
        self::assertStringContainsString('grid-template-columns: minmax(20rem, 0.9fr) minmax(22rem, 1.1fr);', $stylesheet);
        self::assertStringContainsString('gap: 1.5rem;', $stylesheet);
        self::assertStringContainsString('align-items: start;', $stylesheet);
        self::assertStringContainsString('@media (max-width: 62rem)', $stylesheet);
        self::assertStringContainsString('.mgd-display-layout { grid-template-columns: 1fr; }', $stylesheet);
        foreach ([
            '--mgd-preview-font-size: 12px;',
            '--mgd-preview-outer-margin: 8px;',
            '--mgd-preview-inner-padding: 6px;',
            '--mgd-preview-border-radius: 4px;',
            '--mgd-preview-blur: 0px;',
            '--mgd-preview-background-opacity: 0.92;',
            '.mgd-display-preview--top-right',
            '.mgd-display-preview--bottom-right',
            '.mgd-display-preview--top-left',
            '.mgd-display-preview--bottom-left',
            '.mgd-display-preview--theme-auto',
            '.mgd-display-preview--theme-light',
            '.mgd-display-preview--theme-dark',
            'box-sizing: border-box;',
            'max-width: calc(100% - var(--mgd-preview-outer-margin) - var(--mgd-preview-outer-margin));',
            'overflow-wrap: anywhere;',
            'word-break: normal;',
            '@media (prefers-color-scheme: light)',
            '.mgd-display-preview--theme-auto .mgd-display__label { background: rgba(255, 255, 255, var(--mgd-preview-background-opacity)); border-color: rgba(23, 33, 27, .4); color: #17211b; }',
            'prefers-reduced-motion: reduce',
        ] as $expectedCss) {
            self::assertStringContainsString($expectedCss, $stylesheet);
        }
    }

    #[Test]
    public function darstellungseinstieg_schliesst_unautorisierte_und_ungesicherte_anfragen(): void
    {
        $entryPoint = (string) file_get_contents(self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/display.php');

        self::assertStringContainsString("defined('PFAD_ROOT')", $entryPoint);
        self::assertStringContainsString('JtlAuthorizationAdapter', $entryPoint);
        self::assertStringContainsString('JtlDisplayConfigAdapter', $entryPoint);
        self::assertStringContainsString('DisplaySettingsAdminService', $entryPoint);
        self::assertStringContainsString('DisplayConfigCommittedException', $entryPoint);
        self::assertStringContainsString('AdminTabScope::capture', $entryPoint);
        self::assertStringContainsString("'kPlugin'", $entryPoint);
        self::assertStringContainsString("'kPluginAdminMenu'", $entryPoint);
        self::assertStringContainsString('array_diff_key($request->post', $entryPoint);
        self::assertStringContainsString("'display_request_failed'", $entryPoint);
        self::assertStringContainsString("'display_cache_invalidation_failed'", $entryPoint);
        self::assertStringNotContainsString("'count' => 0", $entryPoint);
        self::assertStringContainsString('AdminTabScope::error', $entryPoint);
        self::assertSame(1, substr_count($entryPoint, 'http_response_code(403)'));
        self::assertStringNotContainsString('http_response_code(400)', $entryPoint);
        self::assertStringNotContainsString('http_response_code(500)', $entryPoint);
        self::assertStringNotContainsString('$_COOKIE', $entryPoint);
        self::assertStringNotContainsString('var_dump', $entryPoint);
    }

    #[Test]
    public function updatehinweis_ist_auf_den_adressierten_darstellungstab_und_einen_sicheren_releaselink_begrenzt(): void
    {
        $root = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/';
        $entryPoint = (string) file_get_contents($root . 'display.php');
        $template = (string) file_get_contents($root . 'templates/display.tpl');

        self::assertStringContainsString('GitHubReleaseChecker', $entryPoint);
        self::assertStringContainsString('FileReleaseCache', $entryPoint);
        self::assertStringContainsString('$scope->isAddressed', $entryPoint);
        self::assertStringContainsString("'update_notices'", $entryPoint);
        self::assertStringContainsString('$updateNotice', $entryPoint);
        self::assertStringContainsString('{if $updateNotice !== null}', $template);
        self::assertStringContainsString('{$updateNotice->tag|escape:', $template);
        self::assertStringContainsString('{$updateNotice->url|escape:', $template);
        self::assertStringContainsString('target="_blank" rel="noopener noreferrer"', $template);
        self::assertStringContainsString('aria-label=', $template);
        self::assertStringNotContainsString('javascript:', strtolower($template));
    }

    /**
     * Liest eine maximal 2 MB große PNG-Datei und prüft ausschließlich ihre Chunk-Kopfzeilen.
     *
     * @return list<string>
     */
    private function lesePngChunkTypen(string $image): array
    {
        $dateigroesse = filesize($image);
        self::assertIsInt($dateigroesse);
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, $dateigroesse);

        $binary = file_get_contents($image);
        self::assertIsString($binary);

        return $this->pruefePngChunkStruktur($binary);
    }

    /**
     * Prüft eine maximal 2 MB große PNG-Bytefolge ausschließlich anhand ihrer Chunk-Kopfzeilen.
     *
     * @return list<string>
     */
    private function pruefePngChunkStruktur(string $binary): array
    {
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, strlen($binary));
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $binary);

        $offset = 8;
        $laenge = strlen($binary);
        $chunkTypes = [];

        while ($offset + 12 <= $laenge) {
            $chunkLaenge = unpack('Nlength', substr($binary, $offset, 4));
            self::assertIsArray($chunkLaenge);
            self::assertIsInt($chunkLaenge['length'] ?? null);

            $chunkType = substr($binary, $offset + 4, 4);
            self::assertSame(4, strlen($chunkType));
            self::assertContains(
                $chunkType,
                ['IHDR', 'IDAT', 'IEND'],
                sprintf('Der PNG-Chunktyp %s ist im minimalen Vorschaubild nicht erlaubt.', $chunkType),
            );
            $chunkTypes[] = $chunkType;

            $offset += 12 + $chunkLaenge['length'];
            self::assertLessThanOrEqual($laenge, $offset, 'Der PNG-Chunk ist unvollständig.');

            if ($chunkType === 'IEND') {
                self::assertSame(0, $chunkLaenge['length'], 'Der IEND-Chunk darf keine Daten enthalten.');
                self::assertSame($laenge, $offset, 'Nach dem IEND-Chunk dürfen keine Daten folgen.');
                break;
            }
        }

        self::assertContains('IEND', $chunkTypes, 'Die PNG-Datei muss mit einem IEND-Chunk enden.');

        return $chunkTypes;
    }

    /**
     * Erstellt eine minimale, nur für die Strukturprüfung geeignete PNG-Bytefolge.
     * Die CRC-Werte sind für den getesteten Chunk-Vertrag absichtlich nicht relevant.
     *
     * @param list<array{0: string, 1: string}> $zusaetzlicheChunks
     */
    private function erstelleSynthetischesPng(array $zusaetzlicheChunks, string $datenNachIend = ''): string
    {
        $header = "\x89PNG\r\n\x1a\n";
        $ihdr = $this->erstellePngChunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0));
        $idat = $this->erstellePngChunk('IDAT', '');
        $iend = $this->erstellePngChunk('IEND', '');
        $chunks = '';

        foreach ($zusaetzlicheChunks as [$typ, $daten]) {
            $chunks .= $this->erstellePngChunk($typ, $daten);
        }

        return $header . $ihdr . $chunks . $idat . $iend . $datenNachIend;
    }

    /** Erstellt einen PNG-Chunk mit einem für diesen Strukturtest nicht ausgewerteten CRC-Platzhalter. */
    private function erstellePngChunk(string $typ, string $daten): string
    {
        self::assertSame(4, strlen($typ));

        return pack('N', strlen($daten)) . $typ . $daten . pack('N', 0);
    }
}
