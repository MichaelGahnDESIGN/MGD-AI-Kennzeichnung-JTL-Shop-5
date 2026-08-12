<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Exception;

use RuntimeException;

/** Meldet ein nicht mehr vorhandenes Asset ohne Datenbankdetails. */
final class AssetNotFoundException extends RuntimeException {}
