<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\AssetDisplayMapper;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetCardView;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

/** Lädt eine begrenzte Übersichtsseite ohne Schreibzugriff und ohne N+1-Abfragen. */
final class AssetListAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly AdminAssetRepositoryInterface $assets,
        private readonly LocalPreviewUrlResolver $previewUrls,
        private readonly AssetDisplayMapper $display,
        private readonly string $shopBaseUrl,
    ) {}

    /** @param array<string, mixed> $filters */
    public function load(mixed $page, mixed $pageSize, array $filters, mixed $sort, mixed $direction): AssetListView
    {
        $this->authorization->assertCanManageAssets();
        if (!is_int($page) || $page < 1 || !is_int($pageSize) || $pageSize < 1 || $pageSize > 100) {
            throw new ValidationException('Die Seiteneinstellungen sind ungültig.');
        }
        if (!is_string($sort) || !in_array($sort, ['id', 'status', 'updated_at'], true)
            || !is_string($direction) || !in_array(strtolower($direction), ['asc', 'desc'], true)
        ) {
            throw new ValidationException('Die Sortierung ist ungültig.');
        }
        $normalFilters = $this->filters($filters);
        $offset = ($page - 1) * $pageSize;

        $rows = $this->assets->listPage($offset, $pageSize, $normalFilters, $sort, strtolower($direction));

        return new AssetListView(
            array_map(fn(array $row): AssetCardView => $this->card($row), $rows),
            $this->assets->countForList($normalFilters),
            $page,
            $pageSize,
            $normalFilters,
            $sort,
            strtolower($direction),
        );
    }

    /** @param array<string, scalar|null> $row */
    private function card(array $row): AssetCardView
    {
        $localPath = is_string($row['local_path'] ?? null) ? $row['local_path'] : '';
        $fileName = basename(str_replace('\\', '/', $localPath));
        $status = LabelStatus::fromInput($row['status'] ?? null);
        $source = AssetSource::fromInput($row['source'] ?? null);
        $position = LabelPosition::fromInput($row['position'] ?? null);
        $theme = LabelTheme::fromInput($row['theme'] ?? null);

        return new AssetCardView(
            id: is_numeric($row['id'] ?? null) ? max(0, (int) $row['id']) : 0,
            fileName: $fileName !== '' ? $fileName : 'Unbekanntes Bild',
            previewUrl: $this->previewUrls->resolve($localPath, $this->shopBaseUrl),
            status: $status->value,
            statusLabel: $this->display->statusLabel($status),
            sourceLabel: $this->display->sourceLabel($source),
            position: $position->value,
            theme: $theme->value,
            usageCount: is_numeric($row['usage_count'] ?? null) ? max(0, (int) $row['usage_count']) : 0,
            updatedAt: is_string($row['updated_at'] ?? null) ? $row['updated_at'] : '',
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string|bool>
     */
    private function filters(array $filters): array
    {
        foreach (array_keys($filters) as $name) {
            if (!in_array($name, ['status', 'source', 'present'], true)) {
                throw new ValidationException('Ein Listenfilter ist unbekannt.');
            }
        }
        $normal = [];
        if (isset($filters['status'])) {
            if (!is_string($filters['status']) || LabelStatus::tryFrom($filters['status']) === null) {
                throw new ValidationException('Der Statusfilter ist ungültig.');
            }
            $normal['status'] = $filters['status'];
        }
        if (isset($filters['source'])) {
            if (!is_string($filters['source']) || AssetSource::tryFrom($filters['source']) === null) {
                throw new ValidationException('Der Quellenfilter ist ungültig.');
            }
            $normal['source'] = $filters['source'];
        }
        if (array_key_exists('present', $filters)) {
            if (!is_bool($filters['present'])) {
                throw new ValidationException('Der Fundstellenfilter ist ungültig.');
            }
            $normal['present'] = $filters['present'];
        }

        return $normal;
    }
}
