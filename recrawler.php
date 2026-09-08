<?php
/**
 * Plugin Name: ReCrawler
 * Description: ReCrawler is a small WordPress Plugin for quickly notifying search engines whenever their website content is created, updated, or deleted.
 * Version: 1.1.0
 * Author: Mikhail Kobzarev
 * Author URI: https://www.kobzarev.com/
 * Plugin URI: https://wordpress.org/plugins/recrawler/
 * GitHub Plugin URI: https://github.com/mihdan/recrawler
 * Requires PHP: 8.2
 * Requires at least: 6.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link https://github.com/mihdan/recrawler
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RECRAWLER_VERSION', '1.1.0' );
define( 'RECRAWLER_SLUG', 'recrawler' );
define( 'RECRAWLER_PREFIX', 'recrawler' );
define( 'RECRAWLER_NAME', 'ReCrawler' );
define( 'RECRAWLER_FILE', __FILE__ );
define( 'RECRAWLER_DIR', __DIR__ );
define( 'RECRAWLER_BASENAME', plugin_basename( __FILE__ ) );
define( 'RECRAWLER_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) && file_exists( __DIR__ . '/vendor-prefixed/woocommerce/action-scheduler/action-scheduler.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
	require_once __DIR__ . '/vendor-prefixed/woocommerce/action-scheduler/action-scheduler.php';

	( new Main( new Container() ) )->init();
} else {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			wp_admin_notice(
				__( 'ReCrawler: dependencies are not built, the plugin is doing nothing. Run "composer prefix-dependencies" in the plugin directory.', 'recrawler' ),
				[ 'type' => 'error' ]
			);
		}
	);
}
