<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcStorageSourceAdapter;
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
        $scannedSources = $this->validateAdapters();
        if ($scannedSources === []) {
            return new ImageScanResult(0, 0);
        }

        $assetSessionStarted = false;
        $usageSessionStarted = false;
        try {
            $this->assets->beginScanSession();
            $assetSessionStarted = true;
            $this->usages->beginScanSession();
            $usageSessionStarted = true;

            return $this->usages->reconcile($scannedSources, function (): ImageScanResult {
                $createdAssets = 0;
                $recordedUsages = 0;

                foreach ($this->adapters as $adapter) {
                    if (!$adapter instanceof SourceAdapterPageInterface) {
                        throw new RuntimeException('Der Quellenadapter meldet die Zahl gelesener Quelldatensätze nicht.');
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
                         * Eine hundertste volle Quellseite besitzt kein nachweisbares
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
        } finally {
            if ($usageSessionStarted) {
                $this->usages->endScanSession();
            }
            if ($assetSessionStarted) {
                $this->assets->endScanSession();
            }
        }
    }

    /**
     * @return list<AssetSource>
     */
    private function validateAdapters(): array
    {
        $automaticSources = [
            AssetSource::Product,
            AssetSource::Category,
            AssetSource::Manufacturer,
            AssetSource::Banner,
            AssetSource::Opc,
        ];
        $sources = [];
        /** @var array<string, list<class-string>> $contributions */
        $contributions = [];
        $opcContributions = [OpcSourceAdapter::class, OpcStorageSourceAdapter::class];
        foreach ($this->adapters as $adapter) {
            if (!$adapter instanceof SourceAdapterPageInterface) {
                throw new RuntimeException('Der Quellenadapter erfüllt den sicheren Seitenvertrag nicht.');
            }
            $source = $adapter->source();
            if (!in_array($source, $automaticSources, true)) {
                throw new RuntimeException('Der Quellenadapter verwendet keinen automatisch scanbaren Quellentyp.');
            }
            if (isset($sources[$source->value])) {
                $previous = $contributions[$source->value];
                /*
                 * Nur die beiden bekannten, finalen OPC-Adapter dürfen einen
                 * Quellentyp gemeinsam vervollständigen. Fremde Implementierungen
                 * und ein zweites Exemplar desselben Beitrags bleiben gesperrt.
                 * Der anschließende Abgleich erhält OPC dennoch nur einmal.
                 */
                if ($source !== AssetSource::Opc || count($previous) !== 1
                    || !in_array($adapter::class, $opcContributions, true)
                    || !in_array($previous[0], $opcContributions, true)
                    || $previous[0] === $adapter::class
                ) {
                    throw new RuntimeException('Ein Quellentyp wurde doppelt als Scanneradapter registriert.');
                }
            }
            $contributions[$source->value][] = $adapter::class;
            $sources[$source->value] = $source;
        }

        return array_values($sources);
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
