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
