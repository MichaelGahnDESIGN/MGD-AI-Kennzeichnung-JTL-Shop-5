<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Controller;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminRequestNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminActionHandlerInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminPage;

/** Erlaubt nur fest definierte Leseansichten und POST-Aktionen. */
final class AdminAssetController
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly AdminRequestNormalizer $normalizer,
        private readonly AdminActionHandlerInterface $handler,
    ) {}

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    public function handle(string $method, array $query, array $post): AdminPage
    {
        if ($method === 'GET') {
            $this->authorization->assertCanManageAssets();
            $view = $query['view'] ?? 'list';
            if (!is_string($view)) {
                throw new ValidationException('Die Ansicht ist ungültig.');
            }

            return match ($view) {
                'list' => $this->handler->list($this->normalizer->assetList($query)),
                'detail' => $this->handler->detail($this->normalizer->assetDetail($query)),
                'cleanup' => $this->handler->cleanupList($this->normalizer->cleanupList($query)),
                default => throw new ValidationException('Die Ansicht ist nicht freigegeben.'),
            };
        }
        if ($method !== 'POST') {
            throw new ValidationException('Die HTTP-Methode ist nicht freigegeben.');
        }

        /* Sicherheitsreihenfolge: Berechtigung, CSRF, erst danach fachliche Formulardaten. */
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($this->normalizer->csrfToken($post));
        $action = $this->normalizer->action($post);

        return match ($action) {
            'single-update' => $this->handler->singleUpdate($this->normalizer->singleUpdate($post)),
            'bulk-preview' => $this->handler->bulkPreview($this->normalizer->bulkPreview($post)),
            'bulk-update' => $this->handler->bulkExecute($this->normalizer->bulkExecute($post)),
            'scan' => $this->handler->scan($this->normalizer->scan($post)),
            'cleanup-preview' => $this->handler->cleanupPreview($this->normalizer->cleanupPreview($post)),
            'cleanup-execute' => $this->handler->cleanupExecute($this->normalizer->cleanupExecute($post)),
            default => throw new ValidationException('Die Formularaktion ist nicht freigegeben.'),
        };
    }
}
