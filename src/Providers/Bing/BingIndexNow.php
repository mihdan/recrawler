<?php
/**
 * IndexNow via Bing.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Bing;

use \Mihdan\ReCrawler\IndexNowAbstract;

class BingIndexNow extends IndexNowAbstract {

	public function get_slug(): string {
		return 'bing-index-now';
	}

	public function get_name(): string {
		return __( 'Bing IndexNow', 'recrawler' );
	}

	protected function get_api_url(): string {
		return 'https://www.bing.com/indexnow';
	}

	protected function get_bot_useragent(): string {
		return 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';
	}
}
