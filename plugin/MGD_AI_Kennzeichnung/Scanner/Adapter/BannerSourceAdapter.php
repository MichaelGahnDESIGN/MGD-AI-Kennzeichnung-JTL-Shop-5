<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterPageInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceScanPage;

/** Liest Bannerbilder anhand ihrer technischen JTL-ID. */
final class BannerSourceAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    public function __construct(private readonly DbInterface $db, private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->scanPage($offset, $limit)->references;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        $this->assertPage($offset, $limit);
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT CONCAT('bilder/banner/', `b`.`cBildPfad`) AS `local_path`,
                       CONCAT('banner:', `b`.`kImageMap`) AS `source_reference`,
                       `b`.`cTitel` AS `context`
                  FROM `timagemap` AS `b`
                 ORDER BY `b`.`kImageMap`
                 LIMIT :limit OFFSET :offset
                SQL,
            ['offset' => $offset, 'limit' => $limit],
        );
        $references = [];
        foreach ($rows as $row) {
            $reference = LocalImageReference::fromRaw($row->local_path ?? null, $this->source(), $row->source_reference ?? null, $row->context ?? null, $this->normalizer);
            if ($reference !== null) {
                $references[] = $reference;
            }
        }

        return new SourceScanPage($references, count($rows));
    }

    public function source(): AssetSource
    {
        return AssetSource::Banner;
    }

    private function assertPage(int $offset, int $limit): void
    {
        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Offset muss positiv und das Seitenlimit zwischen 1 und 100 sein.');
        }
    }
}
