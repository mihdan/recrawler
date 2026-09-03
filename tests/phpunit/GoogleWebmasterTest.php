<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Providers\Bing\BingWebmaster;
use Mihdan\ReCrawler\Providers\Google\GoogleWebmaster;
use Mihdan\ReCrawler\Providers\Yandex\YandexWebmaster;
use Mihdan\ReCrawler\Views\WPOSA;
use Mihdan\ReCrawler\Logger\Logger;
use PHPUnit\Framework\TestCase;

class GoogleWebmasterTest extends TestCase {

	private $logger;
	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->logger = $this->createMock(Logger::class);
		$this->wposa  = $this->createMock(WPOSA::class);
	}

	public function test_get_slug() {
		$provider = new GoogleWebmaster($this->logger, $this->wposa);
		$this->assertSame('google-webmaster', $provider->get_slug());
	}

	public function test_get_token() {
		$this->wposa->method('get_option')
			->with('json_key', 'google_webmaster')
			->willReturn('{"key":"value"}');

		$provider = new GoogleWebmaster($this->logger, $this->wposa);
		$this->assertSame('{"key":"value"}', $provider->get_token());
	}

	public function test_is_enabled_returns_true() {
		$this->wposa->method('get_option')
			->with('enable', 'google_webmaster', 'off')
			->willReturn('on');

		$provider = new GoogleWebmaster($this->logger, $this->wposa);
		$this->assertTrue($provider->is_enabled());
	}

	public function test_is_enabled_returns_false() {
		$this->wposa->method('get_option')
			->with('enable', 'google_webmaster', 'off')
			->willReturn('off');

		$provider = new GoogleWebmaster($this->logger, $this->wposa);
		$this->assertFalse($provider->is_enabled());
	}

	public function test_get_quota_returns_empty_array() {
		$provider = new GoogleWebmaster($this->logger, $this->wposa);
		$this->assertSame([], $provider->get_quota());
	}
}
