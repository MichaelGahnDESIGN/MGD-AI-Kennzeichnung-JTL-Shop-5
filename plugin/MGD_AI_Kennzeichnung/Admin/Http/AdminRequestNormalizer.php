<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Http;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetDetailRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\ScanRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\SingleUpdateRequest;

/**
 * Einzige Übersetzungsgrenze zwischen untypisierten HTTP-Werten und der
 * Anwendungslogik. Es findet keine PHP-String-/Zahl-Coercion statt.
 */
final class AdminRequestNormalizer
{
    private const MAX_FIELDS = 32;
    private const MAX_DEPTH = 3;
    private const MAX_TOKEN_LENGTH = 256;
    private const ALLOWED_TARGETS = [
        'status' => ['unreviewed', 'none', 'generated', 'partially-generated', 'modified', 'deepfake'],
        'position' => ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
        'theme' => ['auto', 'light', 'dark'],
    ];

    /** @param array<string, mixed> $post */
    public function bulkPreview(array $post): BulkPreviewRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token', 'asset_ids', 'mask', 'values']);
        if (($post['action'] ?? null) !== 'bulk-preview') {
            throw new ValidationException('Die Formularaktion ist ungültig.');
        }
        $mask = $this->checkboxMap($post['mask'] ?? null);
        $values = $this->stringMap($post['values'] ?? null, 64);

