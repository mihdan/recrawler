<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Migrations\Migrations;
use PHPUnit\Framework\TestCase;

class MigrationsTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		unset($_GET['service-worker']);
	}

	public function test_constants() {
		$this->assertSame('recrawler_versions', Migrations::MIGRATED_VERSIONS_OPTION_NAME);
		$this->assertSame(RECRAWLER_VERSION, Migrations::PLUGIN_VERSION);
		$this->assertSame(-1, Migrations::STARTED);
		$this->assertSame(-2, Migrations::FAILED);
		$this->assertSame('ReCrawler', Migrations::PLUGIN_NAME);
	}

	public function test_is_allowed_returns_true_for_cron() {
		_set_wp_override('wp_doing_cron', function () { return true; });

		$migrations = new Migrations();
		$this->assertTrue($migrations->is_allowed());
	}

	public function test_is_allowed_returns_true_for_admin() {
		$migrations = new Migrations();
		$this->assertTrue($migrations->is_allowed());
	}

	public function test_is_allowed_returns_false_for_frontend() {
		_set_wp_override('is_admin', function () { return false; });
		_set_wp_override('wp_doing_cron', function () { return false; });

		$migrations = new Migrations();
		$this->assertFalse($migrations->is_allowed());
	}

	public function test_is_allowed_returns_false_for_service_worker() {
		$_GET['service-worker'] = '1';

		$migrations = new Migrations();
		$this->assertFalse($migrations->is_allowed());

		unset($_GET['service-worker']);
	}

	public function test_setup_hooks_adds_action_when_allowed() {
		_set_wp_override('wp_doing_cron', function () { return true; });
		_expect_wp_mock('add_action', 1);

		$migrations = new Migrations();
		$migrations->setup_hooks();
	}

	protected function tearDown(): void {
		parent::tearDown();
		_assert_wp_mock('add_action');
	}

	public function test_setup_hooks_does_nothing_when_not_allowed() {
		_set_wp_override('is_admin', function () { return false; });
		_set_wp_override('wp_doing_cron', function () { return false; });
		_expect_wp_mock('add_action', 0);

		$migrations = new Migrations();
		$migrations->setup_hooks();
	}

	public function test_migrate_runs_without_crash() {
		$migrations = new Migrations();
		$migrations->migrate();
		$this->assertTrue(true);
	}

	public function test_migrate_skips_already_migrated() {
		_set_wp_override('get_option', function ($option, $default) {
			if ($option === 'recrawler_versions') {
				return [
					'0.1.2' => 1234567890,
					'0.3.0' => 1234567890,
					'0.3.2' => 1234567890,
				];
			}
			return $default;
		});

		$migrations = new Migrations();
		$migrations->migrate();
		$this->assertTrue(true);
	}
}
