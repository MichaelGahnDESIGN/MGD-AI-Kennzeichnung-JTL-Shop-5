<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterPageInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceScanPage;
use RuntimeException;

/** Orchestriert den begrenzten, atomaren Abgleich sämtlicher Bildquellen. */
final class ImageScanService
{
    private const PAGE_SIZE = 100;
    private const MAXIMUM_PAGES_PER_ADAPTER = 100;

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
                if (!$adapter instanceof SourceAdapterPageInterface) {
                    throw new RuntimeException('Der Quellenadapter meldet die Zahl gelesener Datenbankzeilen nicht.');
                }
                $offset = 0;
                $previousFullPage = null;

                for ($pageNumber = 1; $pageNumber <= self::MAXIMUM_PAGES_PER_ADAPTER; ++$pageNumber) {
                    $page = $adapter->scanPage($offset, self::PAGE_SIZE);
                    $this->assertPage($page, $adapter);
                    if ($page->rowsRead === 0) {
                        break;
                    }

                    $fingerprint = $page->references === [] ? null : $this->pageFingerprint($page->references);
                    if ($page->rowsRead === self::PAGE_SIZE
                        && $fingerprint !== null
                        && $fingerprint === $previousFullPage
                    ) {
                        throw new RuntimeException('Ein Quellenadapter lieferte erneut eine identische Seite.');
                    }
                    $previousFullPage = $page->rowsRead === self::PAGE_SIZE ? $fingerprint : null;

                    /*
                     * Eine hundertste volle DB-Seite besitzt kein nachweisbares
                     * natürliches Ende. Wir brechen vor ihrer Verarbeitung ab;
                     * der Repository-Callback rollt dadurch den gesamten Lauf
                     * zurück und markiert insbesondere keine alte Nutzung als
                     * fehlend.
                     */
                    if ($pageNumber === self::MAXIMUM_PAGES_PER_ADAPTER
                        && $page->rowsRead === self::PAGE_SIZE
                    ) {
                        throw new RuntimeException('Ein Quellenadapter überschritt die harte Grenze von 100 vollen Seiten.');
                    }

                    foreach ($page->references as $reference) {
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

                    $offset += $page->rowsRead;
                    if ($page->rowsRead < self::PAGE_SIZE) {
                        break;
                    }
                }
            }

            return new ImageScanResult($createdAssets, $recordedUsages);
        });
    }

    private function assertPage(SourceScanPage $page, SourceAdapterInterface $adapter): void
    {
        foreach ($page->references as $reference) {
            if ($reference->source !== $adapter->source()) {
                throw new RuntimeException('Ein Quellenadapter lieferte eine ungültige oder fremde Referenz.');
            }
        }
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
