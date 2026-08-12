<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;

/**
 * Liest vom JTL-Core materialisierte OPC-Bildfundstellen.
 *
 * Der Tabellenvertrag ist bewusst vollständig in dieser Klasse gekapselt. Da
 * JTL die Core-Schemadateien für 5.7.2 nicht öffentlich versioniert, muss dieser
 * Vertrag beim Integrationstest gegen die Zielinstallation bestätigt werden.
 */
final class OpcSourceAdapter implements SourceAdapterInterface
{
    public function __construct(private readonly DbInterface $db, private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        $this->assertPage($offset, $limit);
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT `a`.`cImagePath` AS `local_path`,
                       CONCAT('opc:', `a`.`kArea`) AS `source_reference`,
                       `a`.`cName` AS `context`
                  FROM `topcarea` AS `a`
                 WHERE `a`.`cImagePath` IS NOT NULL
                 ORDER BY `a`.`kArea`
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
        return AssetSource::Opc;
    }

    private function assertPage(int $offset, int $limit): void
    {
        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Offset muss positiv und das Seitenlimit zwischen 1 und 100 sein.');
        }
    }
}
