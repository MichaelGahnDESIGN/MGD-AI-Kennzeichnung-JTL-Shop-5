<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpRequest;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpResponse;

/** Trennt den Update-Dienst vollständig vom konkreten Netzwerktransport. */
interface HttpClientInterface
{
    public function send(HttpRequest $request): HttpResponse;
}
