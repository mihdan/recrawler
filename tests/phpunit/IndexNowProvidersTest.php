<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Providers\Bing\BingIndexNow;
use Mihdan\ReCrawler\Providers\Yandex\YandexIndexNow;
use Mihdan\ReCrawler\Providers\IndexNow\IndexNow;
use Mihdan\ReCrawler\Providers\Seznam\SeznamIndexNow;
use Mihdan\ReCrawler\Providers\Naver\NaverIndexNow;
use Mihdan\ReCrawler\Views\WPOSA;
use Mihdan\ReCrawler\Logger\Logger;
use PHPUnit\Framework\TestCase;

abstract class IndexNowTestCase extends TestCase {

	protected $logger;
	protected $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->logger = $this->createMock(Logger::class);
		$this->wposa  = $this->createMock(WPOSA::class);
	}
}

class BingIndexNowTest extends IndexNowTestCase {

	public function test_get_slug() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new BingIndexNow($this->logger, $this->wposa);
		$this->assertSame('bing-index-now', $provider->get_slug());
	}

	public function test_get_name() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new BingIndexNow($this->logger, $this->wposa);
		$this->assertSame('Bing IndexNow', $provider->get_name());
	}

	public function test_get_api_url() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new BingIndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_api_url');
		$method->setAccessible(true);

		$this->assertSame('https://www.bing.com/indexnow', $method->invoke($provider));
	}

	public function test_get_bot_useragent() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new BingIndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_bot_useragent');
		$method->setAccessible(true);

		$this->assertStringContainsString('bingbot', $method->invoke($provider));
	}
}

class YandexIndexNowTest extends IndexNowTestCase {

	public function test_get_slug() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new YandexIndexNow($this->logger, $this->wposa);
		$this->assertSame('yandex-index-now', $provider->get_slug());
	}

	public function test_get_api_url() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new YandexIndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_api_url');
		$method->setAccessible(true);

		$this->assertSame('https://yandex.com/indexnow', $method->invoke($provider));
	}
}

class IndexNowProviderTest extends IndexNowTestCase {

	public function test_get_slug() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new IndexNow($this->logger, $this->wposa);
		$this->assertSame('index-now', $provider->get_slug());
	}

	public function test_get_api_url() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new IndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_api_url');
		$method->setAccessible(true);

		$this->assertSame('https://api.indexnow.org/indexnow', $method->invoke($provider));
	}
}

class SeznamIndexNowTest extends IndexNowTestCase {

	public function test_get_slug() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new SeznamIndexNow($this->logger, $this->wposa);
		$this->assertSame('seznam-index-now', $provider->get_slug());
	}

	public function test_get_api_url() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new SeznamIndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_api_url');
		$method->setAccessible(true);

		$this->assertSame('https://search.seznam.cz/indexnow', $method->invoke($provider));
	}
}

class NaverIndexNowTest extends IndexNowTestCase {

	public function test_get_slug() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new NaverIndexNow($this->logger, $this->wposa);
		$this->assertSame('naver-index-now', $provider->get_slug());
	}

	public function test_get_api_url() {
		$this->wposa->method('get_option')->willReturn('test-key');

		$provider = new NaverIndexNow($this->logger, $this->wposa);

		$ref = new \ReflectionClass($provider);
		$method = $ref->getMethod('get_api_url');
		$method->setAccessible(true);

		$this->assertSame('https://searchadvisor.naver.com/indexnow', $method->invoke($provider));
	}
}
