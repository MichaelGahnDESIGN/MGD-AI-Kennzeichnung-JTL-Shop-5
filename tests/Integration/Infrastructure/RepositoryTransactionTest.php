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

        try {
            (new AssetRepository($db))->bulkUpdate([
                ['asset_key' => 'eins', 'status' => 'generated'],
            ]);
            self::fail('Der Rollback-Fehler muss sichtbar eskalieren.');
        } catch (RuntimeException $fehler) {
            self::assertStringContainsString('Rollback', $fehler->getMessage());
            self::assertInstanceOf(RuntimeException::class, $fehler->getPrevious());
            self::assertSame('Erzwungener Fehler beim dritten Asset.', $fehler->getPrevious()->getMessage());
        }
    }

    #[Test]
    public function bulk_update_meldet_auch_rollback_false_mit_urspruenglichem_fehler_als_previous(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['eins' => 'none']);
        $db->failOnAssetKey = 'eins';
        $db->returnFalseOnRollback = true;

        try {
            (new AssetRepository($db))->bulkUpdate([['asset_key' => 'eins', 'status' => 'generated']]);
            self::fail('Rollback false muss sichtbar eskalieren.');
        } catch (RuntimeException $fehler) {
            self::assertStringContainsString('Rollback', $fehler->getMessage());
            self::assertInstanceOf(RuntimeException::class, $fehler->getPrevious());
            self::assertSame('Erzwungener Fehler beim dritten Asset.', $fehler->getPrevious()->getMessage());
        }
    }

    #[Test]
    public function asset_upsert_normalisiert_pfad_und_geschlossene_werte_mit_bindings(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $repository = new AssetRepository($db);
        $lokalerEingabepfad = '/media//produkte/../bilder/bild.jpg';

        $repository->upsert('sha256:ABCDEF', $lokalerEingabepfad, 'NICHT-BEKANNT', 'TOP-LEFT', 'DARK');

        $aufruf = $this->schreibAufrufe($db)[0];
        self::assertSame(hash('sha256', 'sha256:ABCDEF'), $aufruf['params']['asset_key']);
        self::assertSame('unreviewed', $aufruf['params']['status']);
        self::assertSame('top-left', $aufruf['params']['position']);
        self::assertSame('dark', $aufruf['params']['theme']);
        $lokalerPfad = $aufruf['params']['local_path'];
        self::assertIsString($lokalerPfad);
        self::assertLessThanOrEqual(1024, mb_strlen($lokalerPfad));
        self::assertStringNotContainsString('//', $lokalerPfad);
        self::assertStringNotContainsString('/../', $lokalerPfad);
        self::assertStringNotContainsString('sha256:ABCDEF', $aufruf['sql']);
        self::assertStringNotContainsString($lokalerEingabepfad, $aufruf['sql']);
    }

    #[Test]
    public function asset_upsert_akzeptiert_exakten_sha256_case_normalisiert_ohne_kuerzung(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $hash = str_repeat('A', 64);

        (new AssetRepository($db))->upsert($hash, '/media/a.jpg');

        $aufruf = $this->schreibAufrufe($db)[0];
        self::assertSame(strtolower($hash), $aufruf['params']['asset_key']);
        self::assertSame(64, strlen((string) $aufruf['params']['asset_key']));
    }

    #[Test]
    public function asset_hash_unterscheidet_rohe_case_akzent_und_nachlaufende_leerzeichen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $repository = new AssetRepository($db);

        foreach (['Bild', 'bild', 'Bíld', 'Bild '] as $index => $assetKey) {
            $repository->upsert($assetKey, '/media/' . $index . '.jpg');
        }

        self::assertSame(4, $db->assetCount());
    }

    #[Test]
    public function asset_upsert_weist_ueberlangen_pfad_ab_statt_identitaeten_zu_verschmelzen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('zu lang');

        (new AssetRepository($db))->upsert('asset-a', '/' . str_repeat('p', 1025));
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
        self::assertSame(2, $db->forUpdateSelections);
        foreach ($this->schreibAufrufe($db) as $aufruf) {
            $assetKey = $aufruf['params']['asset_key'] ?? null;
            self::assertIsString($assetKey);
            self::assertStringNotContainsString($assetKey, $aufruf['sql']);
            self::assertStringContainsString(':', $aufruf['sql']);
        }
    }

    #[Test]
    public function bulk_update_rollt_zurueck_wenn_ein_angefordertes_asset_fehlt(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['vorhanden' => 'none']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nicht gefunden');

        try {
            (new AssetRepository($db))->bulkUpdate([
                ['asset_key' => 'vorhanden', 'status' => 'none'],
                ['asset_key' => 'fehlt', 'status' => 'generated'],
            ]);
        } finally {
            self::assertSame(['vorhanden' => 'none'], $db->assetStatuses());
            self::assertSame(1, $db->rollbacks);
        }
    }

    #[Test]
    public function bulk_update_zaehlt_auch_unveraendertes_asset_als_vorhanden(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->seedAssets(['gleich' => 'none']);

        (new AssetRepository($db))->bulkUpdate([['asset_key' => 'gleich', 'status' => 'none']]);

        self::assertSame(1, $db->commits);
        self::assertSame(1, $db->forUpdateSelections);
    }

    #[Test]
    public function usage_upsert_ist_idempotent_und_begrenzt_freie_minimierte_werte(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);
        $referenz = 'produkt:42';
        $unsichererKontext = '<script>alert(1)</script>' . str_repeat('k', 900);

        $repository->upsert(7, 'PRODUCT', $referenz, $unsichererKontext, true);
        $repository->upsert(7, 'product', $referenz, $unsichererKontext, false);

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
        self::assertSame(hash('sha256', $referenz), $aufruf['params']['source_reference_hash']);
        self::assertStringNotContainsString($referenz, $aufruf['sql']);
    }

    #[Test]
    public function usage_weist_ueberlange_referenz_ab_ohne_gleiche_praefixe_zu_verschmelzen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);
        $prefix = str_repeat('x', 255);

        foreach ([$prefix . 'a', $prefix . 'b'] as $referenz) {
            try {
                $repository->upsert(7, 'product', $referenz);
                self::fail('Überlange Referenzen müssen abgewiesen werden.');
            } catch (RuntimeException $fehler) {
                self::assertStringContainsString('zu lang', $fehler->getMessage());
            }
        }
        self::assertSame(0, $db->usageCount());
    }

    #[Test]
    public function usage_hash_unterscheidet_case_akzent_und_nachlaufendes_leerzeichen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);

        foreach (['Bild', 'bild', 'Bíld', 'Bild '] as $referenz) {
            $repository->upsert(7, 'product', $referenz);
        }

        self::assertSame(4, $db->usageCount());
    }

    #[Test]
    public function usage_kontext_entfernt_entity_kodierte_aktive_bloecke_tags_und_eventattribute(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $repository = new UsageRepository($db);
        $angriffe = [
            '&lt;img src=x onerror=alert(1)&gt;Sicher',
            '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;Sicher',
            '&lt;style&gt;body{display:none}&lt;/style&gt;Sicher',
        ];

        foreach ($angriffe as $index => $kontext) {
            $repository->upsert(7, 'product', 'ref-' . $index, $kontext);
        }

        foreach ($this->schreibAufrufe($db) as $aufruf) {
            $kontext = $aufruf['params']['context'] ?? null;
            self::assertIsString($kontext);
            self::assertSame('Sicher', $kontext);
        }
    }

    #[Test]
    public function usage_kontext_weist_tief_kodiertes_rest_markup_fail_closed_ab(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);
        $kontext = '<img src="x" onerror="alert(1)">';
        for ($durchlauf = 0; $durchlauf < 12; ++$durchlauf) {
            $kontext = htmlspecialchars($kontext, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        (new UsageRepository($db))->upsert(7, 'product', 'tief', $kontext);

        $aufruf = $this->schreibAufrufe($db)[0];
        self::assertNull($aufruf['params']['context']);
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

    #[Test]
    public function philosophy_entfernt_einfach_entity_kodierte_script_tags_vollstaendig(): void
    {
        $inhalt = $this->speicherePhilosophie('&lt;script&gt;alert(1)&lt;/script&gt; Sicherer Text');

        self::assertSame('Sicherer Text', $inhalt);
        $this->assertKeinMarkup($inhalt);
    }

    #[Test]
    public function philosophy_entfernt_auch_doppelt_entity_kodierte_script_tags_vollstaendig(): void
    {
        $inhalt = $this->speicherePhilosophie(
            '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt; Doppelt sicher',
        );

        self::assertSame('Doppelt sicher', $inhalt);
        $this->assertKeinMarkup($inhalt);
    }

    #[Test]
    public function philosophy_entfernt_gemischte_script_tags_attribute_und_eventhandler(): void
    {
        $inhalt = $this->speicherePhilosophie(
            '&LT;ScRiPt type=&quot;text/javascript&quot; OnLoAd=&quot;alert(1)&quot;&GT;'
            . 'alert(1)&LT;/sCrIpT&GT;'
            . '&lt;p onclick=&quot;alert(2)&quot;&gt;Haltung&lt;/p&gt;',
        );

        self::assertSame('Haltung', $inhalt);
        $this->assertKeinMarkup($inhalt);
        self::assertStringNotContainsStringIgnoringCase('onclick', $inhalt);
        self::assertStringNotContainsStringIgnoringCase('onload', $inhalt);
    }

    #[Test]
    public function philosophy_erhaelt_normale_umlaute_und_text_entities_als_utf8(): void
    {
        $inhalt = $this->speicherePhilosophie(
            'Künstliche Intelligenz fördert Transparenz &amp; Verantwortung: ä ö ü ß &quot;fair&quot;.',
        );

        self::assertSame(
            'Künstliche Intelligenz fördert Transparenz & Verantwortung: ä ö ü ß "fair".',
            $inhalt,
        );
        $this->assertKeinMarkup($inhalt);
    }

    #[Test]
    public function philosophy_weist_extrem_tief_kodiertes_rest_markup_sicher_ab(): void
    {
        $inhalt = '<img src="x" onerror="alert(1)">';
        for ($durchlauf = 0; $durchlauf < 12; ++$durchlauf) {
            $inhalt = htmlspecialchars($inhalt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', self::MARKER);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('darf nicht leer sein');

        (new PhilosophyRepository($db))->upsert('de', $inhalt);
    }

    #[Test]
    public function philosophy_neutralisiert_semikolonlose_numerische_tag_entities(): void
    {
        foreach (['&#60script&#62alert(1)&#60/script&#62', '&#x3cscript&#x3ealert(1)&#x3c/script&#x3e'] as $angriff) {
            $inhalt = $this->speicherePhilosophie($angriff . ' Sicher');
            self::assertSame('Sicher', $inhalt);
            $this->assertKeinMarkup($inhalt);
        }
    }

    private function speicherePhilosophie(string $inhalt): string
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', self::MARKER);

        (new PhilosophyRepository($db))->upsert('de', $inhalt);

        return $db->philosophies()['de'];
    }

    private function assertKeinMarkup(string $inhalt): void
    {
        self::assertStringNotContainsString('<', $inhalt);
        self::assertStringNotContainsString('>', $inhalt);
        self::assertStringNotContainsStringIgnoringCase('script', $inhalt);
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
