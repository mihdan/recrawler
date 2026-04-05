<?php
/**
 * WebSub (PubSubHubbub) publisher.
 *
 * Implements the W3C WebSub protocol for real-time content distribution.
 *
 * @link https://www.w3.org/TR/websub/
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\WebSub;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\SearchEngineInterface;
use Mihdan\ReCrawler\Utils;
use Mihdan\ReCrawler\Views\WPOSA;
use WP_Post;

class WebSub implements SearchEngineInterface {

	/**
	 * Default hub endpoints.
	 */
	const DEFAULT_HUBS = [
		'https://pubsubhubbub.appspot.com/',
		'https://pubsubhubbub.superfeedr.com/',
	];

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * WPOSA instance.
	 *
	 * @var WPOSA
	 */
	private WPOSA $wposa;

	/**
	 * WebSub constructor.
	 *
	 * @param Logger $logger Logger instance.
	 * @param WPOSA  $wposa  WPOSA instance.
	 */
	public function __construct( Logger $logger, WPOSA $wposa ) {
		$this->logger = $logger;
		$this->wposa  = $wposa;
	}

	/**
	 * Get provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'websub';
	}

	/**
	 * Get provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'WebSub', 'recrawler' );
	}

	/**
	 * Check if provider is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->wposa->get_option( 'enable', 'websub', 'off' ) === 'on';
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'recrawler/post_added', [ $this, 'publish' ], 10, 2 );
		add_action( 'recrawler/post_updated', [ $this, 'publish' ], 10, 2 );

		add_action( 'atom_head', [ $this, 'add_atom_links' ] );
		add_action( 'rss2_head', [ $this, 'add_rss2_links' ] );
		add_action( 'rdf_header', [ $this, 'add_rdf_links' ] );

		add_action( 'wp_head', [ $this, 'add_html_head_links' ] );

		add_filter( 'rss2_ns', [ $this, 'add_atom_namespace' ] );
		add_filter( 'rdf_ns', [ $this, 'add_atom_namespace' ] );
	}

	/**
	 * Publish content update notification to all configured hubs.
	 *
	 * Per the WebSub spec, the publisher notifies the hub about its topic feed URLs.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function publish( int $post_id, WP_Post $post ): void {
		$hubs       = $this->get_hubs();
		$topic_urls = $this->get_topic_urls( $post_id );

		foreach ( $hubs as $hub_url ) {
			foreach ( $topic_urls as $topic_url ) {
				$this->ping_hub( $hub_url, $topic_url );
			}
		}
	}

	/**
	 * Send a WebSub publish notification to a single hub.
	 *
	 * @param string $hub_url   Hub endpoint URL.
	 * @param string $topic_url Topic (feed) URL to notify about.
	 *
	 * @return void
	 */
	private function ping_hub( string $hub_url, string $topic_url ): void {
		$response = wp_remote_post(
			$hub_url,
			[
				'timeout' => 30,
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
				],
				'body'    => [
					'hub.mode' => 'publish',
					'hub.url'  => $topic_url,
				],
			]
		);

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		$log_data = [
			'status_code'   => $status_code,
			'search_engine' => $this->get_slug(),
		];

		if ( Utils::is_response_code_success( $status_code ) ) {
			$message = sprintf(
				'<a href="%1$s" target="_blank">%1$s</a> → <a href="%2$s" target="_blank">%2$s</a> - OK',
				esc_url( $topic_url ),
				esc_url( $hub_url )
			);
			$this->logger->info( $message, $log_data );
		} else {
			$error = $body ?: wp_remote_retrieve_response_message( $response );
			$this->logger->error( $error, $log_data );
		}
	}

	/**
	 * Get configured hub URLs.
	 *
	 * Falls back to default hubs if none are configured.
	 *
	 * @return string[]
	 */
	public function get_hubs(): array {
		$raw = trim( (string) $this->wposa->get_option( 'hubs', 'websub', '' ) );

		if ( empty( $raw ) ) {
			return apply_filters( 'recrawler/websub/hubs', self::DEFAULT_HUBS );
		}

		$hubs = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );

		return apply_filters( 'recrawler/websub/hubs', array_values( $hubs ) );
	}

	/**
	 * Get topic URLs to notify about.
	 *
	 * WebSub publishers typically notify about feed URLs since subscribers
	 * subscribe to feeds, not individual post URLs.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string[]
	 */
	private function get_topic_urls( int $post_id ): array {
		$urls = [
			get_bloginfo( 'rss2_url' ),
			get_bloginfo( 'atom_url' ),
		];

		return apply_filters( 'recrawler/websub/topic_urls', array_unique( array_filter( $urls ) ), $post_id );
	}

	/**
	 * Add hub and self link elements to Atom feed head.
	 *
	 * @return void
	 */
	public function add_atom_links(): void {
		foreach ( $this->get_hubs() as $hub_url ) {
			printf( "\t<link rel=\"hub\" href=\"%s\" />\n", esc_url( $hub_url ) );
		}
		printf( "\t<link rel=\"self\" href=\"%s\" />\n", esc_url( get_bloginfo( 'atom_url' ) ) );
	}

	/**
	 * Add hub and self atom:link elements to RSS 2.0 feed head.
	 *
	 * @return void
	 */
	public function add_rss2_links(): void {
		foreach ( $this->get_hubs() as $hub_url ) {
			printf( "\t<atom:link rel=\"hub\" href=\"%s\" />\n", esc_url( $hub_url ) );
		}
		printf( "\t<atom:link rel=\"self\" href=\"%s\" />\n", esc_url( get_bloginfo( 'rss2_url' ) ) );
	}

	/**
	 * Add hub atom:link elements to RDF/RSS 1.0 feed head.
	 *
	 * @return void
	 */
	public function add_rdf_links(): void {
		foreach ( $this->get_hubs() as $hub_url ) {
			printf( "\t<atom:link rel=\"hub\" href=\"%s\" />\n", esc_url( $hub_url ) );
		}
	}

	/**
	 * Add hub link tags to HTML <head>.
	 *
	 * Allows browsers and subscribers to auto-discover WebSub hubs.
	 *
	 * @return void
	 */
	public function add_html_head_links(): void {
		foreach ( $this->get_hubs() as $hub_url ) {
			printf( "<link rel=\"hub\" href=\"%s\" />\n", esc_url( $hub_url ) );
		}
	}

	/**
	 * Add Atom XML namespace to RSS 2.0 and RDF feeds.
	 *
	 * Required for atom:link elements to be valid.
	 *
	 * @param string $ns Existing namespace declarations.
	 *
	 * @return string
	 */
	public function add_atom_namespace( string $ns ): string {
		return $ns . 'xmlns:atom="http://www.w3.org/2005/Atom" ';
	}
}
