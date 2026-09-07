<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\Aioseo;
use PHPUnit\Framework\TestCase;

class AioseoTest extends TestCase {

	private function make_post( int $id = 42, string $type = 'post' ): \WP_Post {
		$post            = new \WP_Post();
		$post->ID        = $id;
		$post->post_type = $type;

		return $post;
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		_set_wp_override( 'get_post', function () {
			return $this->make_post();
		} );
	}

	public function test_slug() {
		$this->assertSame( 'aioseo', ( new Aioseo() )->get_slug() );
	}

	public function test_post_level_noindex_wins() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => false, 'robots_noindex' => true ] );
		_set_seo_stub( 'aioseo_post_type_noindex', false );

		$this->assertTrue( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_post_level_index_wins() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => false, 'robots_noindex' => false ] );
		_set_seo_stub( 'aioseo_post_type_noindex', true );

		$this->assertFalse( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_falls_back_to_post_type_setting() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => true, 'robots_noindex' => false ] );
		_set_seo_stub( 'aioseo_post_type_noindex', true );

		$this->assertTrue( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_missing_meta_is_undetermined() {
		_set_seo_stub( 'aioseo_meta', null );

		$this->assertNull( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'aioseo_meta', 'throw' );

		$this->assertNull( ( new Aioseo() )->is_post_noindex( 42 ) );
	}
}
