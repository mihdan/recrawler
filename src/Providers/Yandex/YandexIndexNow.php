<?php
/**
 * IndexNow via Yandex.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Yandex;

use \Mihdan\ReCrawler\IndexNowAbstract;

class YandexIndexNow extends IndexNowAbstract {

	public function get_slug(): string {
		return 'yandex-index-now';
	}

	public function get_name(): string {
		return __( 'Yandex IndexNow', 'recrawler' );
	}

	protected function get_api_url(): string {
		return 'https://yandex.com/indexnow';
	}

	protected function get_bot_useragent(): string {
		return 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)';
	}
}
