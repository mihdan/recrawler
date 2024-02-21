<?php
/**
 * IndexNow via Yandex.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\IndexNow;

use \Mihdan\ReCrawler\IndexNowAbstract;

class IndexNow extends IndexNowAbstract {

	public function get_slug(): string {
		return 'index-now';
	}

	public function get_name(): string {
		return __( 'IndexNow', 'recrawler' );
	}

	protected function get_api_url(): string {
		return 'https://api.indexnow.org/indexnow';
	}

	protected function get_bot_useragent(): string {
		return 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)';
	}
}
