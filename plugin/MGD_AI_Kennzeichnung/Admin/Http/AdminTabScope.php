<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Http;

use JTL\Plugin\PluginInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlHttpRequestAdapter;

/**
 * Trennt die parallele Ausführung aller JTL-Customlinks vom tatsächlich
 * adressierten Tab. Nicht adressierte Dateien lesen keine fremden Payloads,
 * dürfen ihren neutralen Lesestand jedoch weiterhin ausgeben.
 */
final class AdminTabScope
{
    private function __construct(
        public readonly bool $isAddressed,
        public readonly bool $shouldRender,
        public readonly AdminHttpRequest $request,
    ) {}

    /**
     * Liefert nur dem durch die kanonische JTL-Route adressierten Dateitab den
     * echten Request. Jeder gültige Tab darf unabhängig davon seinen neutralen
     * Leserequest rendern. So bleibt der normale JTL-GET ohne Routingparameter
     * funktionsfähig, obwohl JTL alle Customlink-Dateien ausführt.
     */
    public static function capture(
        PluginInterface $plugin,
        mixed $menuId,
        string $expectedFilename,
        bool $preservePostRouting = false,
    ): self {
        if (!is_int($menuId) || $menuId < 1) {
            return self::inactive();
        }
        $item = $plugin->getAdminMenu()->getItemByID($menuId);
        if (!is_object($item) || !isset($item->cDateiname)
            || !is_string($item->cDateiname) || $item->cDateiname !== $expectedFilename
        ) {
            return self::inactive();
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $normalizedMethod = is_string($method) ? strtoupper($method) : 'GET';
        $route = $normalizedMethod === 'POST' ? $_POST : $_GET;
        if (!array_key_exists('kPlugin', $route)
            || !array_key_exists('kPluginAdminMenu', $route)
            || !self::matchesId($route['kPlugin'], $plugin->getID())
            || !self::matchesId($route['kPluginAdminMenu'], $menuId)
        ) {
            return self::neutral();
        }

        return new self(
            true,
            true,
            (new JtlHttpRequestAdapter())->capture($plugin->getID(), $menuId, $preservePostRouting),
        );
    }

    private static function inactive(): self
    {
        return new self(false, false, new AdminHttpRequest('GET', [], []));
    }

    /** Ein gültiger, aber nicht adressierter Tab erhält keinen fremden Payload. */
    private static function neutral(): self
    {
        return new self(false, true, new AdminHttpRequest('GET', [], []));
    }

    /**
     * JTL 5.7.2 erstellt nach dem Customlink eine neue 200-Response. Deshalb
     * erscheinen Fehler hier als feste, datenfreie Inline-Hinweise.
     */
    public static function error(string $message): string
    {
        return '<div class="alert alert-danger" role="alert">'
            . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</div>';
    }

    private static function matchesId(mixed $raw, int $expected): bool
    {
        return is_string($raw)
            && preg_match('/^[1-9][0-9]*$/D', $raw) === 1
            && $raw === (string) $expected;
    }
}
