<?php
/**
 * Block AI crawlers.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers;

use Mihdan\ReCrawler\Enums\BlockAiCrawlerEnum;
use Mihdan\ReCrawler\Views\WPOSA;

class BlockAiCrawler {

	private WPOSA $wposa;

	public function __construct( WPOSA $wposa ) {
		$this->wposa = $wposa;
	}

	public function setup_hooks(): void {
		add_action( 'init', [ $this, 'maybe_block' ] );
		add_action( 'do_robots', [ $this, 'add_robots_rules' ] );
	}

	public function maybe_block(): void {
		$bots = $this->wposa->get_option( 'bots', 'ai', [] );

		if ( empty( $bots ) || ! is_array( $bots ) ) {
			return;
		}

		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $user_agent ) ) {
			return;
		}

		foreach ( BlockAiCrawlerEnum::cases() as $case ) {
			if ( ! isset( $bots[ $case->name ] ) ) {
				continue;
			}

			if ( stripos( $user_agent, $case->value ) !== false ) {
				$this->block();
				return;
			}
		}
	}

	public function add_robots_rules(): void {
		$bots = $this->wposa->get_option( 'bots', 'ai', [] );

		if ( empty( $bots ) || ! is_array( $bots ) ) {
			return;
		}

		foreach ( BlockAiCrawlerEnum::cases() as $case ) {
			if ( ! isset( $bots[ $case->name ] ) ) {
				continue;
			}

			echo "User-agent: {$case->value}\n";
			echo "Disallow: /\n\n";
		}
	}

	private function block(): void {
		status_header( 403 );
		nocache_headers();
		exit;
	}
}
