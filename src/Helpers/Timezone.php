<?php
/**
 * Timezone Helper Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Timezone Helper Class
 */
class Timezone {

	/**
	 * Get available timezones.
	 *
	 * @return array Array of timezone groups and zones.
	 */
	public static function get_available_timezones() {
		return array(
			'US Timezones' => array(
				'America/New_York'     => __( 'Eastern Time (EST/EDT)', 'graylog-search' ),
				'America/Chicago'      => __( 'Central Time (CST/CDT)', 'graylog-search' ),
				'America/Denver'       => __( 'Mountain Time (MST/MDT)', 'graylog-search' ),
				'America/Phoenix'      => __( 'Arizona Time (MST - No DST)', 'graylog-search' ),
				'America/Los_Angeles'  => __( 'Pacific Time (PST/PDT)', 'graylog-search' ),
				'America/Anchorage'    => __( 'Alaska Time (AKST/AKDT)', 'graylog-search' ),
				'Pacific/Honolulu'     => __( 'Hawaii Time (HST)', 'graylog-search' ),
			),
			'UTC/GMT'      => array(
				'UTC' => __( 'UTC / GMT / Zulu Time', 'graylog-search' ),
			),
			'India'        => array(
				'Asia/Kolkata' => __( 'India Standard Time (IST)', 'graylog-search' ),
			),
		);
	}

	/**
	 * Get user's saved timezone preference.
	 *
	 * @param int $user_id Optional user ID. Default current user.
	 * @return string Timezone string.
	 */
	public static function get_user_timezone( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$timezone = get_user_meta( $user_id, 'graylog_timezone', true );

		// Default to UTC if not set.
		if ( empty( $timezone ) ) {
			$timezone = 'UTC';
		}

		return $timezone;
	}

	/**
	 * Save user's timezone preference.
	 *
	 * @param string $timezone Timezone string.
	 * @param int    $user_id Optional user ID. Default current user.
	 * @return bool True on success.
	 */
	public static function save_user_timezone( $timezone, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Validate timezone.
		if ( ! self::is_valid_timezone( $timezone ) ) {
			return false;
		}

		return update_user_meta( $user_id, 'graylog_timezone', $timezone );
	}

	/**
	 * Validate if timezone is in available list.
	 *
	 * @param string $timezone Timezone string to validate.
	 * @return bool True if valid.
	 */
	public static function is_valid_timezone( $timezone ) {
		$available_timezones = self::get_available_timezones();

		foreach ( $available_timezones as $group => $zones ) {
			if ( array_key_exists( $timezone, $zones ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Initialize AJAX handlers.
	 */
	public static function init_ajax_handlers() {
		add_action( 'wp_ajax_graylog_get_timezone', array( __CLASS__, 'ajax_get_timezone' ) );
		add_action( 'wp_ajax_graylog_save_timezone', array( __CLASS__, 'ajax_save_timezone' ) );
	}

	/**
	 * AJAX handler to get user's saved timezone preference.
	 */
	public static function ajax_get_timezone() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$timezone = self::get_user_timezone();

		wp_send_json_success( array( 'timezone' => $timezone ) );
	}

	/**
	 * AJAX handler to save user's timezone preference.
	 */
	public static function ajax_save_timezone() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$timezone = Security::sanitize_text( Security::get_post( 'timezone', 'UTC' ) );

		if ( ! self::is_valid_timezone( $timezone ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid timezone.', 'graylog-search' ),
				)
			);
		}

		$success = self::save_user_timezone( $timezone );

		if ( $success ) {
			wp_send_json_success(
				array(
					'message'  => esc_html__( 'Timezone saved.', 'graylog-search' ),
					'timezone' => $timezone,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to save timezone.', 'graylog-search' ),
				)
			);
		}
	}
}

// Initialize timezone AJAX handlers.
Timezone::init_ajax_handlers();
