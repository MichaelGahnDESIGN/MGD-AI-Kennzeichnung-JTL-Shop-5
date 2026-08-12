<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;

/** Unveränderliche, minimierte Fundstelle eines bereits normalisierten Bildes. */
final class LocalImageReference
{
    private function __construct(
        public readonly string $localPath,
        public readonly AssetSource $source,
        public readonly string $sourceReference,
        public readonly ?string $context,
        public readonly string $assetKey,
    ) {}

    /**
     * Erzeugt das Objekt ausschließlich über den zentralen Normalisierer.
     * Ungültige Datenzeilen werden für einen robusten Seitenscan übersprungen.
     */
    public static function fromRaw(
        mixed $rawPath,
        AssetSource $source,
        mixed $sourceReference,
        mixed $context,
        LocalPathNormalizer $normalizer,
    ): ?self {
        $path = $normalizer->normalize($rawPath);
        $reference = self::technicalReference($sourceReference);
        if ($path === null || $reference === null) {
            return null;
        }

        $plainContext = self::plainContext($context);

        return new self(
            $path,
            $source,
            $reference,
            $plainContext === '' ? null : $plainContext,
            hash('sha256', $path),
        );
    }

    private static function technicalReference(mixed $input): ?string
    {
        if (!is_string($input)
            || !mb_check_encoding($input, 'UTF-8')
            || str_contains($input, "\0")
            || preg_match('/[\x00-\x1F\x7F]/u', $input) === 1
        ) {
            return null;
        }
        $reference = trim($input);

        return $reference !== '' && mb_strlen($reference) <= 255 ? $reference : null;
    }

    /** Entfernt Markup und begrenzt Kontext auf den fachlich nötigen Klartext. */
    private static function plainContext(mixed $input): string
    {
        if (!is_string($input) || !mb_check_encoding($input, 'UTF-8') || str_contains($input, "\0")) {
            return '';
        }

        $decoded = mb_substr($input, 0, 5000);
        for ($round = 0; $round < 10; ++$round) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }
        if (preg_match(
            '/&(?:(?:amp|#0*38|#x0*26);)*(?:lt|gt|#0*(?:60|62);?|#x0*(?:3c|3e);?)/iu',
            $decoded,
        ) === 1) {
            return '';
        }
        $withoutActiveBlocks = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#isu', '', $decoded) ?? '';
        $text = preg_replace('/\s+/u', ' ', strip_tags($withoutActiveBlocks)) ?? '';

        return mb_substr(trim($text), 0, 500);
    }
}
