<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use RuntimeException;

/** Meldet eine fehlende Verwaltungsberechtigung ohne interne Details. */
final class AccessDeniedException extends RuntimeException {}
