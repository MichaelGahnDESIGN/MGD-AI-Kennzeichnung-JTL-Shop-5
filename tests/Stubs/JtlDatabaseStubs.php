<?php

declare(strict_types=1);

/*
 * Kleine Laufzeit-Doppel der in JTL-Shop 5.7.2 vorhandenen Datenbank- und
 * Migrationsverträge. Das Projekt installiert den Shopkern bewusst nicht als
 * Composer-Abhängigkeit. Die Stubs halten die Tests dennoch an genau den
 * Methoden fest, welche die Produktivklassen tatsächlich verwenden.
 */

namespace JTL\DB;

use stdClass;

interface DbInterface
{
    public function getPDO(): \PDO;

    /** @param array<string, mixed> $params
     *  @return stdClass[]
     */
    public function getObjects(string $stmt, array $params = []): array;

    /** @param array<string, mixed> $params */
    public function getSingleObject(string $stmt, array $params = []): ?stdClass;

    /** @param array<string, mixed> $params */
    public function getAffectedRows(string $stmt, array $params = []): int;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;
}

namespace JTL\Update;

interface IMigration
{
    public function up(): void;

    public function down(): void;
}

namespace JTL\Plugin;

use JTL\DB\DbInterface;

class Migration
{
    public function __construct(private readonly DbInterface $db) {}

    protected function getDB(): DbInterface
    {
        return $this->db;
    }

    protected function execute(string $sql): void
    {
        $this->db->getAffectedRows($sql);
    }
}
