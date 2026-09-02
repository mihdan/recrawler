<?php
/**
 * Regression check: composer autoload must NOT declare ActionScheduler's global
 * as_*() functions. They live in functions.php without function_exists() guards
 * and may only be loaded by the winner of ActionScheduler's version negotiation.
 * Eager loading caused fatal "Cannot redeclare as_enqueue_async_action()" in 0.3.2.
 *
 * @package Mihdan\ReCrawler
 */

require_once __DIR__ . '/../../vendor-prefixed/autoload.php';

assert( ! function_exists( 'as_enqueue_async_action' ), 'autoload.php must not declare as_enqueue_async_action()' );
assert( ! class_exists( 'ActionScheduler', false ), 'autoload.php must not declare class ActionScheduler' );

echo "OK\n";
