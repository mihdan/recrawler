<?php
/**
 * Settings class.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$options = [
	'recrawler_general',
	'recrawler_index_now',
	'recrawler_bing_webmaster',
	'recrawler_google_webmaster',
	'recrawler_yandex_webmaster',
	'recrawler_logs',
	'recrawler_version',
];

if ( is_multisite() ) {
	// Delete settings.
	foreach( $options as $option ) {
		delete_site_option( $option );
	}

	// Delete Log tables.
	$sites = get_sites( [ 'fields' => 'ids' ] );

	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}recrawler_log" ); // phpcs:ignore
		restore_current_blog();
	}
} else {
	// Delete settings.
	foreach( $options as $option ) {
		delete_option( $option );
	}

	// Delete Log table.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}recrawler_log" ); // phpcs:ignore
}
