<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Update;

use Plugin\MGD_AI_Kennzeichnung\Install\InstallClient;
use Plugin\MGD_AI_Kennzeichnung\Install\Platform\Jtl;
use Plugin\MGD_AI_Kennzeichnung\Install\Settings;
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Admin\InstallReporting;
use Throwable;

// Free-Lizenz Anmeldung > theme.michael-gahn.de, max 1x am Tag, Plugin läuft immer weiter
final class InstallReport
{
    /** null = keine Meldung möglich (Pfad, Name oder System ungültig) */
    public static function client(string $pluginDir): ?InstallClient
    {
        try {
            if (!defined('PFAD_ROOT') || !is_string(PFAD_ROOT)) {
                return null;
            }
            $dataDir = UpdateDataDirectory::prepare(PFAD_ROOT . 'templates_c/');
            $version = PluginVersionReader::read(rtrim($pluginDir, '/') . '/info.xml');

            return Jtl::client($dataDir, UpdatesPageFactory::PRODUCT, $version);
        } catch (Throwable) {
            return null;
        }
    }

    // Bootstrap + Admin, Fehler nie nach außen
    public static function ping(string $pluginDir, bool $force = false): void
    {
        try {
            self::client($pluginDir)?->ping($force);
        } catch (Throwable) {
            return;
        }
    }

    // Schalter für die Updates Seite
    public static function reporting(?InstallClient $client): ?InstallReporting
    {
        if ($client === null) {
            return null;
        }

        return new InstallReporting(
            static function () use ($client): void {
                $client->ping();
            },
            static fn(): bool => $client->isEnabled(),
            static function (bool $on) use ($client): void {
                $client->setEnabled($on);
            },
            static fn(): string => $client->status(),
            Settings::HINT,
        );
    }
}
