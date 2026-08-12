<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminHttpRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

/** Isoliert die unvermeidbaren PHP-Globals vollständig vom Controller. */
final class JtlHttpRequestAdapter
{
    private const JTL_ROUTING_KEYS = ['kPlugin', 'kPluginAdminMenu'];

    public function capture(int $pluginId, int $adminMenuId): AdminHttpRequest
    {
        if ($pluginId < 1 || $adminMenuId < 1) {
            throw new ValidationException('Die JTL-Administrationsroute ist ungültig.');
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $normalizedMethod = is_string($method) ? strtoupper($method) : 'GET';
        $this->assertRoute($_GET, $pluginId, $adminMenuId, false);
        $this->assertRoute($_POST, $pluginId, $adminMenuId, $normalizedMethod === 'POST');

        return new AdminHttpRequest(
            $normalizedMethod,
            $this->stringKeyed($_GET, true),
            $this->stringKeyed($_POST, true),
        );
    }

    /**
     * Vorhandene Routendaten müssen vollständig, kanonisch und identisch mit
     * dem bereits von JTL aufgelösten Plugin-/Menükontext sein.
     *
     * @param array<mixed, mixed> $input
     */
    private function assertRoute(array $input, int $pluginId, int $adminMenuId, bool $required): void
    {
        $hasPlugin = array_key_exists('kPlugin', $input);
        $hasMenu = array_key_exists('kPluginAdminMenu', $input);
        if ($required && (!$hasPlugin || !$hasMenu)) {
            throw new ValidationException('Die JTL-Administrationsroute ist ungültig.');
        }
        if (($hasPlugin && !$this->matchesCanonicalId($input['kPlugin'], $pluginId))
            || ($hasMenu && !$this->matchesCanonicalId($input['kPluginAdminMenu'], $adminMenuId))
        ) {
            throw new ValidationException('Die JTL-Administrationsroute ist ungültig.');
        }
    }

    private function matchesCanonicalId(mixed $raw, int $expected): bool
    {
        return is_string($raw)
            && preg_match('/^[1-9][0-9]*$/D', $raw) === 1
            && $raw === (string) $expected;
    }

    /**
     * JTL-eigene Routingparameter gehören nicht zum Pluginformular. Alle
     * übrigen Schlüssel bleiben erhalten, damit der Normalizer Unbekanntes
     * ausdrücklich zurückweist.
     *
     * @param array<mixed, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function stringKeyed(array $input, bool $removeJtlRouting): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if ($removeJtlRouting && in_array($key, self::JTL_ROUTING_KEYS, true)) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
