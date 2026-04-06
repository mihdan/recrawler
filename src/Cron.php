<?php
namespace Mihdan\ReCrawler;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Views\WPOSA;
use Mihdan\ReCrawler\ActionScheduler;

class Cron {
	public const ACTION_NAME = 'recrawler/cron/clear_log';

	/**
	 * Logger instance.
	 *
	 * @var Logger $logger
	 */
	private $logger;

	/**
	 * WPOSA instance.
	 *
	 * @var WPOSA $wposa
	 */
	private $wposa;

	/**
	 * Cron constructor.
	 *
	 * @param Logger $logger Logger instance.
	 * @param WPOSA  $wposa
	 */
	public function __construct( Logger $logger, WPOSA $wposa ) {
		$this->logger = $logger;
		$this->wposa  = $wposa;
	}

	public function setup_hooks() {
		add_action( 'admin_init', [ $this, 'schedule_task' ] );
		add_action( self::ACTION_NAME, [ $this, 'clear_log' ] );
	}

	/**
	 * Schedule log cleanup via ActionScheduler.
	 *
	 * @return void
	 */
	public function schedule_task() {
		// Check if already scheduled.
		$next_scheduled = ActionScheduler::next( self::ACTION_NAME );

		if ( ! $next_scheduled ) {
			ActionScheduler::recurring(
				time(),
				HOUR_IN_SECONDS,
				self::ACTION_NAME
			);
		}
	}

	/**
	 * Clear log table.
	 *
	 * @return bool
	 */
	public function clear_log(): bool {
		global $wpdb;

		$lifetime   = $this->wposa->get_option( 'lifetime', 'logs', 1 );
		$table_name = $this->logger->get_logger_table_name();

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table_name} WHERE DATEDIFF(NOW(), created_at)>=%d", $lifetime )
		);

		if ( $this->wposa->get_option( 'cron_events', 'logs', 'off' ) === 'on' ) {
			$data = [
				'direction' => 'internal',
			];

			$this->logger->info( 'Old log entries were deleted successfully.', $data );
		}

		return true;
	}
}
