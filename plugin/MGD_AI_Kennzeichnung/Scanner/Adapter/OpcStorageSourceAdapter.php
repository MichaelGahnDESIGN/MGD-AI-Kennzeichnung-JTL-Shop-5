<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter;

use InvalidArgumentException;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageFileLister;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageScanException;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageScanFailure;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterPageInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceScanPage;

/** Übersetzt auch unbenutzte Uploads in separate, stabile OPC-Dateifundstellen. */
final class OpcStorageSourceAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    /** @var list<string>|null Begrenzte Auflistung eines Laufs, kein dauerhafter Cache. */
    private ?array $paths = null;

    public function __construct(
        private readonly OpcStorageFileLister $files,
        private readonly LocalPathNormalizer $normalizer,
    ) {}

    public function source(): AssetSource
    {
        return AssetSource::Opc;
    }

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->scanPage($offset, $limit)->references;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        if ($offset < 0 || $limit < 1 || $limit > 100 || ($offset > 0 && $this->paths === null)) {
            throw new InvalidArgumentException('Eine Dateiscanner-Seite benötigt einen gültigen Offset und 1 bis 100 Einträge; der Lauf beginnt bei Offset 0.');
        }
        if ($offset === 0) {
            // Vor einem möglichen Fehler leeren: Niemals die Liste eines alten Laufs weiterverwenden.
            $this->paths = null;
            $this->paths = $this->files->listPaths();
        }
        $slice = array_slice($this->paths ?? [], $offset, $limit);
        $references = [];
        foreach ($slice as $path) {
            $reference = LocalImageReference::fromRaw(
                $path,
                AssetSource::Opc,
                'opc-datei:' . hash('sha256', $path),
                'OPC-Dateispeicher',
                $this->normalizer,
            );
            if ($reference === null) {
                throw new OpcStorageScanException(OpcStorageScanFailure::InvalidFilePath);
            }
            $references[] = $reference;
        }

        return new SourceScanPage($references, count($slice));
    }
}
