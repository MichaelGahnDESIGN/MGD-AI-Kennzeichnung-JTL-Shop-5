<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use Plugin\MGD_AI_Kennzeichnung\Setup\PluginDataLifecycle;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class PluginLifecycleTest extends TestCase
{
    #[Test]
    public function deinstallation_ohne_loeschwunsch_behaelt_alle_plugin_daten(): void
    {
        $db = $this->vollstaendigeDatenbank();

        (new PluginDataLifecycle($db))->uninstalled(false);

        self::assertCount(4, $db->existingTables());
        self::assertSame([], $db->droppedTables);
    }

    #[Test]
    public function ausdruecklicher_loeschwunsch_entfernt_nur_vollstaendig_gepruefte_plugin_tabellen(): void
    {
        $db = $this->vollstaendigeDatenbank();

        (new PluginDataLifecycle($db))->uninstalled(true);

        self::assertSame([], $db->existingTables());
        self::assertSame([
            'xplugin_mgd_ai_confirmation_claim',
            'xplugin_mgd_ai_usage',
            'xplugin_mgd_ai_philosophy',
            'xplugin_mgd_ai_asset',
        ], $db->droppedTables);
    }

    #[Test]
    public function abweichender_eigentuemermarker_bricht_vor_dem_ersten_drop_ab(): void
    {
        $db = $this->vollstaendigeDatenbank();
        $db->setMarker('xplugin_mgd_ai_philosophy', 'fremder-marker');

        try {
            (new PluginDataLifecycle($db))->uninstalled(true);
            self::fail('Eine fremde Tabelle muss den Löschvorgang abbrechen.');
        } catch (RuntimeException) {
            self::assertSame([], $db->droppedTables);
            self::assertCount(4, $db->existingTables());
        }
    }

    private function vollstaendigeDatenbank(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        foreach (['xplugin_mgd_ai_asset', 'xplugin_mgd_ai_usage', 'xplugin_mgd_ai_philosophy'] as $tabelle) {
            $db->setMarker($tabelle, SchemaOwnershipGuard::OWNERSHIP_MARKER);
        }
        $db->setMarker('xplugin_mgd_ai_confirmation_claim', ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER);

        return $db;
    }
}
