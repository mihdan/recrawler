<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Providers\Bing\BingWebmaster;
use Mihdan\ReCrawler\Providers\Google\GoogleWebmaster;
use Mihdan\ReCrawler\Providers\Yandex\YandexWebmaster;
use Mihdan\ReCrawler\Views\WPOSA;
use Mihdan\ReCrawler\Logger\Logger;
use PHPUnit\Framework\TestCase;

class YandexWebmasterTest extends TestCase {

	private $logger;
	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->logger = $this->createMock(Logger::class);
		$this->wposa  = $this->createMock(WPOSA::class);
	}

	public function test_get_slug() {
		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('yandex-webmaster', $provider->get_slug());
	}

	public function test_get_token() {
		$this->wposa->method('get_option')
			->with('access_token', 'yandex_webmaster')
			->willReturn('ya-token');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('ya-token', $provider->get_token());
	}

	public function test_get_user_id() {
		$this->wposa->method('get_option')
			->with('user_id', 'yandex_webmaster')
			->willReturn('12345');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('12345', $provider->get_user_id());
	}

	public function test_get_host_id() {
		$this->wposa->method('get_option')
			->with('host_id', 'yandex_webmaster')
			->willReturn('host-1');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('host-1', $provider->get_host_id());
	}

	public function test_get_client_id() {
		$this->wposa->method('get_option')
			->with('client_id', 'yandex_webmaster')
			->willReturn('client-id');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('client-id', $provider->get_client_id());
	}

	public function test_get_client_secret() {
		$this->wposa->method('get_option')
			->with('client_secret', 'yandex_webmaster')
			->willReturn('client-secret');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertSame('client-secret', $provider->get_client_secret());
	}

	public function test_get_ping_endpoint() {
		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertStringContainsString('api.webmaster.yandex.net', $provider->get_ping_endpoint());
	}

	public function test_get_quota_endpoint() {
		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertStringContainsString('recrawl/quota', $provider->get_quota_endpoint());
	}

	public function test_is_enabled_returns_true() {
		$this->wposa->method('get_option')
			->with('enable', 'yandex_webmaster', 'off')
			->willReturn('on');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertTrue($provider->is_enabled());
	}

	public function test_is_enabled_returns_false() {
		$this->wposa->method('get_option')
			->with('enable', 'yandex_webmaster', 'off')
			->willReturn('off');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		$this->assertFalse($provider->is_enabled());
	}

	public function test_get_settings_url_points_at_the_yandex_tab() {
		$this->wposa->method('get_prefix')->willReturn('recrawler');
		$this->wposa->expects($this->once())
			->method('get_tab_url')
			->with('recrawler_yandex_webmaster')
			->willReturn('https://example.com/wp-admin/admin.php?page=recrawler&tab=yandex_webmaster');

		$provider = new YandexWebmaster($this->logger, $this->wposa);

		$this->assertSame(
			'https://example.com/wp-admin/admin.php?page=recrawler&tab=yandex_webmaster',
			$provider->get_settings_url()
		);
	}

	public function test_setup_hooks_registers_actions() {
		$this->wposa->method('get_option')->willReturn('on');

		$provider = new YandexWebmaster($this->logger, $this->wposa);
		_expect_wp_mock('add_action', 6);
		_expect_wp_mock('add_filter', 0);

		$provider->setup_hooks();
	}

	protected function tearDown(): void {
		parent::tearDown();
		_assert_wp_mock('add_action');
		_assert_wp_mock('add_filter');
	}
}