        return new BulkPreviewRequest(
            $this->token($post['csrf_token'] ?? null),
            $this->ids($post['asset_ids'] ?? null),
            $mask,
            $values,
        );
    }

    /** @param array<string, mixed> $post */
    public function bulkExecute(array $post): BulkExecuteRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token', 'confirmation_token']);
        if (($post['action'] ?? null) !== 'bulk-update') {
            throw new ValidationException('Die Formularaktion ist ungültig.');
        }

        return new BulkExecuteRequest(
            $this->token($post['csrf_token'] ?? null),
            $this->token($post['confirmation_token'] ?? null),
        );
    }

    /**
     * Liest vor jeder weiteren POST-Auswertung ausschließlich das begrenzte CSRF-Feld.
     *
     * @param array<string, mixed> $post
     */
    public function csrfToken(array $post): string
    {
        return $this->token($post['csrf_token'] ?? null);
    }

    /** @param array<string, mixed> $post */
    public function action(array $post): string
    {
        $action = $post['action'] ?? null;
        if (!is_string($action) || !in_array($action, [
            'single-update', 'bulk-preview', 'bulk-update', 'scan', 'cleanup-preview', 'cleanup-execute',
        ], true)) {
            throw new ValidationException('Die Formularaktion ist nicht freigegeben.');
        }

        return $action;
    }

    /** @param array<string, mixed> $post */
    public function singleUpdate(array $post): SingleUpdateRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token', 'asset_id', 'mask', 'values']);
        if (($post['action'] ?? null) !== 'single-update') {
            throw new ValidationException('Die Formularaktion ist ungültig.');
        }

        $ids = $this->ids([$post['asset_id'] ?? null]);

        return new SingleUpdateRequest(
            $this->token($post['csrf_token'] ?? null),
            $ids[0],
            $this->checkboxMap($post['mask'] ?? null),
            $this->stringMap($post['values'] ?? null, 64),
        );
    }

    /** @param array<string, mixed> $post */
    public function scan(array $post): ScanRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token']);

        return new ScanRequest($this->token($post['csrf_token'] ?? null));
    }

    /** @param array<string, mixed> $post */
    public function cleanupPreview(array $post): CleanupPreviewRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token', 'usage_ids']);

        return new CleanupPreviewRequest(
            $this->token($post['csrf_token'] ?? null),
            $this->ids($post['usage_ids'] ?? null),
        );
    }

    /** @param array<string, mixed> $post */
    public function cleanupExecute(array $post): CleanupExecuteRequest
    {
        $this->assertPayloadBounds($post);
        $this->assertExactKeys($post, ['action', 'csrf_token', 'confirmation_token']);

        return new CleanupExecuteRequest(
            $this->token($post['csrf_token'] ?? null),
            $this->token($post['confirmation_token'] ?? null),
        );
    }

    /** @param array<string, mixed> $query */
    public function assetList(array $query): AssetListRequest
    {
        $this->assertPayloadBounds($query);
        $this->assertExactKeys($query, ['view', 'page', 'page_size', 'status', 'source', 'present', 'sort', 'direction']);
        if (($query['view'] ?? 'list') !== 'list') {
            throw new ValidationException('Die Listenansicht ist ungültig.');
        }
        $page = $this->positiveDecimal($query['page'] ?? '1', 1000000);
        $pageSize = $this->positiveDecimal($query['page_size'] ?? '25', 100);
        $filters = [];
        foreach (['status', 'source'] as $key) {
            if (!array_key_exists($key, $query)) {
                continue;
            }
            if ($query[$key] === '') {
                continue;
            }
            if (!is_string($query[$key]) || strlen($query[$key]) > 64) {
                throw new ValidationException('Ein Listenfilter ist ungültig.');
            }
            $filters[$key] = $query[$key];
        }
        if (array_key_exists('present', $query)) {
            if ($query['present'] === '') {
                unset($query['present']);
            } elseif (!is_string($query['present']) || !in_array($query['present'], ['0', '1'], true)) {
                throw new ValidationException('Der Fundstellenfilter ist ungültig.');
            } else {
                $filters['present'] = $query['present'] === '1';
            }
        }
        $sort = $query['sort'] ?? 'id';
        $direction = $query['direction'] ?? 'asc';
        if (!is_string($sort) || !in_array($sort, ['id', 'status', 'updated_at'], true)
            || !is_string($direction) || !in_array($direction, ['asc', 'desc'], true)
        ) {
            throw new ValidationException('Die Sortierung ist ungültig.');
        }

        return new AssetListRequest($page, $pageSize, $filters, $sort, $direction);
    }

    /** @param array<string, mixed> $query */
    public function assetDetail(array $query): AssetDetailRequest
    {
        $this->assertExactKeys($query, ['view', 'asset_id']);
        if (($query['view'] ?? null) !== 'detail') {
            throw new ValidationException('Die Detailansicht ist ungültig.');
        }

        return new AssetDetailRequest($this->positiveDecimal($query['asset_id'] ?? null, PHP_INT_MAX));
    }

    /** @param array<string, mixed> $query */
    public function cleanupList(array $query): CleanupListRequest
    {
        $this->assertExactKeys($query, ['view', 'page', 'page_size']);
        if (($query['view'] ?? null) !== 'cleanup') {
            throw new ValidationException('Die Bereinigungsansicht ist ungültig.');
        }

        return new CleanupListRequest(
            $this->positiveDecimal($query['page'] ?? '1', 1000000),
            $this->positiveDecimal($query['page_size'] ?? '25', 100),
        );
    }

    /**
     * @param mixed $input
     *
     * @return list<int>
     */
    private function ids(mixed $input): array
    {
        if (!is_array($input) || !array_is_list($input) || $input === [] || count($input) > 500) {
            throw new ValidationException('Die ID-Auswahl ist ungültig oder zu groß.');
        }
        $ids = [];
        foreach ($input as $rawId) {
            if (!is_string($rawId) || preg_match('/^[1-9][0-9]*$/D', $rawId) !== 1) {
                throw new ValidationException('Eine ID besitzt kein kanonisches Dezimalformat.');
            }
            $id = (int) $rawId;
            if ($id < 1 || (string) $id !== $rawId) {
                throw new ValidationException('Eine ID liegt außerhalb des erlaubten Bereichs.');
            }
            $ids[] = $id;
        }

        return $ids;
    }

    /** @return array<string, bool> */
    private function checkboxMap(mixed $input): array
    {
        if (!is_array($input) || array_is_list($input)) {
            throw new ValidationException('Die Feldmaske ist ungültig.');
        }
        $result = [];
        foreach ($input as $key => $value) {
            if (!is_string($key) || !in_array($key, ['status', 'position', 'theme'], true)
                || !is_string($value) || !in_array($value, ['0', '1'], true)
            ) {
                throw new ValidationException('Die Feldmaske enthält einen unbekannten Wert.');
            }
            $result[$key] = $value === '1';
        }

        return $result;
    }

    /** @return array<string, string> */
    private function stringMap(mixed $input, int $maximumLength): array
    {
        if (!is_array($input) || array_is_list($input)) {
            throw new ValidationException('Die Zielwerte sind ungültig.');
        }
        $result = [];
        foreach ($input as $key => $value) {
            if (!is_string($key) || !in_array($key, ['status', 'position', 'theme'], true)
                || !is_string($value) || $value === '' || strlen($value) > $maximumLength
                || !in_array($value, self::ALLOWED_TARGETS[$key], true)
            ) {
                throw new ValidationException('Ein Zielwert ist ungültig.');
            }
            $result[$key] = $value;
        }

        return $result;
    }

    private function token(mixed $input): string
    {
        if (!is_string($input) || $input === '' || strlen($input) > self::MAX_TOKEN_LENGTH) {
            throw new ValidationException('Ein Sicherheitstoken fehlt oder ist ungültig.');
        }

        return $input;
    }

    private function positiveDecimal(mixed $input, int $maximum): int
    {
        if (!is_string($input) || preg_match('/^[1-9][0-9]*$/D', $input) !== 1) {
            throw new ValidationException('Eine Zahl besitzt kein kanonisches Dezimalformat.');
        }
        $value = (int) $input;
        if ($value < 1 || $value > $maximum || (string) $value !== $input) {
            throw new ValidationException('Eine Zahl liegt außerhalb des erlaubten Bereichs.');
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private function assertPayloadBounds(array $payload): void
    {
        $count = 0;
        $walk = function (array $values, int $depth) use (&$walk, &$count): void {
            if ($depth > self::MAX_DEPTH) {
                throw new ValidationException('Das Formular ist zu tief verschachtelt.');
            }
            foreach ($values as $value) {
                ++$count;
                if ($count > 1000) {
                    throw new ValidationException('Das Formular enthält zu viele Werte.');
                }
                if (is_array($value)) {
                    $walk($value, $depth + 1);
                }
            }
        };
        $walk($payload, 1);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string>         $allowed
     */
    private function assertExactKeys(array $payload, array $allowed): void
    {
        if (count($payload) > self::MAX_FIELDS) {
            throw new ValidationException('Das Formular enthält zu viele Felder.');
        }
        foreach (array_keys($payload) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new ValidationException('Das Formular enthält ein unbekanntes Feld.');
            }
        }
    }
}
