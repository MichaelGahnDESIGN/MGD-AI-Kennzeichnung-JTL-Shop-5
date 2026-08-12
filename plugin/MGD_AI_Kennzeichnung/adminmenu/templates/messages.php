<?php

declare(strict_types=1);

/** @var string $message Bereits generische, für Benutzer bestimmte Rückmeldung. */
$escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div role="status" aria-label="Rückmeldung">
    <p><?= $escapedMessage ?></p>
</div>
