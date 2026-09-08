<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\TheSeoFramework;
use PHPUnit\Framework\TestCase;

class TheSeoFrameworkTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'the-seo-framework', ( new TheSeoFramework() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'tsf_meta', [ 'noindex' => 'noindex' ] );
		$this->assertTrue( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'tsf_meta', [ 'noindex' => '' ] );
		$this->assertFalse( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_missing_key_is_undetermined() {
		_set_seo_stub( 'tsf_meta', [] );
		$this->assertNull( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'tsf_meta', 'throw' );
		$this->assertNull( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}
}
