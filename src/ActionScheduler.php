<?php
/**
 * ActionScheduler wrapper class.
 *
 * Provides a centralized interface for scheduling async actions
 * via the ActionScheduler library.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler;

/**
 * Class ActionScheduler.
 *
 * Handles async task scheduling via ActionScheduler.
 */
class ActionScheduler {

	/**
	 * ActionScheduler group name.
	 */
	public const GROUP = 'recrawler';

	/**
	 * Schedule an async action to be run as soon as possible.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Action arguments.
	 * @param string $group  Action group.
	 *
	 * @return int|false Action ID on success, false on failure.
	 */
	public static function async( string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		return as_enqueue_async_action( $action, $args, $group, true );
	}

	/**
	 * Schedule a single action to run at a specific time.
	 *
	 * @param int    $timestamp Unix timestamp (UTC) for when the action should run.
	 * @param string $action    Action name.
	 * @param array  $args      Action arguments.
	 * @param string $group     Action group.
	 *
	 * @return int|false Action ID on success, false on failure.
	 */
	public static function single( int $timestamp, string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return false;
		}

		return as_schedule_single_action( $timestamp, $action, $args, $group );
	}

	/**
	 * Schedule a recurring action.
	 *
	 * @param int    $timestamp  Unix timestamp (UTC) for when the first action should run.
	 * @param int    $interval_in_seconds Interval in seconds between runs.
	 * @param string $action               Action name.
	 * @param array  $args                 Action arguments.
	 * @param string $group                Action group.
	 *
	 * @return int|false Action ID on success, false on failure.
	 */
	public static function recurring( int $timestamp, int $interval_in_seconds, string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return false;
		}

		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $action, $args, $group );
	}

	/**
	 * Schedule a cron action with WP-cron-like syntax.
	 *
	 * @param int    $timestamp    Unix timestamp (UTC) for when the first action should run.
	 * @param string $cron_schedule Cron schedule string (e.g., 'hourly', 'daily').
	 * @param string $action        Action name.
	 * @param array  $args          Action arguments.
	 * @param string $group         Action group.
	 *
	 * @return int|false Action ID on success, false on failure.
	 */
	public static function cron( int $timestamp, string $cron_schedule, string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_schedule_cron_action' ) ) {
			return false;
		}

		return as_schedule_cron_action( $timestamp, $cron_schedule, $action, $args, $group );
	}

	/**
	 * Cancel all actions with a specific action name.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Optional args to match.
	 * @param string $group  Optional group to match.
	 *
	 * @return void
	 */
	public static function cancel( string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		as_unschedule_all_actions( $action, $args, $group );
	}

	/**
	 * Cancel all pending actions with a specific action name.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Optional args to match.
	 * @param string $group  Optional group to match.
	 *
	 * @return void
	 */
	public static function cancel_pending( string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_unschedule_pending_actions' ) ) {
			return;
		}

		as_unschedule_pending_actions( $action, $args, $group );
	}

	/**
	 * Check if an action is already scheduled.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Action arguments.
	 * @param string $group  Action group.
	 *
	 * @return bool True if scheduled, false otherwise.
	 */
	public static function is_scheduled( string $action, array $args = [], string $group = self::GROUP ): bool {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return false;
		}

		return as_has_scheduled_action( $action, $args, $group );
	}

	/**
	 * Get the next scheduled run time for an action.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Action arguments.
	 * @param string $group  Action group.
	 *
	 * @return int|false Unix timestamp (UTC) or false if not found.
	 */
	public static function next( string $action, array $args = [], string $group = self::GROUP ) {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return false;
		}

		return as_next_scheduled_action( $action, $args, $group );
	}
}
