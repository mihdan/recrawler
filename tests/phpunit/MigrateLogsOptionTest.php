<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Migrations\Migrations;
use PHPUnit\Framework\TestCase;

class MigrateLogsOptionTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_merges_logs_into_general_keeping_old_values() {
		$saved   = [];
		$deleted = [];

		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			if ( 'recrawler_logs' === $option ) {
				return [ 'enable' => 'off', 'lifetime' => 30 ];
			}

			if ( 'recrawler_general' === $option ) {
				return [ 'ping_delay' => 5 ];
			}

			return $default;
		} );

		_set_wp_override( 'update_option', function ( $option, $value ) use ( &$saved ) {
			$saved[ $option ] = $value;
			return true;
		} );

		_set_wp_override( 'delete_option', function ( $option ) use ( &$deleted ) {
			$deleted[] = $option;
			return true;
		} );

		$this->assertTrue( ( new Migrations() )->migrate_1_0_0() );

		$this->assertSame( 'off', $saved['recrawler_general']['enable'] );
		$this->assertSame( 30, $saved['recrawler_general']['lifetime'] );
		$this->assertSame( 5, $saved['recrawler_general']['ping_delay'] );
		$this->assertContains( 'recrawler_logs', $deleted );
	}

	public function test_old_values_win_on_key_collision() {
		$saved = [];

		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			if ( 'recrawler_logs' === $option ) {
				return [ 'enable' => 'off' ];
			}

			if ( 'recrawler_general' === $option ) {
				return [ 'enable' => 'on' ];
			}

			return $default;
		} );

		_set_wp_override( 'update_option', function ( $option, $value ) use ( &$saved ) {
			$saved[ $option ] = $value;
			return true;
		} );

		( new Migrations() )->migrate_1_0_0();

		$this->assertSame( 'off', $saved['recrawler_general']['enable'] );
	}

	public function test_returns_true_when_nothing_to_migrate() {
		$saved = [];

		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return $default;
		} );

		_set_wp_override( 'update_option', function ( $option, $value ) use ( &$saved ) {
			$saved[ $option ] = $value;
			return true;
		} );

		$this->assertTrue( ( new Migrations() )->migrate_1_0_0() );
		$this->assertArrayNotHasKey( 'recrawler_general', $saved );
	}
}
