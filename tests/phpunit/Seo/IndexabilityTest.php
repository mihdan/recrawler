<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Seo\Indexability;
use Mihdan\ReCrawler\Seo\SeoPluginInterface;
use PHPUnit\Framework\TestCase;

class IndexabilityTest extends TestCase {

	private $logger;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		$this->logger = $this->createMock( Logger::class );
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 1 : $default;
		} );
	}

	/**
	 * @param bool|null $answer What is_post_noindex() returns.
	 */
	private function detector( string $slug, bool $active, $answer, bool $throws = false ): SeoPluginInterface {
		return new class( $slug, $active, $answer, $throws ) implements SeoPluginInterface {
			private $slug;
			private $active;
			private $answer;
			private $throws;
			public $asked = false;

			public function __construct( $slug, $active, $answer, $throws ) {
				$this->slug   = $slug;
				$this->active = $active;
				$this->answer = $answer;
				$this->throws = $throws;
			}

			public function get_slug(): string {
				return $this->slug;
			}

			public function is_active(): bool {
				return $this->active;
			}

			public function is_post_noindex( int $post_id ): ?bool {
				$this->asked = true;

				if ( $this->throws ) {
					throw new \RuntimeException( 'detector exploded' );
				}

				return $this->answer;
			}
		};
	}

	public function test_closed_site_is_not_indexable_and_detectors_are_not_asked() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 0 : $default;
		} );

		$detector = $this->detector( 'yoast', true, false );

		$this->assertFalse( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $detector->asked );
	}

	public function test_first_definite_answer_stops_the_walk() {
		$first  = $this->detector( 'yoast', true, true );
		$second = $this->detector( 'rank-math', true, false );

		$this->assertFalse( ( new Indexability( $this->logger, [ $first, $second ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $second->asked );
	}

	public function test_undetermined_detector_defers_to_the_next_one() {
		$first  = $this->detector( 'yoast', true, null );
		$second = $this->detector( 'rank-math', true, true );

		$this->assertFalse( ( new Indexability( $this->logger, [ $first, $second ] ) )->is_post_indexable( 42 ) );
		$this->assertTrue( $second->asked );
	}

	public function test_inactive_detector_is_skipped() {
		$inactive = $this->detector( 'yoast', false, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $inactive ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $inactive->asked );
	}

	public function test_empty_list_fails_open() {
		$this->assertTrue( ( new Indexability( $this->logger, [] ) )->is_post_indexable( 42 ) );
	}

	public function test_throwing_detector_fails_open() {
		$detector = $this->detector( 'yoast', true, null, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
	}

	public function test_filter_can_override_noindex() {
		_set_wp_override( 'apply_filters', function ( $tag, $value, ...$args ) {
			return 'recrawler/is_post_indexable' === $tag ? true : $value;
		} );

		$detector = $this->detector( 'yoast', true, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
	}

	public function test_filter_can_override_closed_site() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 0 : $default;
		} );
		_set_wp_override( 'apply_filters', function ( $tag, $value, ...$args ) {
			return 'recrawler/is_post_indexable' === $tag ? true : $value;
		} );

		$this->assertTrue( ( new Indexability( $this->logger, [] ) )->is_post_indexable( 42 ) );
	}

	public function test_skip_is_logged_with_the_reason() {
		$this->logger->expects( $this->once() )
			->method( 'debug' )
			->with( $this->stringContains( 'yoast' ), $this->anything() );

		$detector = $this->detector( 'yoast', true, true );

		( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 );
	}

	public function test_indexable_post_is_not_logged() {
		$this->logger->expects( $this->never() )->method( 'debug' );

		$detector = $this->detector( 'yoast', true, false );

		( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 );
	}

	public function test_is_site_public_returns_true_when_blog_public_is_on() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 1 : $default;
		} );

		$this->assertTrue( ( new Indexability( $this->logger, [] ) )->is_site_public() );
	}

	public function test_is_site_public_returns_false_when_blog_public_is_off() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 0 : $default;
		} );

		$this->assertFalse( ( new Indexability( $this->logger, [] ) )->is_site_public() );
	}
}
