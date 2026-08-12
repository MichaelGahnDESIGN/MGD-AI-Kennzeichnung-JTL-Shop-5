<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner;

use InvalidArgumentException;

/**
 * Unveränderliches Seitenergebnis mit getrennten Roh- und Trefferzahlen.
 *
 * `rowsRead` zählt Datenbankzeilen. Deshalb bleibt die Pagination korrekt,
 * selbst wenn der Sicherheitsfilter alle Referenzen einer Seite verwirft.
 */
final class SourceScanPage
{
    /** @var list<LocalImageReference> */
    public readonly array $references;

    public readonly int $rowsRead;

    /**
     * @param list<mixed> $references Die Laufzeitprüfung schützt auch Adapter
     *                                ohne verlässliche statische Typangabe.
     */
    public function __construct(array $references, int $rowsRead)
    {
        if ($rowsRead < 0 || $rowsRead > 100) {
            throw new InvalidArgumentException('Eine Scannerseite darf nur 0 bis 100 gelesene Zeilen enthalten.');
        }
        $validated = [];
        foreach ($references as $reference) {
            if (!$reference instanceof LocalImageReference) {
                throw new InvalidArgumentException('Eine Scannerseite darf nur lokale Bildreferenzen enthalten.');
            }
            $validated[] = $reference;
        }
        $this->references = $validated;
        $this->rowsRead = $rowsRead;
    }
}
