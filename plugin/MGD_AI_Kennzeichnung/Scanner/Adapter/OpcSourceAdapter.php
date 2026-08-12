<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use JsonException;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterPageInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceScanPage;
use RuntimeException;

/**
 * Liest die in OPC-Seiten eingebetteten Bildreferenzen aus JTL-Shop 5.7.2.
 *
 * Das offizielle Core-Schema speichert eine Seite in `topcpage` und deren
 * Portlet-Baum in `cAreasJson`. Der Adapter interpretiert ausschließlich die
 * vom Core verwendeten Bildfelder. Beliebiger Text oder HTML wird niemals nach
 * URLs durchsucht; dadurch entsteht weder ein externer Abruf noch eine
 * unerwartete Datenweitergabe.
 */
final class OpcSourceAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    private const MAXIMUM_JSON_BYTES = 102400;
    private const MAXIMUM_JSON_DEPTH = 64;
    private const MAXIMUM_VISITED_NODES = 10000;
    private const MAXIMUM_REFERENCES_PER_ROW = 100;
    private const STORAGE_PREFIX = 'media/image/storage/opc/';

    public function __construct(private readonly DbInterface $db, private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->scanPage($offset, $limit)->references;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        self::assertPage($offset, $limit);
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT `p`.`kPage` AS `page_id`,
                       `p`.`cAreasJson` AS `areas_json`,
                       `p`.`cName` AS `context`
                  FROM `topcpage` AS `p`
                 ORDER BY `p`.`kPage`
                 LIMIT :limit OFFSET :offset
                SQL,
            ['offset' => $offset, 'limit' => $limit],
        );

        $references = [];
        foreach ($rows as $row) {
            $pageId = filter_var($row->page_id ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $json = $row->areas_json ?? null;
            if ($pageId === false || !is_string($json) || strlen($json) > self::MAXIMUM_JSON_BYTES) {
                continue;
            }

            try {
                $tree = json_decode($json, true, self::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }
            if (!is_array($tree)) {
                continue;
            }

            $visited = 0;
            $candidates = [];
            if (!$this->collectImageFields($tree, '$', 0, $visited, $candidates)) {
                continue;
            }
            foreach ($candidates as $candidate) {
                /*
                 * Zuerst wird der JSON-Wert eigenständig geprüft. Ein
                 * vorangestelltes Storage-Verzeichnis dürfte ein darin
                 * verstecktes Schema wie `javascript%3A...` sonst optisch zu
                 * einem lokalen Unterpfad machen.
                 */
                $candidatePath = $this->normalizer->normalize($candidate['value']);
                if ($candidatePath === null) {
                    continue;
                }
                $reference = LocalImageReference::fromRaw(
                    self::STORAGE_PREFIX . $candidatePath,
                    $this->source(),
                    sprintf('opc-seite:%d:json:%s', $pageId, hash('sha256', $candidate['path'])),
                    $row->context ?? null,
                    $this->normalizer,
                );
                if ($reference !== null) {
                    $references[] = $reference;
                    if (count($references) > SourceScanPage::MAXIMUM_REFERENCES) {
                        throw new RuntimeException('Eine OPC-Datenbankseite enthält mehr als 500 Bildreferenzen.');
                    }
                }
            }
        }

        return new SourceScanPage($references, count($rows));
    }

    public function source(): AssetSource
    {
        return AssetSource::Opc;
    }

    /**
     * Sammelt nur die nachweislich vom JTL-Core verwendeten OPC-Bildfelder.
     * Der JSON-Pfad bleibt rein technisch und wird nur gehasht gespeichert.
     *
     * @param array<mixed> $node
     * @param list<array{path: string, value: string}> $candidates
     */
    private function collectImageFields(
        array $node,
        string $path,
        int $depth,
        int &$visited,
        array &$candidates,
    ): bool {
        if ($depth > self::MAXIMUM_JSON_DEPTH || ++$visited > self::MAXIMUM_VISITED_NODES) {
            return false;
        }

        $properties = $node['properties'] ?? null;
        if (is_array($properties)) {
            foreach (['src', 'still-src', 'video-poster'] as $key) {
                if (isset($properties[$key]) && is_string($properties[$key])) {
                    $this->addCandidate(
                        $candidates,
                        $path . '.properties.' . $key,
                        $properties[$key],
                    );
                }
            }
            foreach (['images', 'slides'] as $collectionKey) {
                $items = $properties[$collectionKey] ?? null;
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $index => $item) {
                    if (is_array($item) && isset($item['url']) && is_string($item['url'])) {
                        $this->addCandidate(
                            $candidates,
                            sprintf('%s.properties.%s[%s].url', $path, $collectionKey, (string) $index),
                            $item['url'],
                        );
                    }
                }
            }
        }

        foreach ($node as $key => $child) {
            if (is_array($child)
                && !$this->collectImageFields($child, $path . '[' . (string) $key . ']', $depth + 1, $visited, $candidates)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Zählt jeden rohen erlaubten JSON-Kandidaten vor Normalisierung oder einer
     * möglichen späteren Deduplizierung. Auch hundertfach derselbe Wert kostet
     * damit hundert Einträge und kann das Ressourcenlimit nicht umgehen.
     *
     * @param list<array{path: string, value: string}> $candidates
     */
    private function addCandidate(array &$candidates, string $path, string $value): void
    {
        if (count($candidates) >= self::MAXIMUM_REFERENCES_PER_ROW) {
            throw new RuntimeException('Eine OPC-Datenbankzeile enthält mehr als 100 Bildreferenzen.');
        }
        $candidates[] = ['path' => $path, 'value' => $value];
    }

    private static function assertPage(int $offset, int $limit): void
    {
        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Offset muss positiv und das Seitenlimit zwischen 1 und 100 sein.');
        }
    }
}
