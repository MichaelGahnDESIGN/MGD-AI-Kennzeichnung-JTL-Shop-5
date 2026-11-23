<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Kit\Platform\Jtl;

use Plugin\MGD_AI_Kennzeichnung\Kit\Catalog\Recommendations;
use Plugin\MGD_AI_Kennzeichnung\Kit\Support\Text;

// Tab "Weitere Plugins" ohne Smarty, nur Admin, keine fremden Bilder, nix wird installiert
final class RecommendationsView
{
    public static function render(?Recommendations $r, Guard $guard): string
    {
        try {
            if ($r === null || !$guard->canManage()) {
                return '';
            }

            return self::html($r->load());
        } catch (\Throwable) {
            return '';
        }
    }

    /** @param array{items?:mixed,source?:mixed} $result von Recommendations::load() */
    public static function html(array $result): string
    {
        $items = is_array($result['items'] ?? null) ? $result['items'] : [];
        $source = $result['source'] ?? '';
        $out = '<div class="mgd-kit-recommendations">';
        if ($source === 'stale' || $source === 'fallback') {
            $out .= '<p class="text-muted">Die Liste konnte gerade nicht aktualisiert werden. Du siehst den letzten bekannten Stand.</p>';
        }
        if ($items === []) {
            return $out . '<p>Gerade gibt es keine weiteren Plugins.</p></div>';
        }
        $out .= '<ul class="mgd-kit-cards">';
        foreach ($items as $it) {
            $out .= is_array($it) ? self::card($it) : '';
        }

        return $out . '</ul></div>';
    }

    private static function card(array $it): string
    {
        $slug = is_string($it['slug'] ?? null) ? $it['slug'] : '';
        if (preg_match(Recommendations::SLUG_RE, $slug) !== 1) {
            return '';
        }
        // Link immer selbst bauen, nie aus den Daten
        $url = Recommendations::DETAILS_PREFIX . $slug;
        $version = is_string($it['version'] ?? null) ? ltrim($it['version'], 'vV') : '';

        return '<li class="mgd-kit-card"><h3>' . Text::e((string) ($it['name'] ?? '')) . '</h3>'
            . '<p>' . Text::e((string) ($it['description'] ?? '')) . '</p>'
            . ($version !== '' ? '<p class="mgd-kit-version">Version ' . Text::e($version) . '</p>' : '')
            . '<p><a class="btn btn-default mgd-kit-btn" href="' . Text::e($url) . '" target="_blank" rel="noopener noreferrer">Mehr erfahren</a></p></li>';
    }
}
