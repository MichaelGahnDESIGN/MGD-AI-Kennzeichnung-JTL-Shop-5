<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use RuntimeException;

/** Meldet eine fehlende, abgelaufene oder manipulierte Vorschau-Bestätigung. */
final class ConfirmationException extends RuntimeException {}
