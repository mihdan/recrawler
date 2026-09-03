<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Cron;
use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase {

	private $logger;
	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->logger = $this->createMock(Logger::class);
		$this->wposa  = $this->createMock(WPOSA::class);
	}

	public function test_action_name_constant() {
		$this->assertSame('recrawler/cron/clear_log', Cron::ACTION_NAME);
	}

	public function test_setup_hooks_registers_actions() {
		$cron = new Cron($this->logger, $this->wposa);
		_expect_wp_mock('add_action', 2);

		$cron->setup_hooks();
	}

	protected function tearDown(): void {
		parent::tearDown();
		_assert_wp_mock('add_action');
	}

	public function test_schedule_task_does_not_crash_when_as_not_initialized() {
		$cron = new Cron($this->logger, $this->wposa);
		$cron->schedule_task();
		$this->assertTrue(true);
	}

	public function test_clear_log_executes_query() {
		$this->wposa->method('get_option')->willReturnMap([
			['lifetime', 'general', 1, 7],
			['cron_events', 'general', 'off', 'off'],
		]);

		$this->logger->method('get_logger_table_name')->willReturn('wp_recrawler_log');

		$cron = new Cron($this->logger, $this->wposa);

		$GLOBALS['wpdb'] = new class {
			public $query_executed = false;
			public $query_sql = '';

			public function prepare($sql, $val) {
				return sprintf($sql, $val);
			}

			public function query($sql) {
				$this->query_sql = $sql;
				$this->query_executed = true;
			}
		};

		$result = $cron->clear_log();

		$this->assertTrue($result);
		$this->assertTrue($GLOBALS['wpdb']->query_executed);
		$this->assertStringContainsString('DELETE FROM', $GLOBALS['wpdb']->query_sql);
	}

	public function test_clear_log_logs_when_cron_events_enabled() {
		$this->wposa->method('get_option')->willReturnMap([
			['lifetime', 'general', 1, 7],
			['cron_events', 'general', 'off', 'on'],
		]);

		$this->logger->method('get_logger_table_name')->willReturn('wp_recrawler_log');
		$this->logger->expects($this->once())->method('info');

		$cron = new Cron($this->logger, $this->wposa);

		$GLOBALS['wpdb'] = new class {
			public function prepare($sql, $val) {
				return sprintf($sql, $val);
			}

			public function query($sql) {}
		};

		$cron->clear_log();
	}
}
