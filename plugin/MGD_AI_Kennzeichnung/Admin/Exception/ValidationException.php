<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use InvalidArgumentException;

/** Meldet ausschließlich verständliche Validierungsfehler. */
final class ValidationException extends InvalidArgumentException {}
