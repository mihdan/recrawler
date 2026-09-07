<?php
/**
 * All in One SEO noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class Aioseo implements SeoPluginInterface {

	public function get_slug(): string {
		return 'aioseo';
	}

	public function is_active(): bool {
		return function_exists( 'aioseo' );
	}

	/**
	 * Mirrors AIOSEO's own resolution order: the post's own robots meta wins
	 * unless it defers to defaults, in which case the post type setting decides.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$post = get_post( $post_id );

			if ( ! $post ) {
				return null;
			}

			$meta = aioseo()->meta->metaData->getMetaData( $post );

			if ( ! is_object( $meta ) ) {
				return null;
			}

			if ( empty( $meta->robots_default ) ) {
				return (bool) ( $meta->robots_noindex ?? false );
			}

			return $this->is_post_type_noindex( $post->post_type );
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * A post type can be registered without an `advanced` group, so every hop
	 * is guarded the same way AIOSEO guards it in Meta\Robots::globalValues().
	 */
	private function is_post_type_noindex( string $post_type ): ?bool {
		$options = aioseo()->dynamicOptions->noConflict( true )->searchAppearance;

		if ( ! $options->has( 'postTypes', false ) ) {
			return null;
		}

		$options = $options->postTypes;

		if ( ! $options->has( $post_type, false ) ) {
			return null;
		}

		$options = $options->{$post_type};

		if ( ! $options->has( 'advanced', false ) ) {
			return null;
		}

		$options = $options->advanced;

		if ( ! $options->has( 'robotsMeta', false ) ) {
			return null;
		}

		$robots_meta = $options->robotsMeta->all();

		// A post type set to defaults defers to the plugin's global robots meta,
		// which has no stable public API — better undetermined than wrong.
		if ( ! is_array( $robots_meta ) || ! empty( $robots_meta['default'] ) ) {
			return null;
		}

		return ! empty( $robots_meta['noindex'] );
	}
}
