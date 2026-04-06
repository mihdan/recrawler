<?php
/**
 * IndexNow via Bing.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Bing;

use Mihdan\ReCrawler\WebmasterAbstract;
use Mihdan\ReCrawler\Utils;
use Mihdan\ReCrawler\ActionScheduler;
class BingWebmaster extends WebmasterAbstract {
	private const RECRAWL_ENDPOINT = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=%s';

	public function get_ping_endpoint(): string {
		return self::RECRAWL_ENDPOINT;
	}

	public function get_slug(): string {
		return 'bing-webmaster';
	}

	public function get_name(): string {
		return __( 'Bing Webmaster', 'recrawler' );
	}

	public function get_token(): string {
		return $this->wposa->get_option( 'api_key', 'bing_webmaster' );
	}

	public function is_enabled(): bool {
		return $this->wposa->get_option( 'enable', 'bing_webmaster', 'off' ) === 'on';
	}

	public function setup_hooks() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'recrawler/post_added', [ $this, 'schedule_ping' ] );
		add_action( 'recrawler/post_updated', [ $this, 'schedule_ping' ] );

		// Register async action handler.
		add_action( 'recrawler/webmaster/ping/' . $this->get_slug(), [ $this, 'async_ping_handler' ], 10, 1 );
	}

	/**
	 * Schedule async ping action.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function schedule_ping( int $post_id ) {
		ActionScheduler::async(
			'recrawler/webmaster/ping/' . $this->get_slug(),
			[ 'post_id' => $post_id ]
		);
	}

	/**
	 * Async action handler for ping.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function async_ping_handler( int $post_id ) {
		$this->ping( $post_id );
	}

	/**
	 * Bing Webmaster ping.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @link https://www.bing.com/webmasters/url-submission-api#APIs
	 */
	public function ping( int $post_id ) {
		$url  = sprintf( $this->get_ping_endpoint(), $this->get_token() );
		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				[
					'siteUrl' => get_home_url(),
					'urlList' => [
						get_permalink( $post_id ),
					],
				]
			),
		);

		$response    = wp_remote_post( $url, $args );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		$data = [
			'status_code'   => $status_code,
			'search_engine' => $this->get_slug(),
		];

		if ( Utils::is_response_code_success( $status_code ) ) {
			$message = sprintf( '<a href="%s" target="_blank">%s</a> - OK', get_permalink( $post_id ), get_the_title( $post_id ) );
			$this->logger->info( $message, $data );
		} else {
			$this->logger->error( $body['Message'], $data );
		}
	}

	public function get_quota(): array {
		// TODO: Implement get_quota() method.
		return [];
	}
}
