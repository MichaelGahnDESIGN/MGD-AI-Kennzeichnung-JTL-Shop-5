<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use RuntimeException;

/** Orchestriert den begrenzten, atomaren Abgleich sämtlicher Bildquellen. */
final class ImageScanService
{
    private const PAGE_SIZE = 100;

    /** @param list<SourceAdapterInterface> $adapters */
    public function __construct(
        private readonly array $adapters,
        private readonly AssetRepository $assets,
        private readonly UsageRepository $usages,
    ) {}

    public function scan(): ImageScanResult
    {
        return $this->usages->reconcile(function (): ImageScanResult {
            $createdAssets = 0;
            $recordedUsages = 0;

            foreach ($this->adapters as $adapter) {
                $offset = 0;
                $previousFullPage = null;

                while (true) {
                    $page = $this->collectPage($adapter, $offset);
                    if ($page === []) {
                        break;
                    }

                    $fingerprint = $this->pageFingerprint($page);
                    if (count($page) === self::PAGE_SIZE && $fingerprint === $previousFullPage) {
                        throw new RuntimeException('Ein Quellenadapter lieferte erneut eine identische Seite.');
                    }
                    $previousFullPage = count($page) === self::PAGE_SIZE ? $fingerprint : null;

                    foreach ($page as $reference) {
                        if ($reference->source !== $adapter->source()) {
                            throw new RuntimeException('Ein Quellenadapter lieferte eine ungültige oder fremde Referenz.');
                        }
                        $asset = $this->assets->ensureUnreviewed($reference->assetKey, $reference->localPath);
                        if ($asset['created']) {
                            ++$createdAssets;
                        }
                        if ($this->usages->upsert(
                            $asset['id'],
                            $reference->source,
                            $reference->sourceReference,
                            $reference->context,
                        )) {
                            ++$recordedUsages;
                        }
                    }

                    /* Der Offset bezieht sich auf gelesene DB-Zeilen, nicht nur auf gültige Fundstellen. */
                    $offset += self::PAGE_SIZE;
                }
            }

            return new ImageScanResult($createdAssets, $recordedUsages);
        });
    }

    /** @return list<LocalImageReference> */
    private function collectPage(SourceAdapterInterface $adapter, int $offset): array
    {
        $page = [];
        foreach ($adapter->scan($offset, self::PAGE_SIZE) as $reference) {
            if (!$reference instanceof LocalImageReference) {
                throw new RuntimeException('Ein Quellenadapter lieferte keine lokale Bildreferenz.');
            }
            $page[] = $reference;
            if (count($page) > self::PAGE_SIZE) {
                throw new RuntimeException('Ein Quellenadapter lieferte mehr als 100 Referenzen pro Seite.');
            }
        }

        return $page;
    }

    /** @param list<LocalImageReference> $page */
    private function pageFingerprint(array $page): string
    {
        $hash = hash_init('sha256');
        foreach ($page as $reference) {
            hash_update($hash, $reference->assetKey . "\0" . $reference->sourceReference . "\0");
        }

        return hash_final($hash);
    }
}
