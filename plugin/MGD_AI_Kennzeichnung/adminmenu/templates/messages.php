<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;

/** @var string $message Bereits generische, für Benutzer bestimmte Rückmeldung. */
/** @var AdminRoute $route */
$escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div role="status" aria-label="Rückmeldung">
    <p><?= $escapedMessage ?></p>
</div>
<p><a href="<?= htmlspecialchars($route->query(['view' => 'list']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Zurück zur Bildliste</a></p>
