<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem;

/** Ausschließlich feste Meldungen: Dateinamen, Serverpfade und Ausnahmen bleiben intern. */
enum OpcStorageScanFailure
{
    case RootUnavailable;
    case UnsafePath;
    case UnreadableDirectory;
    case TraversalFailed;
    case ImageLimit;
    case EntryLimit;
    case DepthLimit;
    case InvalidFilePath;

    public function message(): string
    {
        return match ($this) {
            self::RootUnavailable => 'Der OPC-Dateispeicher fehlt oder ist nicht lesbar. Bitte die Shopkonfiguration prüfen.',
            self::UnsafePath => 'Der OPC-Dateispeicher lässt sich nicht sicher der Shopwurzel zuordnen. Symbolische Links sind dort nicht zulässig.',
            self::UnreadableDirectory => 'Ein OPC-Unterordner ist nicht lesbar. Bitte die Verzeichnisberechtigungen prüfen und erneut scannen.',
            self::TraversalFailed => 'Der OPC-Dateispeicher konnte nicht vollständig gelesen werden. Bitte nach Abschluss laufender Dateiänderungen erneut scannen.',
            self::ImageLimit => 'Der OPC-Dateispeicher überschreitet die Scan-Grenze von 9.999 Bilddateien.',
            self::EntryLimit => 'Der OPC-Dateispeicher überschreitet die Scan-Grenze von 20.000 Verzeichniseinträgen.',
            self::DepthLimit => 'Der OPC-Dateispeicher überschreitet die Scan-Grenze von 32 Unterordnerebenen.',
            self::InvalidFilePath => 'Ein OPC-Bildpfad ist nicht eindeutig darstellbar. Bitte Dateinamen auf URL-Codierungen, Sonderzeichen sowie führende oder abschließende Punkte und Leerzeichen prüfen.',
        };
    }
}
