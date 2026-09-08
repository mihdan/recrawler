<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\SeoPress;
use PHPUnit\Framework\TestCase;

class SeoPressTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'seopress', ( new SeoPress() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_wp_override( 'get_post_meta', function ( $post_id, $key, $single = false ) {
			return '_seopress_robots_index' === $key ? 'yes' : '';
		} );

		$this->assertTrue( ( new SeoPress() )->is_post_noindex( 42 ) );
	}

	public function test_empty_meta_is_undetermined() {
		_set_wp_override( 'get_post_meta', function () {
			return '';
		} );

		$this->assertNull( ( new SeoPress() )->is_post_noindex( 42 ) );
	}
}
