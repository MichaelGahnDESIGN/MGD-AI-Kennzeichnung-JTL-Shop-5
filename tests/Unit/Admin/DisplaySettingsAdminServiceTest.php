<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Display\DisplaySettingsAdminService;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\DisplayConfigPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelLanguage;

/** Prüft das Speichern der Anzeigeoptionen an der Sicherheitsgrenze zum POST-Request. */
final class DisplaySettingsAdminServiceTest extends TestCase
{
    #[Test]
    public function gueltiger_post_wird_kanonisch_gespeichert_und_typisiert_zurueckgegeben(): void
    {
        $config = new RecordingDisplayConfigPort();
        $service = new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config);

        $settings = $service->save('csrf', self::validPost());

        self::assertSame([self::canonicalValues()], $config->savedValues);
        self::assertSame(LabelLanguage::De, $settings->language);
        self::assertSame(18, $settings->fontSize);
        self::assertSame(12, $settings->outerMargin);
        self::assertSame(8, $settings->innerPadding);
        self::assertSame(10, $settings->borderRadius);
        self::assertSame(6, $settings->blur);
        self::assertSame(20, $settings->transparency);
    }

    #[Test]
    public function speichern_bewahrt_die_nicht_editierbaren_sichtbaren_optionen_aus_der_geladenen_konfiguration(): void
    {
        $config = new RecordingDisplayConfigPort([
            'show_credit' => 'Y',
            'update_notices' => 'Y',
            'language' => 'auto',
            'font_size' => '8',
            'outer_margin' => '0',
            'inner_padding' => '0',
            'border_radius' => '0',
            'blur' => '0',
            'transparency' => '0',
        ]);
        $service = new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config);

        $settings = $service->save('csrf', self::validPost());

        self::assertTrue($settings->showCredit);
        self::assertTrue($settings->updateNoticesEnabled);
        self::assertSame(LabelLanguage::De, $settings->language);
        self::assertSame(18, $settings->fontSize);
        self::assertSame(1, $config->loadCalls);
    }

    #[Test]
    public function unbekanntes_post_feld_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $service = new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config);

        $this->expectException(ValidationException::class);
        try {
            $service->save('csrf', self::validPost() + ['unbekannt' => 'wert']);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function arraywert_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['font_size'] = ['18'];

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function leerzeichen_in_ganzzahl_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['font_size'] = ' 18';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function dezimalzahl_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['blur'] = '6.0';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function css_einheit_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['outer_margin'] = '12px';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function unterschreitung_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['font_size'] = '7';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function ueberschreitung_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['transparency'] = '91';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function zu_grosses_inner_padding_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['inner_padding'] = '33';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function zu_grosser_border_radius_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['border_radius'] = '33';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function zu_grosser_blur_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['blur'] = '25';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function ungueltige_sprache_wird_vor_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $post = self::validPost();
        $post['language'] = 'fr';

        $this->expectException(ValidationException::class);
        try {
            (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->save('csrf', $post);
        } finally {
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function falsches_csrf_token_wird_vor_der_post_verarbeitung_und_dem_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $csrf = new ValidCsrf(false);
        $service = new DisplaySettingsAdminService(new AllowedAuthorization(), $csrf, $config);

        $this->expectException(CsrfException::class);
        try {
            $service->save('falsch', self::validPost());
        } finally {
            self::assertSame(['falsch'], $csrf->tokens);
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function fehlende_adminberechtigung_wird_vor_csrf_pruefung_und_speichern_abgewiesen(): void
    {
        $config = new RecordingDisplayConfigPort();
        $csrf = new ValidCsrf();
        $service = new DisplaySettingsAdminService(new AllowedAuthorization(false), $csrf, $config);

        $this->expectException(AccessDeniedException::class);
        try {
            $service->save('csrf', self::validPost());
        } finally {
            self::assertSame([], $csrf->tokens);
            self::assertSame([], $config->savedValues);
        }
    }

    #[Test]
    public function laden_prueft_zuerst_die_berechtigung_und_uebersetzt_die_jtl_konfiguration(): void
    {
        $config = new RecordingDisplayConfigPort(self::canonicalValues());
        $settings = (new DisplaySettingsAdminService(new AllowedAuthorization(), new ValidCsrf(), $config))->load();

        self::assertSame(LabelLanguage::De, $settings->language);
        self::assertSame(18, $settings->fontSize);
        self::assertSame(1, $config->loadCalls);
    }

    /** @return array<string, string> */
    private static function validPost(): array
    {
        return self::canonicalValues();
    }

    /** @return array<string, string> */
    private static function canonicalValues(): array
    {
        return [
            'language' => 'de',
            'font_size' => '18',
            'outer_margin' => '12',
            'inner_padding' => '8',
            'border_radius' => '10',
            'blur' => '6',
            'transparency' => '20',
        ];
    }
}

/** Bewusst einfacher Fachport, der Speicherversuche vollständig sichtbar macht. */
final class RecordingDisplayConfigPort implements DisplayConfigPortInterface
{
    /** @var list<array<string, string>> */
    public array $savedValues = [];
    public int $loadCalls = 0;

    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values = []) {}

    /** @return array<string, mixed> */
    public function load(): array
    {
        ++$this->loadCalls;

        return $this->values;
    }

    /** @param array<string, string> $values */
    public function save(array $values): void
    {
        $this->savedValues[] = $values;
    }
}

/** Kapselt in den Tests die ausdrücklich erlaubte bzw. verweigerte Adminberechtigung. */
final class AllowedAuthorization implements AuthorizationPortInterface
{
    public function __construct(private readonly bool $allowed = true) {}

    public function assertCanManageAssets(): void
    {
        if (!$this->allowed) {
            throw new AccessDeniedException('Nicht berechtigt.');
        }
    }

    public function subjectKey(): string
    {
        return 'test-subject';
    }
}

/** Kapselt einen nachvollziehbaren CSRF-Erfolg beziehungsweise -Fehler. */
final class ValidCsrf implements CsrfPortInterface
{
    /** @var list<string> */
    public array $tokens = [];

    public function __construct(private readonly bool $valid = true) {}

    public function assertValid(string $token): void
    {
        $this->tokens[] = $token;
        if (!$this->valid) {
            throw new CsrfException('Ungültiges CSRF-Token.');
        }
    }
}
