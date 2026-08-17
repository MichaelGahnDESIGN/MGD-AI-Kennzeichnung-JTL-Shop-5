<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\HttpClientInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpRequest;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpResponse;
use RuntimeException;

/**
 * Führt genau begrenzte HTTPS-Anfragen über die cURL-Erweiterung aus.
 *
 * Unsichere Transportoptionen werden bereits vor einem Netzwerkzugriff
 * abgewiesen. Antwortdaten werden während des Empfangs hart begrenzt, damit
 * ein unerwartet großer Serverinhalt nicht den Arbeitsspeicher des Shops füllt.
 */
final class CurlHttpClient implements HttpClientInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        $this->assertSafe($request);

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Die PHP-cURL-Erweiterung ist nicht verfügbar.');
        }

        $curl = curl_init($request->url);
        if ($curl === false) {
            throw new RuntimeException('Die HTTPS-Anfrage konnte nicht vorbereitet werden.');
        }

        $body = '';
        $headers = [];
        $headerZeilen = [];
        foreach ($request->headers as $name => $wert) {
            $headerZeilen[] = $name . ': ' . $wert;
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headerZeilen,
            CURLOPT_CONNECTTIMEOUT => $request->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $request->totalTimeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $daten) use (&$body, $request): int {
                unset($handle);
                if (strlen($body) + strlen($daten) > $request->maximumResponseBytes) {
                    return 0;
                }

                $body .= $daten;

                return strlen($daten);
            },
            CURLOPT_HEADERFUNCTION => static function ($handle, string $zeile) use (&$headers): int {
                unset($handle);
                $teile = explode(':', $zeile, 2);
                if (count($teile) === 2) {
                    $name = trim($teile[0]);
                    $wert = trim($teile[1]);
                    if ($name !== '' && $wert !== '') {
                        $headers[$name] = $wert;
                    }
                }

                return strlen($zeile);
            },
        ]);

        $erfolgreich = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $fehlernummer = curl_errno($curl);
        curl_close($curl);

        if ($erfolgreich === false || $fehlernummer !== 0) {
            throw new RuntimeException('Die sichere HTTPS-Anfrage ist fehlgeschlagen.');
        }

        return new HttpResponse($status, $body, $headers);
    }

    private function assertSafe(HttpRequest $request): void
    {
        $teile = parse_url($request->url);
        if (!is_array($teile)
            || ($teile['scheme'] ?? null) !== 'https'
            || !is_string($teile['host'] ?? null)
            || isset($teile['user'])
            || isset($teile['pass'])
            || !$request->verifyTls
            || $request->followRedirects
            || $request->connectTimeoutSeconds < 1
            || $request->connectTimeoutSeconds > 5
            || $request->totalTimeoutSeconds < 1
            || $request->totalTimeoutSeconds > 10
            || $request->maximumResponseBytes < 1
            || $request->maximumResponseBytes > 65_536
        ) {
            throw new RuntimeException('Unsichere HTTPS-Transportparameter wurden abgewiesen.');
        }
    }
}
