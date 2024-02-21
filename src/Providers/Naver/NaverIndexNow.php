<?php
/**
 * IndexNow via Naver.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Naver;

use \Mihdan\ReCrawler\IndexNowAbstract;

class NaverIndexNow extends IndexNowAbstract {

	public function get_slug(): string {
		return 'naver-index-now';
	}

	public function get_name(): string {
		return __( 'Seznam IndexNow', 'recrawler' );
	}

	protected function get_api_url(): string {
		return 'https://searchadvisor.naver.com/indexnow';
	}

	protected function get_bot_useragent(): string {
		return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko; compatible; Yeti/1.1; https://naver.me/spd) Chrome/112.0.0.0 Safari/537.36';
	}
}
