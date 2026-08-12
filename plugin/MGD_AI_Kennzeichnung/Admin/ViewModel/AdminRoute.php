<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

/** Unveränderliche, von JTL bereits aufgelöste Route des Admin-Menüpunkts. */
final class AdminRoute
{
    public function __construct(
        public readonly int $pluginId,
        public readonly int $adminMenuId,
    ) {
        if ($pluginId < 1 || $adminMenuId < 1) {
            throw new ValidationException('Die JTL-Administrationsroute ist ungültig.');
        }
    }

    /**
     * Ergänzt ausschließlich intern festgelegte Parameter um die vertrauenswürdige
     * Pluginroute. Die Ausgabe wird im Template zusätzlich HTML-kontextgerecht escaped.
     *
     * @param array<string, int|string> $parameters
     */
    public function query(array $parameters = []): string
    {
        unset($parameters['kPlugin'], $parameters['kPluginAdminMenu']);
        $allowed = ['view', 'asset_id', 'page', 'page_size', 'status', 'source', 'present', 'sort', 'direction'];
        foreach (array_keys($parameters) as $name) {
            if (!in_array($name, $allowed, true)) {
                throw new ValidationException('Der Adminlink enthält einen unbekannten Parameter.');
            }
        }

        return '?' . http_build_query([
            'kPlugin' => $this->pluginId,
            'kPluginAdminMenu' => $this->adminMenuId,
            ...$parameters,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
