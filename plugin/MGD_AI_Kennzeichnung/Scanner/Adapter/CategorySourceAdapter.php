<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;

/** Liest Kategoriebilder aus dem gekapselten tkategoriepict-Vertrag. */
final class CategorySourceAdapter implements SourceAdapterInterface
{
    public function __construct(private readonly DbInterface $db, private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        $this->assertPage($offset, $limit);
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT CONCAT('media/storage/categories/', `p`.`cPfad`) AS `local_path`,
                       CONCAT('kategorie:', `p`.`kKategorie`) AS `source_reference`,
                       NULL AS `context`
                  FROM `tkategoriepict` AS `p`
                 ORDER BY `p`.`kKategorie`
                 LIMIT :limit OFFSET :offset
                SQL,
            ['offset' => $offset, 'limit' => $limit],
        );
        foreach ($rows as $row) {
            $reference = LocalImageReference::fromRaw($row->local_path ?? null, $this->source(), $row->source_reference ?? null, $row->context ?? null, $this->normalizer);
            if ($reference !== null) {
                yield $reference;
            }
        }
    }

    public function source(): AssetSource
    {
        return AssetSource::Category;
    }

    private function assertPage(int $offset, int $limit): void
    {
        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Offset muss positiv und das Seitenlimit zwischen 1 und 100 sein.');
        }
    }
}
