<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Sichere, von HTTP und Templates unabhängige Datenzugriffsschnittstelle. */
interface AdminAssetRepositoryInterface
{
    /** @param list<int> $ids */
    public function countExistingIds(array $ids): int;

    /** @param array<string, string> $changes */
    public function updateOneById(int $id, array $changes): void;

    /**
     * @param list<int> $ids
     * @param array<string, string> $changes
     */
    public function updateManyByIds(array $ids, array $changes): void;

    /**
     * @param array<string, string|bool> $filters
     * @return list<array<string, scalar|null>>
     */
    public function listPage(int $offset, int $limit, array $filters, string $sort, string $direction): array;

    /** @param array<string, string|bool> $filters */
    public function countForList(array $filters): int;

    /** @return array<string, scalar|null>|null */
    public function detailById(int $id): ?array;
}
