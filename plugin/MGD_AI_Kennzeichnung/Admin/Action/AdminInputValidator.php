<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

/** Zentralisiert die geschlossenen Eingabeverträge aller Admin-Aktionen. */
final class AdminInputValidator
{
    private const MAX_IDS = 500;

    /**
     * @param array<mixed> $ids
     * @return list<int>
     */
    public static function ids(array $ids, int $maximum = self::MAX_IDS): array
    {
        if ($ids === [] || count($ids) > $maximum) {
            throw new ValidationException('Bitte wählen Sie zwischen einem und 500 Einträgen aus.');
        }
        $unique = [];
        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1 || isset($unique[$id])) {
                throw new ValidationException('Die Auswahl enthält ungültige oder doppelte IDs.');
            }
            $unique[$id] = true;
        }
        $normalized = array_keys($unique);
        sort($normalized, SORT_NUMERIC);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $mask
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    public static function changes(array $mask, array $values): array
    {
        $allowed = ['status', 'position', 'theme'];
        foreach ($mask as $field => $enabled) {
            if (!in_array($field, $allowed, true) || !is_bool($enabled)) {
                throw new ValidationException('Die Auswahl der Änderungsfelder ist ungültig.');
            }
        }
        $changes = [];
        foreach ($allowed as $field) {
            if (($mask[$field] ?? false) !== true) {
                continue;
            }
            $value = $values[$field] ?? null;
            if (!is_string($value)) {
                throw new ValidationException('Ein ausgewählter Zielwert fehlt oder ist ungültig.');
            }
            $normal = match ($field) {
                'status' => LabelStatus::tryFrom(strtolower(trim($value))),
                'position' => LabelPosition::tryFrom(strtolower(trim($value))),
                'theme' => LabelTheme::tryFrom(strtolower(trim($value))),
            };
            if ($normal === null) {
                throw new ValidationException('Ein Zielwert gehört nicht zur erlaubten Auswahl.');
            }
            $changes[$field] = $normal->value;
        }
        if ($changes === []) {
            throw new ValidationException('Wählen Sie mindestens ein Änderungsfeld aus.');
        }

        return $changes;
    }

    /**
     * @param list<int> $ids
     * @param array<string, string> $changes
     */
    public static function operationDigest(array $ids, array $changes, string $operation = 'asset-bulk-update'): string
    {
        return hash('sha256', json_encode([$operation, $ids, $changes], JSON_THROW_ON_ERROR));
    }
}
