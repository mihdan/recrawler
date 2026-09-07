<?php
/**
 * Yoast SEO noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class Yoast implements SeoPluginInterface {

	public function get_slug(): string {
		return 'yoast';
	}

	public function is_active(): bool {
		return function_exists( 'YoastSEO' );
	}

	/**
	 * Read the robots array off the Surfaces API (Yoast 14+). It accounts for
	 * both the post meta and the post type wide noindex from Yoast settings.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$presentation = YoastSEO()->meta->for_post( $post_id );

			if ( ! is_object( $presentation ) || ! isset( $presentation->robots['index'] ) ) {
				return null;
			}

			return 'noindex' === $presentation->robots['index'];
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
