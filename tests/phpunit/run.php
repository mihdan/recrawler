#!/usr/bin/env php
<?php
/**
 * PHPUnit test runner that avoids loading vendor-prefixed WordPress stubs.
 *
 * Usage: php tests/phpunit/run.php
 */

$base_dir = dirname(__DIR__, 2);

require_once $base_dir . '/vendor/composer/ClassLoader.php';

$loader = new \Composer\Autoload\ClassLoader();

$loader->addPsr4('PHPUnit\\', [$base_dir . '/vendor/phpunit/phpunit/src']);
$loader->addPsr4('PHPUnit\\TextUI\\', [$base_dir . '/vendor/phpunit/php-tui/src']);
$loader->addPsr4('SebastianBergmann\\', [$base_dir . '/vendor/sebastianbergmann/exporter/src']);
$loader->addPsr4('SebastianBergmann\\Comparator\\', [$base_dir . '/vendor/sebastianbergmann/comparator/src']);
$loader->addPsr4('SebastianBergmann\\Diff\\', [$base_dir . '/vendor/sebastianbergmann/diff/src']);
$loader->addPsr4('SebastianBergmann\\Environment\\', [$base_dir . '/vendor/sebastianbergmann/environment/src']);
$loader->addPsr4('SebastianBergmann\\RecursionContext\\', [$base_dir . '/vendor/sebastianbergmann/recursion-context/src']);
$loader->addPsr4('SebastianBergmann\\Version\\', [$base_dir . '/vendor/sebastianbergmann/version/src']);
$loader->addPsr4('SebastianBergmann\\ObjectEnumerator\\', [$base_dir . '/vendor/sebastianbergmann/object-enumerator/src']);
$loader->addPsr4('SebastianBergmann\\GlobalState\\', [$base_dir . '/vendor/sebastianbergmann/global-state/src']);
$loader->addPsr4('SebastianBergmann\\CodeUnitReverseLookup\\', [$base_dir . '/vendor/sebastianbergmann/code-unit-reverse-lookup/src']);
$loader->addPsr4('SebastianBergmann\\LinesOfCode\\', [$base_dir . '/vendor/sebastianbergmann/lines-of-code/src']);
$loader->addPsr4('SebastianBergmann\\Complexity\\', [$base_dir . '/vendor/sebastianbergmann/complexity/src']);
$loader->addPsr4('SebastianBergmann\\CodeUnit\\', [$base_dir . '/vendor/sebastianbergmann/code-unit/src']);
$loader->addPsr4('SebastianBergmann\\CodeCoverage\\', [$base_dir . '/vendor/sebastianbergmann/code-coverage/src']);
$loader->addPsr4('SebastianBergmann\\Timer\\', [$base_dir . '/vendor/sebastianbergmann/php-timer/src']);
$loader->addPsr4('SebastianBergmann\\CliParser\\', [$base_dir . '/vendor/sebastianbergmann/cli-parser/src']);
$loader->addPsr4('SebastianBergmann\\Invoker\\', [$base_dir . '/vendor/sebastianbergmann/invoker/src']);
$loader->addPsr4('SebastianBergmann\\FileIterator\\', [$base_dir . '/vendor/phpunit/php-file-iterator/src']);
$loader->addPsr4('SebastianBergmann\\Template\\', [$base_dir . '/vendor/sebastianbergmann/php-text-template/src']);
$loader->addPsr4('Brain\\Monkey\\', [$base_dir . '/vendor/brain/monkey/src']);
$loader->addPsr4('Mockery\\', [$base_dir . '/vendor/mockery/mockery/library']);
$loader->addPsr4('Doctrine\\Instantiator\\', [$base_dir . '/vendor/doctrine/instantiator/src/Doctrine/Instantiator']);
$loader->addPsr4('DeepCopy\\', [$base_dir . '/vendor/myclabs/deep-copy/src/DeepCopy']);
$loader->addPsr4('PharIo\\Version\\', [$base_dir . '/vendor/phar-io/version/src']);
$loader->addPsr4('PharIo\\Manifest\\', [$base_dir . '/vendor/phar-io/manifest/src']);
$loader->addPsr4('Webmozart\\Assert\\', [$base_dir . '/vendor/webmozart/assert/src']);
$loader->addPsr4('Psr\\Log\\', [$base_dir . '/vendor/psr/log/Psr/Log']);
$loader->addPsr4('Psr\\Container\\', [$base_dir . '/vendor/psr/container/src']);
$loader->addPsr4('Mihdan\\ReCrawler\\', [$base_dir . '/src']);
$loader->addPsr4('Mihdan\\ReCrawler\\Dependencies\\Psr\\Log\\', [$base_dir . '/vendor-prefixed/psr/log/src']);
$loader->addPsr4('Mihdan\\ReCrawler\\Tests\\', [$base_dir . '/tests/phpunit']);
$loader->addPsr4('Symfony\\Polyfill\\Mbstring\\', [$base_dir . '/vendor/symfony/polyfill-mbstring']);
$loader->addPsr4('Symfony\\Polyfill\\Ctype\\', [$base_dir . '/vendor/symfony/polyfill-ctype']);
$loader->addPsr4('Symfony\\Polyfill\\Intl\\Normalizer\\', [$base_dir . '/vendor/symfony/polyfill-intl-normalizer']);
$loader->addPsr4('Symfony\\Polyfill\\Intl\\Grapheme\\', [$base_dir . '/vendor/symfony/polyfill-intl-grapheme']);
$loader->addPsr4('Symfony\\Contracts\\', [$base_dir . '/vendor/symfony/contracts/src']);
$loader->addPsr4('Symfony\\ServiceContracts\\', [$base_dir . '/vendor/symfony/service-contracts']);
$loader->addPsr4('Nikic\\PHPParser\\', [$base_dir . '/vendor/nikic/php-parser/lib/PHPParser']);
$loader->addPsr4('PhpParser\\', [$base_dir . '/vendor/nikic/php-parser/lib/PhpParser']);
$loader->addPsr4('Text_Template\\', [$base_dir . '/vendor/phpunit/php-text-template/src']);
$loader->addPsr4('TheSeer\\Tokenizer\\', [$base_dir . '/vendor/theseer/tokenizer/src']);

$loader->register(true);

require_once $base_dir . '/vendor/brain/monkey/inc/api.php';

unset($loader);

define('RECRAWLER_VERSION', '0.3.2');
define('RECRAWLER_SLUG', 'recrawler');
define('RECRAWLER_PREFIX', 'recrawler');
define('RECRAWLER_NAME', 'ReCrawler');
define('RECRAWLER_FILE', $base_dir . '/recrawler.php');
define('RECRAWLER_DIR', $base_dir);
define('RECRAWLER_BASENAME', 'recrawler/recrawler.php');
define('RECRAWLER_URL', 'https://example.com/wp-content/plugins/recrawler/');
define('ABSPATH', '/tmp/wordpress/');

\PHPUnit\TextUI\Command::main();
