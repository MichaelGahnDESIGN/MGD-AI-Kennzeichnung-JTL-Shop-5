<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use RuntimeException;

/**
 * Meldet, dass die Datenbank bereits verbindlich gespeichert wurde, während
 * die nachgelagerte Cache-Invalidierung technisch fehlgeschlagen ist.
 */
final class DisplayConfigCommittedException extends RuntimeException {}
