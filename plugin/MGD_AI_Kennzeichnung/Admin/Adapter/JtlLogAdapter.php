<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LogPortInterface;
use Psr\Log\LoggerInterface;

/** Übergibt ausschließlich feste Ereigniscodes und Mengen an JTLs PSR-Logger. */
final class JtlLogAdapter implements LogPortInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function event(string $code, int $count): void
    {
        $this->logger->warning('mgd_ai_admin_event', ['event_code' => $code, 'count' => max(0, $count)]);
    }
}
