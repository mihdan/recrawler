<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Logger\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_get_logger_table_name() {
		$logger = new Logger();

		$GLOBALS['wpdb'] = new \stdClass();
		$GLOBALS['wpdb']->prefix = 'wp_';

		$this->assertSame('wp_recrawler_log', $logger->get_logger_table_name());
	}

	public function test_log_inserts_into_database() {
		$logger = new Logger();

		$GLOBALS['wpdb'] = new class {
			public $inserted = false;
			public $table = '';
			public $data = [];
			public $prefix = 'wp_';

			public function insert($table, $data) {
				$this->table = $table;
				$this->data = $data;
				$this->inserted = true;
			}

			public function query($sql) {}
		};

		$logger->log('info', 'Test message', ['status_code' => 200]);

		$this->assertTrue($GLOBALS['wpdb']->inserted);
		$this->assertSame('wp_recrawler_log', $GLOBALS['wpdb']->table);
		$this->assertSame('info', $GLOBALS['wpdb']->data['level']);
		$this->assertSame('Test message', $GLOBALS['wpdb']->data['message']);
	}

	public function test_log_with_empty_context() {
		$logger = new Logger();

		$GLOBALS['wpdb'] = new class {
			public $data = [];
			public $prefix = 'wp_';

			public function insert($table, $data) {
				$this->data = $data;
			}

			public function query($sql) {}
		};

		$logger->log('error', 'Error occurred');

		$this->assertSame('error', $GLOBALS['wpdb']->data['level']);
		$this->assertSame('Error occurred', $GLOBALS['wpdb']->data['message']);
		$this->assertSame('outgoing', $GLOBALS['wpdb']->data['direction']);
		$this->assertSame(200, $GLOBALS['wpdb']->data['status_code']);
	}

	public function test_log_with_custom_context_overrides_defaults() {
		$logger = new Logger();

		$GLOBALS['wpdb'] = new class {
			public $data = [];
			public $prefix = 'wp_';

			public function insert($table, $data) {
				$this->data = $data;
			}

			public function query($sql) {}
		};

		$logger->log('warning', 'Warning', [
			'status_code'   => 500,
			'search_engine' => 'yandex-index-now',
			'direction'     => 'incoming',
		]);

		$this->assertSame(500, $GLOBALS['wpdb']->data['status_code']);
		$this->assertSame('yandex-index-now', $GLOBALS['wpdb']->data['search_engine']);
		$this->assertSame('incoming', $GLOBALS['wpdb']->data['direction']);
	}
}
