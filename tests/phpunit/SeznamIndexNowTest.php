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
