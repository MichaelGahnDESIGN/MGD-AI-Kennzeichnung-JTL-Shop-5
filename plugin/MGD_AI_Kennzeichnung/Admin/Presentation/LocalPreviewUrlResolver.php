<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Presentation;

use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;

/**
 * Erzeugt Browser-URLs ausschließlich für bekannte lokale JTL-Bildbereiche.
 *
 * Der Resolver bildet keine Dateisystempfade ab und prüft nicht, ob eine Datei
 * existiert. Diese bewusste Trennung verhindert Informationsabflüsse aus dem
 * Serverdateisystem. Unsichere oder unbekannte Pfade erhalten keine Vorschau.
 */
final class LocalPreviewUrlResolver
{
    /** @var list<string> */
    private const ALLOWED_ROOTS = [
        'media/image/',
        'bilder/',
        'opc/',
        'templates/',
    ];

    public function __construct(
        private readonly LocalPathNormalizer $normalizer = new LocalPathNormalizer(),
    ) {}

    /** Liefert eine same-origin Vorschau-URL oder bei jeder Unsicherheit null. */
    public function resolve(string $localPath, string $shopBaseUrl): ?string
    {
        $normalizedPath = $this->allowedPath($localPath);
        if ($normalizedPath === null || !$this->isSafeShopBaseUrl($shopBaseUrl)) {
            return null;
        }

        $encodedPath = implode('/', array_map(
            static fn(string $segment): string => rawurlencode($segment),
            explode('/', $normalizedPath),
        ));

        return rtrim($shopBaseUrl, '/') . '/' . $encodedPath;
    }

    /** Prüft dieselben Pfadregeln, ohne eine URL oder Dateisystemangabe zu erzeugen. */
    public function accepts(string $localPath): bool
    {
        return $this->allowedPath($localPath) !== null;
    }

    /** Liefert nur einen bereits kanonischen Pfad aus einer festen Bildwurzel. */
    private function allowedPath(string $localPath): ?string
    {
        $normalized = $this->normalizer->normalize($localPath);
        if ($normalized === null) {
            return null;
        }

        foreach (self::ALLOWED_ROOTS as $root) {
            if (str_starts_with($normalized, $root)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Die Basisadresse stammt regulär aus JTL. Trotzdem wird sie fail-closed
     * geprüft, damit ein Konfigurationsfehler keine fremde Vorschau erzeugt.
     */
    private function isSafeShopBaseUrl(string $shopBaseUrl): bool
    {
        $parts = parse_url($shopBaseUrl);
        if (!is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            || (string) $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            return false;
        }

        return filter_var((string) $parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
