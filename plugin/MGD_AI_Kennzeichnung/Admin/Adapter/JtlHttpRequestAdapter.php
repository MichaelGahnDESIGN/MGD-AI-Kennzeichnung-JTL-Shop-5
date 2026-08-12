<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminHttpRequest;

/** Isoliert die unvermeidbaren PHP-Globals vollständig vom Controller. */
final class JtlHttpRequestAdapter
{
    private const JTL_ROUTING_KEYS = ['kPlugin', 'kPluginAdminMenu', 'cPluginTab'];

    public function capture(): AdminHttpRequest
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return new AdminHttpRequest(
            is_string($method) ? strtoupper($method) : 'GET',
            $this->stringKeyed($_GET, true),
            $this->stringKeyed($_POST, false),
        );
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
