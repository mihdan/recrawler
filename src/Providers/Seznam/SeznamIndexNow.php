<?php
/**
 * IndexNow via Yandex.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Seznam;

use \Mihdan\ReCrawler\IndexNowAbstract;

class SeznamIndexNow extends IndexNowAbstract {

	public function get_slug(): string {
		return 'seznam-index-now';
	}

	public function get_name(): string {
		return __( 'Seznam IndexNow', 'recrawler' );
	}

	protected function get_api_url(): string {
		return 'https://search.seznam.cz/indexnow';
	}

	protected function get_bot_useragent(): string {
		return 'Mozilla/5.0 (compatible; SeznamBot/3.2; +http://napoveda.seznam.cz/en/seznambot-intro/)';
	}
}
