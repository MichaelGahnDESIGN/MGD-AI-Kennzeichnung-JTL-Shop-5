<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/*
 * Die Formatprüfung betrachtet ausschließlich eigenen PHP-Code.
 * Installierte Abhängigkeiten bleiben bewusst außerhalb des Suchbereichs.
 */
$finder = Finder::create()
    ->in([
        __DIR__ . '/plugin',
        __DIR__ . '/tests',
    ])
    // erzeugter MGD Update Client, nicht von Hand ändern
    ->exclude('MGD_AI_Kennzeichnung/UpdateClient');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS2.0' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
