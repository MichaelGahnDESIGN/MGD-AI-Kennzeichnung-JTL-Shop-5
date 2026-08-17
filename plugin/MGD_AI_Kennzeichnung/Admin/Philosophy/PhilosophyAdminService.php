<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Philosophy;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\PhilosophyRepository;
use Plugin\MGD_AI_Kennzeichnung\Service\PhilosophySanitizer;
use RuntimeException;
use Throwable;

/** Kapselt das zweisprachige, atomare Speichern der AI-Philosophie. */
final class PhilosophyAdminService
{
    private readonly PhilosophySanitizer $sanitizer;

    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly DbInterface $db,
        ?PhilosophySanitizer $sanitizer = null,
    ) {
        $this->sanitizer = $sanitizer ?? new PhilosophySanitizer();
    }

    /** @return array{de: string, en: string} */
    public function load(): array
    {
        $this->authorization->assertCanManageAssets();
        $repository = new PhilosophyRepository($this->db, $this->sanitizer);

        return ['de' => $repository->findForLocale('de'), 'en' => $repository->findForLocale('en')];
    }

    /** @return array{de: string, en: string} */
    public function save(string $csrfToken, mixed $deutsch, mixed $englisch): array
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        if (!is_string($deutsch) || !is_string($englisch)
            || strlen($deutsch) > 50_000 || strlen($englisch) > 50_000
        ) {
            throw new ValidationException('Beide Sprachfassungen müssen begrenzte Texte sein.');
        }

        $inhalte = [
            'de' => $this->sanitizer->sanitize($deutsch),
            'en' => $this->sanitizer->sanitize($englisch),
        ];
        if ($inhalte['de'] === '' || $inhalte['en'] === '') {
            throw new ValidationException('Beide Sprachfassungen benötigen einen sicheren Inhalt.');
        }
        if ($this->db->getPDO()->inTransaction() || !$this->db->beginTransaction()) {
            throw new RuntimeException('Die Philosophie-Texte konnten nicht sicher zum Speichern reserviert werden.');
        }

        try {
            $repository = new PhilosophyRepository($this->db, $this->sanitizer);
            $repository->upsert('de', $inhalte['de']);
            $repository->upsert('en', $inhalte['en']);
            if (!$this->db->commit()) {
                throw new RuntimeException('Die Philosophie-Texte konnten nicht bestätigt werden.');
            }
        } catch (Throwable $fehler) {
            try {
                if (!$this->db->rollback()) {
                    throw new RuntimeException('Der Datenbank-Rollback meldete false.');
                }
            } catch (Throwable) {
                throw new RuntimeException('Speichern und Rücknahme der Philosophie-Texte sind fehlgeschlagen.', 0, $fehler);
            }
            throw $fehler;
        }

        return $inhalte;
    }
}
