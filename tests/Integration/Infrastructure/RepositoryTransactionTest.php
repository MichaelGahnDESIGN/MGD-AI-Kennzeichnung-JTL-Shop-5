<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\PhilosophyRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class RepositoryTransactionTest extends TestCase
{
    private const MARKER = 'mgd-ai-kennzeichnung-jtl-v1';

    #[Test]
    public function bulk_update_rollt_bei_jeder_throwable_alle_vorherigen_aenderungen_zurueck(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['eins' => 'none', 'zwei' => 'generated', 'drei' => 'modified']);
        $db->failOnAssetKey = 'drei';
        $repository = new AssetRepository($db);

        try {
            $repository->bulkUpdate([
                ['asset_key' => 'eins', 'status' => 'generated', 'position' => 'top-left', 'theme' => 'dark'],
                ['asset_key' => 'zwei', 'status' => 'deepfake', 'position' => 'bottom-left', 'theme' => 'light'],
                ['asset_key' => 'drei', 'status' => 'partially-generated', 'position' => 'top-right', 'theme' => 'auto'],
            ]);
            self::fail('Der erzwungene Fehler muss weitergereicht werden.');
        } catch (RuntimeException $fehler) {
            self::assertSame('Erzwungener Fehler beim dritten Asset.', $fehler->getMessage());
        }

        self::assertSame(['eins' => 'none', 'zwei' => 'generated', 'drei' => 'modified'], $db->assetStatuses());
        self::assertSame(1, $db->begins);
        self::assertSame(0, $db->commits);
        self::assertSame(1, $db->rollbacks);
    }

    #[Test]
    public function bulk_update_rollt_auch_einen_php_error_vollstaendig_zurueck(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['eins' => 'none', 'zwei' => 'generated', 'drei' => 'modified']);
        $db->failOnAssetKey = 'drei';
        $db->failWithError = true;

        try {
            (new AssetRepository($db))->bulkUpdate([
                ['asset_key' => 'eins', 'status' => 'generated'],
                ['asset_key' => 'zwei', 'status' => 'deepfake'],
                ['asset_key' => 'drei', 'status' => 'none'],
            ]);
            self::fail('Der erzwungene Error muss weitergereicht werden.');
        } catch (\Error $fehler) {
            self::assertSame('Erzwungener Error beim dritten Asset.', $fehler->getMessage());
        }

        self::assertSame(['eins' => 'none', 'zwei' => 'generated', 'drei' => 'modified'], $db->assetStatuses());
        self::assertSame(1, $db->rollbacks);
        self::assertSame(0, $db->commits);
    }

    #[Test]
    public function bulk_update_reicht_den_urspruenglichen_fehler_auch_bei_rollback_fehler_weiter(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['eins' => 'none']);
        $db->failOnAssetKey = 'eins';
        $db->failRollback = true;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Erzwungener Fehler beim dritten Asset.');

        (new AssetRepository($db))->bulkUpdate([
            ['asset_key' => 'eins', 'status' => 'generated'],
        ]);
    }

    #[Test]
    public function asset_upsert_normalisiert_pfad_und_geschlossene_werte_mit_bindings(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $repository = new AssetRepository($db);
        $langerPfad = '/media//produkte/../bilder/' . str_repeat('a', 1200) . '.jpg';

        $repository->upsert(' sha256:ABCDEF ', $langerPfad, 'NICHT-BEKANNT', 'TOP-LEFT', 'DARK');

        $aufruf = $this->schreibAufrufe($db)[0];
        self::assertSame('sha256:ABCDEF', $aufruf['params']['asset_key']);
        self::assertSame('unreviewed', $aufruf['params']['status']);
        self::assertSame('top-left', $aufruf['params']['position']);
        self::assertSame('dark', $aufruf['params']['theme']);
        $lokalerPfad = $aufruf['params']['local_path'];
        self::assertIsString($lokalerPfad);
        self::assertLessThanOrEqual(1024, mb_strlen($lokalerPfad));
        self::assertStringNotContainsString('//', $lokalerPfad);
        self::assertStringNotContainsString('/../', $lokalerPfad);
        self::assertStringNotContainsString('sha256:ABCDEF', $aufruf['sql']);
        self::assertStringNotContainsString($langerPfad, $aufruf['sql']);
    }

    #[Test]
    public function asset_upsert_weist_einen_leeren_lokalen_pfad_ab(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lokaler Pfad');

        (new AssetRepository($db))->upsert('sha256:abc', " \0 ");
    }

    #[Test]
    public function asset_upsert_weist_externe_urls_statt_lokaler_pfade_ab(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lokaler Pfad');

        (new AssetRepository($db))->upsert('sha256:abc', 'https://tracking.example/person.jpg');
    }

    #[Test]
    public function bulk_update_committet_die_vollstaendige_auswahl_in_genau_einer_transaktion(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['eins' => 'none', 'zwei' => 'generated']);

        (new AssetRepository($db))->bulkUpdate([
            ['asset_key' => 'eins', 'status' => 'GENERATED', 'position' => 'TOP-LEFT', 'theme' => 'DARK'],
            ['asset_key' => 'zwei', 'status' => 'unbekannt', 'position' => 'manipuliert', 'theme' => '<script>'],
        ]);

        self::assertSame(['eins' => 'generated', 'zwei' => 'unreviewed'], $db->assetStatuses());
        self::assertSame(1, $db->begins);
        self::assertSame(1, $db->commits);
        self::assertSame(0, $db->rollbacks);
        foreach ($this->schreibAufrufe($db) as $aufruf) {
            $assetKey = $aufruf['params']['asset_key'] ?? null;
            self::assertIsString($assetKey);
            self::assertStringNotContainsString($assetKey, $aufruf['sql']);
            self::assertStringContainsString(':', $aufruf['sql']);
        }
    }

    #[Test]
    public function usage_upsert_ist_idempotent_und_begrenzt_freie_minimierte_werte(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);
        $langeReferenz = str_repeat('r', 600);
        $unsichererKontext = '<script>alert(1)</script>' . str_repeat('k', 900);

        $repository->upsert(7, 'PRODUCT', $langeReferenz, $unsichererKontext, true);
        $repository->upsert(7, 'product', $langeReferenz, $unsichererKontext, false);

        self::assertSame(1, $db->usageCount());
        $aufruf = $this->schreibAufrufe($db)[1];
        self::assertSame('product', $aufruf['params']['source_type']);
        $referenz = $aufruf['params']['source_reference'];
        $kontext = $aufruf['params']['context'];
        self::assertIsString($referenz);
        self::assertIsString($kontext);
        self::assertLessThanOrEqual(255, mb_strlen($referenz));
        self::assertLessThanOrEqual(500, mb_strlen($kontext));
        self::assertStringNotContainsString('<script', $kontext);
        self::assertStringNotContainsString($langeReferenz, $aufruf['sql']);
    }

    #[Test]
    public function usage_verwendet_die_bestehende_geschlossene_asset_quellenliste(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);

        $repository->upsert(7, 'MANUFACTURER', 'hersteller:42');
        $repository->upsert(7, 'freie-neue-quelle', 'fremd:1');

        $aufrufe = $this->schreibAufrufe($db);
        self::assertSame('manufacturer', $aufrufe[0]['params']['source_type']);
        self::assertSame('unknown', $aufrufe[1]['params']['source_type']);
    }

    #[Test]
    public function philosophy_upsert_ist_je_sprache_idempotent_und_speichert_keinen_html_oder_script_code(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', self::MARKER);
        $repository = new PhilosophyRepository($db);

        $repository->upsert('DE-de', "  <p>Transparenz</p><script>alert('x')</script>  ");
        $repository->upsert('de-de', '<b>Neue Haltung</b>');

        self::assertSame(1, $db->philosophyCount());
        self::assertSame(['de-de' => 'Neue Haltung'], $db->philosophies());
        $aufruf = $this->schreibAufrufe($db)[1];
        self::assertSame('de-de', $aufruf['params']['language']);
        $inhalt = $aufruf['params']['content'];
        self::assertIsString($inhalt);
        self::assertStringNotContainsString('<', $inhalt);
        self::assertStringNotContainsString('Neue Haltung', $aufruf['sql']);
    }

    /**
     * @return list<array{sql: string, params: array<string, mixed>}>
     */
    private function schreibAufrufe(TransactionalDatabaseFake $db): array
    {
        return array_values(array_filter(
            $db->statements,
            static fn(array $aufruf): bool => str_starts_with(ltrim($aufruf['sql']), 'UPDATE')
                || str_starts_with(ltrim($aufruf['sql']), 'INSERT'),
        ));
    }
}
