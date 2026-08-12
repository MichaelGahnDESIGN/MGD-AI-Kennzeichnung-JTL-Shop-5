<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use JTL\Helpers\Form;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfTokenPortInterface;

/** Nutzt die explizite Tokenvariante von JTL-Shop 5.7.2 Form::validateToken(). */
final class JtlCsrfAdapter implements CsrfPortInterface, CsrfTokenPortInterface
{
    /** @param array<string, mixed> $session */
    public function __construct(private readonly array $session) {}

    public function assertValid(string $token): void
    {
        if (!Form::validateToken($token)) {
            throw new CsrfException('Die Sicherheitsprüfung ist fehlgeschlagen.');
        }
    }

    public function token(): string
    {
        $token = $this->session['jtl_token'] ?? null;
        if (!is_string($token) || $token === '' || strlen($token) > 256) {
            throw new CsrfException('Das Formular-Sicherheitstoken ist nicht verfügbar.');
        }

        return $token;
    }
}
