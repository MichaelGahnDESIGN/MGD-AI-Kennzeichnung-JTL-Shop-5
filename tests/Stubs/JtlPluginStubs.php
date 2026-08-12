<?php

declare(strict_types=1);

/*
 * Diese Datei beschreibt PHPStan nur die beiden JTL-Typen, die das minimale
 * Grundgerüst benötigt. Sie wird nicht von Composer geladen und gelangt daher
 * niemals in die Laufzeit eines JTL-Shops.
 */

namespace JTL\Events;

class Dispatcher {}

namespace JTL\Plugin;

use JTL\Events\Dispatcher;

class Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void {}
}
