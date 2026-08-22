<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\IO;

use Plugin\MGD_AI_Kennzeichnung\Admin\Value\LocalAssetLabel;

/** Begrenzte, JSON-kompatible Antwort ohne interne Fehlerdetails. */
final class AdminIoResponse
{
    /** @param array<string, scalar|null> $data */
    private function __construct(
        public readonly bool $ok,
        public readonly string $code,
        public readonly string $message,
        public readonly array $data,
    ) {}

    public static function fromLabel(LocalAssetLabel $label, string $message): self
    {
        return new self(true, 'ok', $message, [
            'id' => $label->id,
            'localPath' => $label->localPath,
            'status' => $label->status->value,
            'position' => $label->position->value,
            'theme' => $label->theme->value,
            'source' => $label->source->value,
            'persisted' => $label->persisted,
        ]);
    }

    public static function error(string $code, string $message): self
    {
        return new self(false, $code, $message, []);
    }
}
