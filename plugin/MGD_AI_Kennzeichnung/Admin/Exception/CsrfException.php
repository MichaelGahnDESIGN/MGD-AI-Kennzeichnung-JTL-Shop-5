<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use RuntimeException;

/** Meldet eine fehlgeschlagene Formularherkunftsprüfung. */
final class CsrfException extends RuntimeException {}
