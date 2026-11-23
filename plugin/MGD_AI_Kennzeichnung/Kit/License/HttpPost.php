<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Kit\License;

use Plugin\MGD_AI_Kennzeichnung\Kit\Install\Response;

// POST mit Body für die Lizenzprüfung. Install-Client Transport kann nur Kopfzeilen, deshalb eigenes Interface im Kit
interface HttpPost
{
    /** @param array<string,string> $headers */
    public function post(string $url, array $headers, string $body): Response;
}
