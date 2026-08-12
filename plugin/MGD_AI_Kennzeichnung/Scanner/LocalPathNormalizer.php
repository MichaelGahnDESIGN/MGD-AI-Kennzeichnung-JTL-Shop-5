<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner;

/**
 * Normalisiert ausschließlich eindeutig lokale Bildreferenzen.
 *
 * Die Klasse arbeitet rein lexikalisch: Sie greift weder auf das Dateisystem
 * noch auf DNS oder das Netzwerk zu. SVG wird bewusst abgelehnt, weil ein
 * später direkt ausgeliefertes SVG aktiven Inhalt enthalten kann. Soll SVG
 * künftig unterstützt werden, braucht es zuerst eine eigene sichere
 * Auslieferungs- und Bereinigungsstrategie.
 */
final class LocalPathNormalizer
{
    private const MAXIMUM_LENGTH = 1024;
    private const MAXIMUM_SEGMENTS = 64;
    private const MAXIMUM_SEGMENT_LENGTH = 255;

    /** @var array<string, true> */
    private array $allowedHosts = [];

    /** @param list<string> $allowedHosts Explizite eigene Shop- oder CDN-Hosts. */
    public function __construct(array $allowedHosts = [])
    {
        foreach ($allowedHosts as $host) {
            $normal = strtolower(rtrim(trim($host), '.'));
            if ($normal !== '' && filter_var($normal, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
                $this->allowedHosts[$normal] = true;
            }
        }
    }

    /** Liefert den kanonischen relativen Slashpfad oder lehnt fail-closed ab. */
    public function normalize(mixed $reference): ?string
    {
        if (!is_string($reference)
            || $reference === ''
            || str_contains($reference, "\0")
            || !mb_check_encoding($reference, 'UTF-8')
            || strlen($reference) > self::MAXIMUM_LENGTH
        ) {
            return null;
        }

        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $path = $this->pathFromAllowedUrl($reference);
        if ($path === null) {
            return null;
        }

        /*
         * Vor und nach jeder bounded Dekodierstufe wird dieselbe vollständige
         * Sicherheitsprüfung ausgeführt. Damit können ein Schema, Host, Query,
         * NUL oder Traversal nicht erst in einer späteren Stufe entstehen.
         */
        for ($round = 0; $round < 6; ++$round) {
            if (!$this->isSafeLocalSyntax($path)) {
                return null;
            }
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }
        if (!$this->isSafeLocalSyntax($path) || preg_match('/%[0-9a-f]{2}/i', $path) === 1) {
            return null;
        }

        $segments = [];
        foreach (explode('/', ltrim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..'
                || strlen($segment) > self::MAXIMUM_SEGMENT_LENGTH
                || preg_match('/^[. ]|[. ]$/u', $segment) === 1
            ) {
                return null;
            }
            $segments[] = $segment;
        }

        if ($segments === [] || count($segments) > self::MAXIMUM_SEGMENTS) {
            return null;
        }

        $normalized = implode('/', $segments);
        if (strlen($normalized) > self::MAXIMUM_LENGTH
            || preg_match('#(?:^|/)includes/config\.JTL-Shop\.ini\.php(?:$|/)#i', $normalized) === 1
            || preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/iD', $normalized) !== 1
        ) {
            return null;
        }

        return $normalized;
    }

    /**
     * Trennt erlaubte absolute HTTPS/HTTP-Referenzen von lokalen Pfaden. Der
     * Hostvergleich ist exakt; Subdomain-Tricks und Credentials sind verboten.
     */
    private function pathFromAllowedUrl(string $reference): ?string
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $reference) !== 1) {
            return $reference;
        }

        $parts = parse_url($reference);
        if (!is_array($parts)
            || !isset($parts['scheme'], $parts['host'], $parts['path'])
            || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            return null;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        return isset($this->allowedHosts[$host]) ? (string) $parts['path'] : null;
    }

    /** Prüft jede dekodierte Zwischenstufe erneut als rein lokalen Pfad. */
    private function isSafeLocalSyntax(string $path): bool
    {
        return $path !== ''
            && strlen($path) <= self::MAXIMUM_LENGTH
            && mb_check_encoding($path, 'UTF-8')
            && !str_contains($path, "\0")
            && !str_contains($path, '?')
            && !str_contains($path, '#')
            && !str_starts_with($path, '//')
            && !str_starts_with($path, '\\\\')
            && preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) !== 1
            && preg_match('/^[a-z]:[\\\\\/]/i', $path) !== 1
            && preg_match('/[\\\\\x00-\x1F\x7F\x{2024}\x{2044}\x{2215}\x{29F8}\x{FE52}\x{FF0E}\x{FF0F}]/u', $path) !== 1;
    }
}
