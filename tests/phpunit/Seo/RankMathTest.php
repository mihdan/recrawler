<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\RankMath;
use PHPUnit\Framework\TestCase;

class RankMathTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'rank-math', ( new RankMath() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'rankmath_robots', [ 'noindex', 'nofollow' ] );
		$this->assertTrue( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'rankmath_robots', [ 'index', 'follow' ] );
		$this->assertFalse( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_empty_meta_is_undetermined() {
		_set_seo_stub( 'rankmath_robots', [] );
		$this->assertNull( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'rankmath_robots', 'throw' );
		$this->assertNull( ( new RankMath() )->is_post_noindex( 42 ) );
	}
}
