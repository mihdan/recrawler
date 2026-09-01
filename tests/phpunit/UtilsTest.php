<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_get_plugin_path() {
		$this->assertSame(RECRAWLER_DIR, Utils::get_plugin_path());
	}

	public function test_get_plugin_basename() {
		$this->assertSame(RECRAWLER_BASENAME, Utils::get_plugin_basename());
	}

	public function test_get_plugin_version() {
		$this->assertSame(RECRAWLER_VERSION, Utils::get_plugin_version());
	}

	public function test_get_plugin_file() {
		$this->assertSame(RECRAWLER_FILE, Utils::get_plugin_file());
	}

	public function test_get_plugin_url() {
		$this->assertSame(RECRAWLER_URL, Utils::get_plugin_url());
	}

	public function test_get_plugin_asset_url() {
		$this->assertSame(RECRAWLER_URL . 'assets/style.css', Utils::get_plugin_asset_url('style.css'));
	}

	public function test_get_plugin_asset_path() {
		$this->assertSame(RECRAWLER_DIR . '/assets/style.css', Utils::get_plugin_asset_path('style.css'));
	}

	public function test_get_plugin_slug() {
		$this->assertSame(RECRAWLER_SLUG, Utils::get_plugin_slug());
	}

	public function test_get_plugin_prefix() {
		$this->assertSame(RECRAWLER_PREFIX, Utils::get_plugin_prefix());
	}

	public function test_get_plugin_name() {
		$this->assertSame(RECRAWLER_NAME, Utils::get_plugin_name());
	}

	public function test_is_response_code_success() {
		$this->assertTrue(Utils::is_response_code_success(200));
		$this->assertTrue(Utils::is_response_code_success(201));
		$this->assertTrue(Utils::is_response_code_success(299));
		$this->assertFalse(Utils::is_response_code_success(199));
		$this->assertFalse(Utils::is_response_code_success(300));
		$this->assertFalse(Utils::is_response_code_success(404));
		$this->assertFalse(Utils::is_response_code_success(500));
	}

	public function test_get_user_agent() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
		$this->assertSame('Mozilla/5.0', Utils::get_user_agent());
	}

	public function test_get_user_agent_empty() {
		unset($_SERVER['HTTP_USER_AGENT']);
		$this->assertSame('', Utils::get_user_agent());
	}

	public function test_get_db_version() {
		_set_wp_override('get_option', function ($option, $default) {
			return $option === 'recrawler_version' ? '0.2.0' : $default;
		});

		$this->assertSame('0.2.0', Utils::get_db_version());
	}

	public function test_set_db_version() {
		$result = Utils::set_db_version('0.3.0');
		$this->assertTrue($result);
	}

	public function test_generate_key() {
		$this->assertSame('550e8400e29b41d4a716446655440000', Utils::generate_key());
	}

	public function test_is_json_valid() {
		$this->assertTrue(Utils::is_json('{"key":"value"}'));
	}

	public function test_is_json_invalid_string() {
		$this->assertFalse(Utils::is_json('not json'));
	}

	public function test_is_json_non_string() {
		$this->assertFalse(Utils::is_json(123));
	}

	public function test_is_json_empty_string() {
		$this->assertFalse(Utils::is_json(''));
	}
}
