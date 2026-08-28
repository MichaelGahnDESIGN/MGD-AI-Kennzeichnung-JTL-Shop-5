<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Plugin\PluginInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\DisplayConfigCommittedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\DisplayConfigPortInterface;
use RuntimeException;
use Throwable;

use const CACHING_GROUP_PLUGIN;

/**
 * Persistiert nur die feste Positivliste der Darstellungseinstellungen.
 *
 * JTLs Config-Objekt bleibt absichtlich ein lesender Zugang. Das direkte,
 * parametrisierte UPDATE hält alle sieben Optionen in einer Transaktion
 * zusammen; der Cache wird erst nach dem erfolgreichen Commit invalidiert.
 */
final class JtlDisplayConfigAdapter implements DisplayConfigPortInterface
{
    /** @var list<string> */
    private const KEYS = [
        'language',
        'font_size',
        'outer_margin',
        'inner_padding',
        'border_radius',
        'blur',
        'transparency',
    ];

    public function __construct(
        private readonly DbInterface $db,
        private readonly PluginInterface $plugin,
        private readonly JTLCacheInterface $cache,
    ) {}

    /** @return array<string, mixed> */
    public function load(): array
    {
        $config = $this->plugin->getConfig();
        $values = [];
        foreach (self::KEYS as $name) {
            $values[$name] = $config->getValue($name);
        }

        return $values;
    }

    /** @param array<string, string> $values */
    public function save(array $values): void
    {
        $this->assertCompleteValues($values);
        $this->assertConfiguredKeys();

        if ($this->db->getPDO()->inTransaction() || !$this->db->beginTransaction()) {
            throw new RuntimeException('Die Darstellungseinstellungen konnten nicht reserviert werden.');
        }
        try {
            $this->assertExactlyOneStoredRowPerKey();
            foreach (self::KEYS as $name) {
                $affected = $this->db->getAffectedRows(
                    'UPDATE `tplugineinstellungen` SET `cWert` = :value WHERE `kPlugin` = :plugin_id AND `cName` = :name',
                    ['value' => $values[$name], 'plugin_id' => $this->plugin->getID(), 'name' => $name],
                );
                if ($affected < 0 || $affected > 1) {
                    throw new RuntimeException('Eine Pluginoption konnte nicht eindeutig gespeichert werden.');
                }
            }
            if (!$this->db->commit()) {
                throw new RuntimeException('Die Darstellungseinstellungen konnten nicht bestätigt werden.');
            }
        } catch (Throwable $error) {
            $this->rollbackAfterFailure($error);
        }

        $pluginId = $this->plugin->getID();
        try {
            $this->cache->flushTags([
                CACHING_GROUP_PLUGIN,
                CACHING_GROUP_PLUGIN . '_' . $pluginId,
            ]);
        } catch (Throwable $error) {
            throw new DisplayConfigCommittedException(
                'Die Darstellungseinstellungen wurden gespeichert, die Cache-Invalidierung ist fehlgeschlagen.',
                0,
                $error,
            );
        }
    }

    /** @param array<string, string> $values */
    private function assertCompleteValues(array $values): void
    {
        if (count($values) !== count(self::KEYS)
            || array_diff(array_keys($values), self::KEYS) !== []
            || array_diff(self::KEYS, array_keys($values)) !== []
        ) {
            throw new RuntimeException('Die zu speichernden Darstellungseinstellungen sind unvollständig.');
        }

    }

    /** Verhindert UPDATEs gegen eine nicht vollständig installierte Plugin-Konfiguration. */
    private function assertConfiguredKeys(): void
    {
        $config = $this->plugin->getConfig();
        foreach (self::KEYS as $name) {
            if ($config->getValue($name) === null) {
                throw new RuntimeException('Die Plugin-Konfiguration enthält nicht alle Darstellungseinstellungen.');
            }
        }
    }

    /** Sperrt jede Zielzeile und verhindert vor den UPDATEs verlorene oder mehrdeutige Optionen. */
    private function assertExactlyOneStoredRowPerKey(): void
    {
        foreach (self::KEYS as $name) {
            $rows = $this->db->getObjects(
                'SELECT `cWert` FROM `tplugineinstellungen` WHERE `kPlugin` = :plugin_id AND `cName` = :name FOR UPDATE',
                ['plugin_id' => $this->plugin->getID(), 'name' => $name],
            );
            if (count($rows) !== 1) {
                throw new RuntimeException('Eine Pluginoption fehlt oder ist nicht eindeutig vorhanden.');
            }
        }
    }

    /** Führt den Rollback aus und bewahrt den ursprünglichen technischen Fehler als Ursache. */
    private function rollbackAfterFailure(Throwable $error): never
    {
        try {
            if (!$this->db->rollback()) {
                throw new RuntimeException('Der Datenbank-Rollback meldete false.');
            }
        } catch (Throwable) {
            throw new RuntimeException(
                'Die Darstellungseinstellungen konnten nicht sicher zurückgenommen werden.',
                0,
                $error,
            );
        }

        throw $error;
    }
}
