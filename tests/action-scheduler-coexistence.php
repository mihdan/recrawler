<?php
/**
 * Проверка сосуществования с чужими копиями ActionScheduler.
 *
 * В 0.3.2 файл functions.php ActionScheduler подключался через composer
 * autoload.files, из-за чего глобальные функции as_*() объявлялись
 * безусловно и в обход ActionScheduler_Versions. Гардов function_exists()
 * в functions.php нет, поэтому любой другой плагин с ActionScheduler
 * (WooCommerce, WPForms, Advanced Ads, отдельный плагин Action Scheduler)
 * падал с "Cannot redeclare as_enqueue_async_action()".
 *
 * Сценарии: чужая копия старее / той же версии / новее, в обоих порядках
 * загрузки. Каждый сценарий выполняется в отдельном процессе, потому что
 * объявление функций необратимо.
 *
 * Запуск: php tests/action-scheduler-coexistence.php
 *
 * @package Mihdan\ReCrawler
 */

define( 'AS_OURS', __DIR__ . '/../vendor-prefixed/woocommerce/action-scheduler' );
define( 'AS_AUTOLOAD', __DIR__ . '/../vendor-prefixed/autoload.php' );

if ( isset( $argv[1] ) ) {
	exit( 'autoload' === $argv[1] ? check_autoload() : check_coexistence( $argv[2], $argv[3] ) );
}

exit( run_all() );

/**
 * Автолоадер не должен объявлять ничего из ActionScheduler.
 *
 * @return int
 */
function check_autoload(): int {
	require_once AS_AUTOLOAD;

	foreach ( array( 'as_enqueue_async_action', 'as_schedule_recurring_action' ) as $fn ) {
		if ( function_exists( $fn ) ) {
			fwrite( STDERR, "autoload.php объявил {$fn}()\n" );
			return 1;
		}
	}

	foreach ( array( 'ActionScheduler', 'ActionScheduler_Versions' ) as $class ) {
		if ( class_exists( $class ) ) {
			fwrite( STDERR, "autoload.php объявил класс {$class}\n" );
			return 1;
		}
	}

	echo "autoload: чисто\n";
	return 0;
}

/**
 * Загружает нашу и чужую копию ActionScheduler и прогоняет plugins_loaded.
 *
 * @param string $foreign Путь к чужой копии.
 * @param string $order   ours-first|foreign-first.
 * @return int
 */
function check_coexistence( string $foreign, string $order ): int {
	wp_stubs();

	$load = array(
		'foreign' => static function () use ( $foreign ) {
			require_once $foreign . '/action-scheduler.php';
		},
		'ours'    => static function () {
			require_once AS_AUTOLOAD;
			require_once AS_OURS . '/action-scheduler.php';
		},
	);

	foreach ( 'foreign-first' === $order ? array( 'foreign', 'ours' ) : array( 'ours', 'foreign' ) as $who ) {
		$load[ $who ]();
	}

	// Инициализация AS глубже functions.php требует полного WP; нас интересует
	// только граница, на которой раньше возникал redeclare-фатал (redeclare —
	// это compile-time fatal, его никакой catch не перехватит).
	try {
		do_action( 'plugins_loaded' );
	} catch ( \Throwable $e ) {
		unset( $e );
	}

	$versions = ActionScheduler_Versions::instance();
	$winner   = $versions->latest_version();
	$source   = (string) array_search( $winner, $versions->get_sources(), true );

	if ( ! function_exists( 'as_enqueue_async_action' ) ) {
		fwrite( STDERR, "as_enqueue_async_action() не объявлена ни одной копией\n" );
		return 1;
	}

	printf( "победила %s из %s\n", $winner, basename( dirname( $source, 2 ) ) );
	return 0;
}

/**
 * Прогоняет все сценарии в дочерних процессах.
 *
 * @return int
 */
function run_all(): int {
	if ( ! is_dir( AS_OURS ) ) {
		fwrite( STDERR, "vendor-prefixed не собран, сначала composer prefix-dependencies\n" );
		return 1;
	}

	$tmp    = sys_get_temp_dir() . '/recrawler-as-coexistence-' . getmypid();
	$failed = 0;

	// Чужая копия старее нашей, той же версии и новее.
	foreach ( array( '3.8.2', current_as_version(), '9.9.9' ) as $version ) {
		$foreign = fake_foreign_copy( $tmp, $version );

		foreach ( array( 'foreign-first', 'ours-first' ) as $order ) {
			printf( 'чужая %-8s %-14s ', $version, $order );
			$failed += run_child( escapeshellarg( $foreign ) . ' ' . escapeshellarg( $order ) );
		}
	}

	printf( '%-24s ', 'autoload' );
	$failed += run_child( 'autoload' );

	rrmdir( $tmp );

	echo 0 === $failed ? "\nOK\n" : "\nПРОВАЛЕНО сценариев: {$failed}\n";
	return 0 === $failed ? 0 : 1;
}

/**
 * Запускает сценарий в отдельном процессе.
 *
 * @param string $args Аргументы.
 * @return int 0 — успех, 1 — провал.
 */
function run_child( string $args ): int {
	$mode = str_starts_with( $args, 'autoload' ) ? '' : 'coexist ';
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . $mode . $args . ' 2>&1', $out, $code );

	echo implode( ' | ', $out ), 0 === $code ? '' : '  <- ПРОВАЛ', "\n";
	return 0 === $code ? 0 : 1;
}

/**
 * Версия ActionScheduler в нашей поставке.
 *
 * @return string
 */
function current_as_version(): string {
	preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', (string) file_get_contents( AS_OURS . '/action-scheduler.php' ), $m );
	return $m[1] ?? '3.9.3';
}

/**
 * Делает копию ActionScheduler под указанную версию — как чужой бандл.
 *
 * @param string $tmp     Временный каталог.
 * @param string $version Версия.
 * @return string Путь к копии.
 */
function fake_foreign_copy( string $tmp, string $version ): string {
	$dir       = $tmp . '/' . $version . '/foreign-plugin/action-scheduler';
	$bootstrap = $dir . '/action-scheduler.php';

	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0777, true );
	}

	// Копия только тех файлов, что нужны до functions.php.
	foreach ( array( 'action-scheduler.php', 'classes/ActionScheduler_Versions.php', 'classes/abstracts/ActionScheduler.php', 'functions.php' ) as $file ) {
		if ( ! is_dir( dirname( $dir . '/' . $file ) ) ) {
			mkdir( dirname( $dir . '/' . $file ), 0777, true );
		}
		copy( AS_OURS . '/' . $file, $dir . '/' . $file );
	}

	$ours = current_as_version();
	$slug = static fn( string $v ): string => str_replace( '.', '_dot_', $v );

	file_put_contents(
		$bootstrap,
		str_replace(
			array( $slug( $ours ), "'" . $ours . "'" ),
			array( $slug( $version ), "'" . $version . "'" ),
			(string) file_get_contents( $bootstrap )
		)
	);

	return $dir;
}

/**
 * Минимальные заглушки WordPress.
 *
 * @return void
 */
function wp_stubs(): void {
	require_once __DIR__ . '/wp-stubs-for-action-scheduler.php';
}

/**
 * Рекурсивно удаляет каталог.
 *
 * @param string $dir Каталог.
 * @return void
 */
function rrmdir( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	foreach ( array_diff( (array) scandir( $dir ), array( '.', '..' ) ) as $item ) {
		$path = $dir . '/' . $item;
		is_dir( $path ) ? rrmdir( $path ) : unlink( $path );
	}

	rmdir( $dir );
}
