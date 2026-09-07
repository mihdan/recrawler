<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\Yoast;
use PHPUnit\Framework\TestCase;

class YoastTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'yoast', ( new Yoast() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'yoast_robots', [ 'index' => 'noindex', 'follow' => 'follow' ] );
		$this->assertTrue( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'yoast_robots', [ 'index' => 'index', 'follow' => 'follow' ] );
		$this->assertFalse( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_missing_presentation_is_undetermined() {
		_set_seo_stub( 'yoast_robots', null );
		$this->assertNull( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'yoast_robots', 'throw' );
		$this->assertNull( ( new Yoast() )->is_post_noindex( 42 ) );
	}
}
