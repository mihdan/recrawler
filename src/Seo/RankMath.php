<?php
/**
 * Rank Math noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use RankMath\Post as RankMathPost;
use Throwable;

class RankMath implements SeoPluginInterface {

	public function get_slug(): string {
		return 'rank-math';
	}

	public function is_active(): bool {
		return class_exists( RankMathPost::class );
	}

	/**
	 * Rank Math keeps robots directives as an array in post meta. An empty
	 * array means the post defers to the global settings, which this detector
	 * does not read — hence undetermined rather than indexable.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$robots = RankMathPost::get_meta( 'robots', $post_id );

			if ( ! is_array( $robots ) || [] === $robots ) {
				return null;
			}

			return in_array( 'noindex', $robots, true );
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
