<?php
/**
 * SEO plugin detector interface.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

interface SeoPluginInterface {
	/**
	 * Slug used to report which plugin closed the post.
	 */
	public function get_slug(): string;

	/**
	 * Whether the plugin is installed and active.
	 */
	public function is_active(): bool;

	/**
	 * Whether the post is closed from indexing.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool|null True when noindex, false when indexable, null when undetermined.
	 */
	public function is_post_noindex( int $post_id ): ?bool;
}
