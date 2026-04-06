<?php
/**
 * Main class.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Providers\Yandex;

use Mihdan\ReCrawler\Utils;
use Mihdan\ReCrawler\WebmasterAbstract;

class YandexWebmaster extends WebmasterAbstract {

	private const USER_ENDPOINT    = 'https://api.webmaster.yandex.net/v4/user/';
	private const TOKEN_ENDPOINT   = 'https://oauth.yandex.ru/token';
	private const HOSTS_ENDPOINT   = 'https://api.webmaster.yandex.net/v4/user/%d/hosts';
	private const RECRAWL_ENDPOINT = 'https://api.webmaster.yandex.net/v4/user/%s/hosts/%s/recrawl/queue';
	private const QUOTA_ENDPOINT   = 'https://api.webmaster.yandex.net/v4/user/%s/hosts/%s/recrawl/quota';

	public const CRON_EVENT_NAME = 'recrawler__yandex_webmaster_refresh_token';

	public function get_slug(): string {
		return 'yandex-webmaster';
	}

	public function get_name(): string {
		return __( 'Yandex Webmaster', 'recrawler' );
	}

	public function get_token(): string {
		return $this->wposa->get_option( 'access_token', 'yandex_webmaster' );
	}

	public function get_user_id(): string {
		return $this->wposa->get_option( 'user_id', 'yandex_webmaster' );
	}

	public function get_host_id(): string {
		return $this->wposa->get_option( 'host_id', 'yandex_webmaster' );
	}

	public function get_client_id(): string {
		return $this->wposa->get_option( 'client_id', 'yandex_webmaster' );
	}

	public function get_client_secret(): string {
		return $this->wposa->get_option( 'client_secret', 'yandex_webmaster' );
	}

	public function get_ping_endpoint(): string {
		return self::RECRAWL_ENDPOINT;
	}

	public function get_quota_endpoint(): string {
		return self::QUOTA_ENDPOINT;
	}

	public function is_enabled(): bool {
		return $this->wposa->get_option( 'enable', 'yandex_webmaster', 'off' ) === 'on';
	}

	public function setup_hooks() {

		add_action( 'admin_init', [ $this, 'get_api_token' ] );
		add_action( 'admin_init', [ $this, 'schedule_token_refresh' ] );
		add_action( self::CRON_EVENT_NAME, [ $this, 'refresh_token_cron' ] );
		add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

		if ( ! $this->is_enabled() ) {
			return;
		}

		//$this->get_quota();
		add_action( 'recrawler/post_added', [ $this, 'ping' ] );
		add_action( 'recrawler/post_updated', [ $this, 'ping' ] );
	}

	/**
	 * Add custom cron schedules for token refresh.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public function add_cron_schedules( $schedules ) {
		for ( $i = 1; $i <= 6; $i++ ) {
			$schedules[ 'recrawler_' . $i . 'months' ] = array(
				'interval' => $i * MONTH_IN_SECONDS,
				'display'  => sprintf( __( 'Every %d month(s)', 'recrawler' ), $i ),
			);
		}
		return $schedules;
	}

	public function get_api_token() {

		if ( isset( $_GET['code'], $_GET['state'] ) && $_GET['state'] === $this->get_slug() ) {
			$data = [];
			$data['body'] = [
				'grant_type'    => 'authorization_code',
				'code'          => wp_unslash( sanitize_text_field( $_GET['code'] ) ),
				'client_id'     => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
			];

			$response    = wp_remote_post( self::TOKEN_ENDPOINT, $data );
			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $status_code !== 200 ) {
				$this->logger->error( $body['error_description'], [ 'search_engine' => $this->get_slug(), 'status_code' => $status_code ] );
				return;
			}

			$this->wposa->set_option( 'access_token', $body['access_token'], 'yandex_webmaster' );
			$this->wposa->set_option( 'refresh_token', $body['refresh_token'], 'yandex_webmaster' );
			$this->wposa->set_option( 'expires_in', $body['expires_in'] + current_time( 'timestamp' ), 'yandex_webmaster' );

			$user_id = $this->get_api_user_id( $body['access_token'] );

			if ( $user_id ) {
				$this->wposa->set_option( 'user_id', $user_id, 'yandex_webmaster' );

				$host_ids = $this->get_api_host_id( $user_id, $body['access_token'] );

				if ( $host_ids ) {
					$this->wposa->set_option( 'host_ids', serialize( $host_ids ), 'yandex_webmaster' );
				}
			}

			wp_safe_redirect(
				add_query_arg(
					'page',
					Utils::get_plugin_slug(),
					admin_url( 'admin.php' )
				)
			);
		}
	}

	/**
	 * Get user ID.
	 *
	 * @param string $token Access token.
	 *
	 * @return int
	 */
	public function get_api_user_id( string $token ): int {
		$args = [
			'headers' => [
				'Authorization' => 'OAuth ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 30,
		];

		$response    = wp_remote_get( self::USER_ENDPOINT, $args );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$this->logger->error( $body['error_message'], [ 'search_engine' => $this->get_slug(), 'status_code' => $status_code ] );
			return 0;
		}

		return $body['user_id'] ?? 0;
	}

	/**
	 * Get user ID.
	 *
	 * @param int    $user_id User ID.
	 * @param string $token   Access token.
	 *
	 * @return int
	 */
	public function get_api_host_id( int $user_id, string $token ): array {
		$args = [
			'headers' => [
				'Authorization' => 'OAuth ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 30,
		];

		$response    = wp_remote_get( sprintf( self::HOSTS_ENDPOINT, $user_id ), $args );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$this->logger->error( $body['error_message'], [ 'search_engine' => $this->get_slug(), 'status_code' => $status_code ] );
			return 0;
		}

		return isset( $body['hosts'] )
			? wp_list_pluck( $body['hosts'], 'host_id' )
			: [];
	}

	/**
	 * Yandex Webmaster ping.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @link https://yandex.com/dev/webmaster/doc/dg/reference/host-recrawl-post.html
	 */
	public function ping( int $post_id ) {

		$url = sprintf( $this->get_ping_endpoint(), $this->get_user_id(), $this->get_host_id() );

		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'OAuth ' . $this->get_token(),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'url' => get_permalink( $post_id ),
				)
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
			$this->logger->error( $body['error_message'], $data );
		}
	}

	public function get_quota(): array {
		$url = sprintf( $this->get_quota_endpoint(), $this->get_user_id(), $this->get_host_id() );

		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'OAuth ' . $this->get_token(),
				'Content-Type'  => 'application/json',
			),
		);

		$response    = wp_remote_get( $url, $args );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		$data = [
			'status_code'   => $status_code,
			'search_engine' => $this->get_slug(),
		];

		if ( Utils::is_response_code_success( $status_code ) ) {
			$message = 'Data on daily limit successfully received';
			$this->logger->info( $message, $data );

			return $body;
		} else {
			$this->logger->error( $body['error_message'], $data );

			return [
				'daily_quota'     => 0,
				'quota_remainder' => 0,
			];
		}
	}

	/**
	 * Schedule cron job for automatic token refresh.
	 */
	public function schedule_token_refresh() {
		$refresh_token = $this->wposa->get_option( 'refresh_token', 'yandex_webmaster' );

		if ( ! $refresh_token ) {
			// Clear scheduled job if no refresh token
			$timestamp = wp_next_scheduled( self::CRON_EVENT_NAME );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_EVENT_NAME );
			}
			return;
		}

		$refresh_period_months = (int) $this->wposa->get_option( 'token_refresh_period', 'yandex_webmaster', 5 );
		$cron_schedule_name = 'recrawler_' . $refresh_period_months . 'months';

		// Check if we need to reschedule due to period change
		$next_scheduled = wp_next_scheduled( self::CRON_EVENT_NAME );

		if ( $next_scheduled ) {
			// Unschedule existing event to apply new period
			wp_unschedule_event( $next_scheduled, self::CRON_EVENT_NAME );
		}

		// Schedule with new period
		wp_schedule_event( time(), $cron_schedule_name, self::CRON_EVENT_NAME );

		$this->logger->info(
			sprintf(
				__( 'Yandex Webmaster token refresh scheduled every %d months', 'recrawler' ),
				$refresh_period_months
			),
			[ 'search_engine' => $this->get_slug() ]
		);
	}

	/**
	 * Refresh token via cron job.
	 */
	public function refresh_token_cron() {
		$refresh_token = $this->wposa->get_option( 'refresh_token', 'yandex_webmaster' );
		$client_id = $this->get_client_id();
		$client_secret = $this->get_client_secret();

		if ( ! $refresh_token || ! $client_id || ! $client_secret ) {
			$this->logger->error(
				__( 'Cannot refresh Yandex Webmaster token: missing credentials', 'recrawler' ),
				[ 'search_engine' => $this->get_slug() ]
			);
			return;
		}

		$data = [
			'body' => [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
			],
		];

		$response    = wp_remote_post( self::TOKEN_ENDPOINT, $data );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		$log_data = [ 'search_engine' => $this->get_slug(), 'status_code' => $status_code ];

		if ( $status_code !== 200 ) {
			$message = isset( $body['error_description'] ) ? $body['error_description'] : 'Unknown error';
			$this->logger->error( $message, $log_data );
			return;
		}

		$this->wposa->set_option( 'access_token', $body['access_token'], 'yandex_webmaster' );
		$this->wposa->set_option( 'refresh_token', $body['refresh_token'], 'yandex_webmaster' );
		$this->wposa->set_option( 'expires_in', $body['expires_in'] + current_time( 'timestamp' ), 'yandex_webmaster' );

		$user_id = $this->get_api_user_id( $body['access_token'] );

		if ( $user_id ) {
			$this->wposa->set_option( 'user_id', $user_id, 'yandex_webmaster' );

			$host_ids = $this->get_api_host_id( $user_id, $body['access_token'] );

			if ( $host_ids ) {
				$this->wposa->set_option( 'host_ids', serialize( $host_ids ), 'yandex_webmaster' );
			}
		}

		$this->logger->info(
			__( 'Yandex Webmaster token refreshed successfully', 'recrawler' ),
			$log_data
		);
	}
}
