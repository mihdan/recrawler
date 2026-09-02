<?php
/**
 * Минимальные заглушки WordPress для tests/action-scheduler-coexistence.php.
 *
 * Хватает ровно до момента, где ActionScheduler подключает functions.php.
 *
 * @package Mihdan\ReCrawler
 */

$GLOBALS['as_test_hooks'] = array();
$GLOBALS['as_test_did']   = array();

/**
 * Регистрирует callback.
 *
 * @param string   $hook     Хук.
 * @param callable $cb       Callback.
 * @param int      $priority Приоритет.
 * @return void
 */
function add_action( $hook, $cb, $priority = 10 ) {
	$GLOBALS['as_test_hooks'][ $hook ][ $priority ][] = $cb;
}

/**
 * Заглушка add_filter().
 *
 * @return void
 */
function add_filter() {}

/**
 * Заглушка apply_filters().
 *
 * @param string $hook  Хук.
 * @param mixed  $value Значение.
 * @return mixed
 */
function apply_filters( $hook, $value = null ) {
	return $value;
}

/**
 * Выполняет хук в порядке приоритетов.
 *
 * @param string $hook Хук.
 * @return void
 */
function do_action( $hook ) {
	$GLOBALS['as_test_did'][ $hook ] = true;

	if ( empty( $GLOBALS['as_test_hooks'][ $hook ] ) ) {
		return;
	}

	ksort( $GLOBALS['as_test_hooks'][ $hook ] );

	foreach ( $GLOBALS['as_test_hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $cb ) {
			call_user_func( $cb );
		}
	}
}

/**
 * Был ли хук выполнен.
 *
 * @param string $hook Хук.
 * @return bool
 */
function did_action( $hook ) {
	return ! empty( $GLOBALS['as_test_did'][ $hook ] );
}

/**
 * Выполняется ли хук прямо сейчас.
 *
 * @return bool
 */
function doing_action() {
	return false;
}

/**
 * Заглушка _deprecated_function().
 *
 * @return void
 */
function _deprecated_function() {}

/**
 * Добавляет слеш в конец.
 *
 * @param string $value Строка.
 * @return string
 */
function trailingslashit( $value ) {
	return rtrim( $value, '/\\' ) . '/';
}

/**
 * Убирает слеш с конца.
 *
 * @param string $value Строка.
 * @return string
 */
function untrailingslashit( $value ) {
	return rtrim( $value, '/\\' );
}
