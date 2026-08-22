<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminTemplateContractTest extends TestCase
{
    #[Test]
    public function bildverwaltung_besteht_aus_responsiver_barrierearmer_galerie(): void
    {
        $root = dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung';
        $partials = [
            $root . '/adminmenu/templates/partials/asset-filter.php',
            $root . '/adminmenu/templates/partials/asset-card.php',
            $root . '/adminmenu/templates/partials/gallery-toolbar.php',
            $root . '/adminmenu/templates/partials/label-dialog.php',
        ];

        foreach ($partials as $partial) {
            self::assertFileExists($partial);
        }

        $list = (string) file_get_contents($root . '/adminmenu/templates/assets-list.php');
        $filter = (string) file_get_contents($partials[0]);
        $card = (string) file_get_contents($partials[1]);
        $toolbar = (string) file_get_contents($partials[2]);
        $dialog = (string) file_get_contents($partials[3]);
        $css = (string) file_get_contents($root . '/adminmenu/assets.css');
        $handler = (string) file_get_contents($root . '/Admin/Action/AdminActionHandler.php');
        $factory = (string) file_get_contents($root . '/Admin/Factory/AdminRuntimeFactory.php');

        self::assertStringContainsString('assetStyleUrl', $list . $handler . $factory);
        self::assertStringContainsString("getAdminURL() . 'assets.css'", $factory);
        self::assertStringContainsString('Ergebnisse', $toolbar);
        self::assertStringContainsString('Sicheren Bildscan starten', $toolbar);
        self::assertStringContainsString('loading="lazy"', $card);
        self::assertStringContainsString('Status:', $card);
        self::assertStringContainsString('aria-label="Bild', $card);
        self::assertStringNotContainsString('localPath', $card);
        self::assertStringContainsString('role="dialog"', $dialog);
        self::assertStringContainsString('aria-modal="true"', $dialog);
        self::assertStringContainsString('Kennzeichnung speichern', $dialog);
        self::assertStringContainsString('name="status"', $filter);
        self::assertStringContainsString('name="source"', $filter);
        self::assertStringContainsString('name="present"', $filter);
        self::assertStringContainsString('name="sort"', $filter);
        self::assertStringContainsString('name="direction"', $filter);
        self::assertStringContainsString('name="page_size"', $filter);
        self::assertStringContainsString('grid-template-columns', $css);
        self::assertStringContainsString('prefers-reduced-motion', $css);
        self::assertStringContainsString(':focus-visible', $css);
    }

    #[Test]
    public function getrennte_admin_templates_sind_escaped_post_csrf_und_barrierearm(): void
    {
        $root = dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung';
        $files = [
            $root . '/adminmenu/templates/assets-list.php',
            $root . '/adminmenu/templates/asset-detail.php',
            $root . '/adminmenu/templates/bulk-preview.php',
            $root . '/adminmenu/templates/messages.php',
            $root . '/adminmenu/templates/cleanup-list.php',
            $root . '/adminmenu/templates/cleanup-preview.php',
        ];

        foreach ($files as $file) {
            self::assertFileExists($file);
            $source = (string) file_get_contents($file);
            self::assertStringContainsString('htmlspecialchars', $source);
            self::assertDoesNotMatchRegularExpression('/<script(?![^>]+src=)|<style|\$_(?:GET|POST|REQUEST)|echo\s+\$(?!escaped)/i', $source);
        }

        $combined = implode("\n", array_map(static fn(string $file): string => (string) file_get_contents($file), $files));
        foreach (glob($root . '/adminmenu/templates/partials/*.php') ?: [] as $partial) {
            $combined .= "\n" . (string) file_get_contents($partial);
        }
        self::assertStringContainsString('method="post"', strtolower($combined));
        self::assertStringContainsString('name="csrf_token"', $combined);
        self::assertStringContainsString('<th scope="col"', $combined);
        self::assertStringContainsString('aria-label', $combined);
        self::assertStringContainsString('confirmation_token', $combined);
        self::assertStringContainsString('Betroffene Datensätze', $combined);
        self::assertStringContainsString('name="mask[position]"', $combined);
        self::assertStringContainsString('name="mask[theme]"', $combined);
        self::assertStringContainsString('value="scan"', $combined);
        self::assertStringContainsString('value="cleanup-preview"', $combined);
        self::assertStringContainsString('value="cleanup-execute"', $combined);
        self::assertStringContainsString('Vorherige Seite', $combined);
        self::assertStringContainsString('Nächste Seite', $combined);
        self::assertStringContainsString('name="kPlugin"', $combined);
        self::assertStringContainsString('name="kPluginAdminMenu"', $combined);
        self::assertStringContainsString("'view' => 'detail'", $combined);
        self::assertStringContainsString("'view' => 'cleanup'", $combined);
    }

    #[Test]
    public function bootstrap_bleibt_duenn_und_mutiert_nicht_per_get(): void
    {
        $file = dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/assets.php';
        self::assertFileExists($file);
        $source = (string) file_get_contents($file);

        self::assertStringContainsString('defined(', $source);
        self::assertStringContainsString('AdminRuntimeFactory', $source);
        self::assertStringContainsString('AdminAssetController', (string) file_get_contents(
            dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php',
        ));
        self::assertStringNotContainsString('$_GET', $source);
        self::assertStringNotContainsString('$_POST', $source);
        self::assertStringNotContainsString('UPDATE ', strtoupper($source));
        self::assertStringNotContainsString('DELETE ', strtoupper($source));
    }
}
