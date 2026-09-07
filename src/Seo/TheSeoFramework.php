<?php
/**
 * The SEO Framework noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use The_SEO_Framework\Meta\Robots as TsfRobots;
use Throwable;

class TheSeoFramework implements SeoPluginInterface {

	public function get_slug(): string {
		return 'the-seo-framework';
	}

	public function is_active(): bool {
		return class_exists( TsfRobots::class );
	}

	/**
	 * TSF converts a truthy directive into its own name, so the `noindex` key
	 * holds either the string `noindex` or an empty value.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$meta = TsfRobots::get_generated_meta( [ 'id' => $post_id ], [ 'noindex' ] );

			if ( ! is_array( $meta ) || ! array_key_exists( 'noindex', $meta ) ) {
				return null;
			}

			return 'noindex' === $meta['noindex'];
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
