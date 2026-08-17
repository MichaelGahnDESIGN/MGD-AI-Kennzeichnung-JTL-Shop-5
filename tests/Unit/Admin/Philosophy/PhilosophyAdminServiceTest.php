<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Philosophy;

require_once __DIR__ . '/../../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Philosophy\PhilosophyAdminService;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use Tests\Support\TransactionalDatabaseFake;

final class PhilosophyAdminServiceTest extends TestCase
{
    #[Test]
    public function beide_sprachen_werden_berechtigt_csrf_geprueft_und_atomar_gespeichert(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $rechte = new PhilosophyAuthorization();
        $csrf = new PhilosophyCsrf();
        $service = new PhilosophyAdminService($rechte, $csrf, $db);

        $resultat = $service->save('sicher', '<p>Deutsch</p>', '<p>English</p>');

        self::assertSame(['de' => '<p>Deutsch</p>', 'en' => '<p>English</p>'], $resultat);
        self::assertSame($resultat, $db->philosophies());
        self::assertSame(1, $rechte->calls);
        self::assertSame(['sicher'], $csrf->tokens);
    }

    #[Test]
    public function ungueltige_zweite_sprache_verhindert_jeden_schreibzugriff(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $service = new PhilosophyAdminService(new PhilosophyAuthorization(), new PhilosophyCsrf(), $db);

        $this->expectException(ValidationException::class);
        try {
            $service->save('sicher', '<p>Deutsch</p>', '<script>nur Angriff</script>');
        } finally {
            self::assertSame([], $db->philosophies());
        }
    }
}

final class PhilosophyAuthorization implements AuthorizationPortInterface
{
    public int $calls = 0;

    public function assertCanManageAssets(): void
    {
        ++$this->calls;
    }

    public function subjectKey(): string
    {
        return 'philosophy-admin';
    }
}

final class PhilosophyCsrf implements CsrfPortInterface
{
    /** @var list<string> */
    public array $tokens = [];

    public function assertValid(string $token): void
    {
        $this->tokens[] = $token;
    }
}
