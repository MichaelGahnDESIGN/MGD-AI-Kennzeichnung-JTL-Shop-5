<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;

/** Liest Produkt-Originalbilder seitenweise aus JTLs tartikelpict-Vertrag. */
final class ProductSourceAdapter implements SourceAdapterInterface
{
    public function __construct(private readonly DbInterface $db, private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        self::assertPage($offset, $limit);
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT CONCAT('media/image/storage/', `p`.`cPfad`) AS `local_path`,
                       CONCAT('artikel:', `p`.`kArtikel`, ':bild:', `p`.`nNr`) AS `source_reference`,
                       NULL AS `context`
                  FROM `tartikelpict` AS `p`
                 ORDER BY `p`.`kArtikel`, `p`.`nNr`
                 LIMIT :limit OFFSET :offset
                SQL,
            ['offset' => $offset, 'limit' => $limit],
        );

        foreach ($rows as $row) {
            $reference = LocalImageReference::fromRaw(
                $row->local_path ?? null,
                $this->source(),
                $row->source_reference ?? null,
                $row->context ?? null,
                $this->normalizer,
            );
            if ($reference !== null) {
                yield $reference;
            }
        }
    }

    public function source(): AssetSource
    {
        return AssetSource::Product;
    }

    private static function assertPage(int $offset, int $limit): void
    {
        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Offset muss positiv und das Seitenlimit zwischen 1 und 100 sein.');
        }
    }
}
