<?php
/**
 * Decides whether a post may be submitted for recrawl.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Mihdan\ReCrawler\Logger\Logger;
use Throwable;

class Indexability {

	/**
	 * Logger instance.
	 *
	 * @var Logger $logger
	 */
	private Logger $logger;

	/**
	 * Detectors in the order they are asked.
	 *
	 * @var SeoPluginInterface[] $detectors
	 */
	private array $detectors;

	/**
	 * Constructor.
	 *
	 * @param Logger                    $logger    Logger instance.
	 * @param SeoPluginInterface[]|null $detectors Detectors to ask. Null means the default set.
	 */
	public function __construct( Logger $logger, ?array $detectors = null ) {
		$this->logger    = $logger;
		$this->detectors = $detectors ?? [
			new Yoast(),
			new RankMath(),
			new Aioseo(),
			new SeoPress(),
			new TheSeoFramework(),
		];
	}

	/**
	 * Whether the post may be submitted for recrawl.
	 *
	 * Fails open: when nothing can tell, the post is treated as indexable —
	 * a broken third-party API must not stop the plugin from doing its job.
	 *
	 * @param int $post_id Post ID.
	 */
	public function is_post_indexable( int $post_id ): bool {
		$indexable = true;
		$reason    = '';

		if ( ! $this->is_site_public() ) {
			$indexable = false;
			$reason    = 'blog_public';
		} else {
			foreach ( $this->detectors as $detector ) {
				if ( ! $detector->is_active() ) {
					continue;
				}

				try {
					$noindex = $detector->is_post_noindex( $post_id );
				} catch ( Throwable $e ) {
					continue;
				}

				if ( null === $noindex ) {
					continue;
				}

				if ( $noindex ) {
					$indexable = false;
					$reason    = $detector->get_slug();
				}

				break;
			}
		}

		if ( ! $indexable ) {
			$this->logger->debug(
				sprintf( 'Post %d skipped: closed from indexing (%s)', $post_id, $reason ),
				[
					'search_engine' => 'site',
					'status_code'   => 0,
				]
			);
		}

		return (bool) apply_filters( 'recrawler/is_post_indexable', $indexable, $post_id );
	}

	/**
	 * Whether the whole site is open for search engines.
	 *
	 * The `recrawler/is_post_indexable` filter is not applied here — its
	 * contract is `( bool $indexable, int $post_id )`, and a term has no
	 * post ID to pass.
	 */
	public function is_site_public(): bool {
		return (bool) get_option( 'blog_public' );
	}
}
