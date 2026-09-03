<?php
/**
 * Testo configuration.
 *
 * @package Mihdan\ReCrawler
 */

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

// Те же константы плагина и заглушки WordPress, что использует PHPUnit.
require_once __DIR__ . '/tests/phpunit/bootstrap.php';

return new ApplicationConfig(
    suites: [
        new SuiteConfig(
            name: 'Unit',
            location: ['tests/Unit'],
        ),
    ],
);
