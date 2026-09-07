<?php
/**
 * SEOPress noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class SeoPress implements SeoPluginInterface {

	public function get_slug(): string {
		return 'seopress';
	}

	public function is_active(): bool {
		return defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * SEOPress stores `yes` only when noindex is set explicitly on the post.
	 * An empty value means the post inherits the plugin's global settings,
	 * which have no public API — so it stays undetermined.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$value = get_post_meta( $post_id, '_seopress_robots_index', true );

			return 'yes' === $value ? true : null;
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
