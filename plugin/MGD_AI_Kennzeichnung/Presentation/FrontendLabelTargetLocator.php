<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Presentation;

use InvalidArgumentException;

/**
 * Erzeugt die eng begrenzten DOM-Selektoren für eine lokale Bilddatei.
 *
 * Der Dateiname wird vor jeder Verwendung erneut geprüft. Dadurch können weder
 * Datenbankwerte noch manipulierte Pfade zusätzliche CSS-Selektoren in den
 * JTL-Outputfilter einschleusen.
 */
final class FrontendLabelTargetLocator
{
    public function imageSelector(string $filename): string
    {
        $this->assertSafeFilename($filename);

        return implode(', ', [
            'img[src="' . $filename . '"]',
            'img[src$="/' . $filename . '"]',
            'img[src*="/' . $filename . '?"]',
            'img[src*="/' . $filename . '#"]',
        ]);
    }

    public function backgroundSelector(string $filename): string
    {
        $this->assertSafeFilename($filename);

        return implode(', ', [
            '[style*="/' . $filename . '"]',
            '[data-image-src="' . $filename . '"]',
            '[data-image-src$="/' . $filename . '"]',
            '[data-image-src*="/' . $filename . '?"]',
            '[data-image-src*="/' . $filename . '#"]',
        ]);
    }

    private function assertSafeFilename(string $filename): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/D', $filename) !== 1) {
            throw new InvalidArgumentException('Der Bilddateiname ist nicht zulässig.');
        }
    }
}
