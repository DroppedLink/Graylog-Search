<?php
/**
 * Security Helper Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Helper Class
 */
class Security {

	/**
	 * Verify AJAX nonce and capability.
	 *
	 * @param string $capability Required capability.
	 * @param string $nonce_action Nonce action name.
	 * @param string $nonce_field Nonce field name.
	 * @return bool True if valid, dies otherwise.
	 */
	public static function verify_ajax_request( $capability = 'search_graylog_logs', $nonce_action = 'graylog_search_nonce', $nonce_field = 'nonce' ) {
		// Verify nonce.
		if ( ! check_ajax_referer( $nonce_action, $nonce_field, false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Security check failed. Please refresh the page and try again.', 'graylog-search' ),
				)
			);
			wp_die();
		}

		// Check capability.
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Insufficient permissions.', 'graylog-search' ),
				)
			);
			wp_die();
		}

		return true;
	}

	/**
	 * Sanitize search query input while preserving newlines.
	 *
	 * WordPress's sanitize_textarea_field() converts newlines to spaces,
	 * which breaks multi-line search functionality. This method preserves
	 * newlines while still removing dangerous HTML and scripts.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized input with preserved newlines.
	 */
	public static function sanitize_search_query( $input ) {
		// Unslash the input.
		$input = wp_unslash( $input );

		// Remove HTML tags but preserve newlines.
		$input = wp_strip_all_tags( $input, false );

		// Normalize entities for security.
		$input = wp_kses_normalize_entities( $input );

		return trim( $input );
	}

	/**
	 * Sanitize multi-line input (for filter_out and similar fields).
	 *
	 * Same as sanitize_search_query - preserves newlines for proper parsing.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized input with preserved newlines.
	 */
	public static function sanitize_multiline_input( $input ) {
		return self::sanitize_search_query( $input );
	}

	/**
	 * Sanitize text field input.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized input.
	 */
	public static function sanitize_text( $input ) {
		return sanitize_text_field( wp_unslash( $input ) );
	}

	/**
	 * Get POST parameter safely.
	 *
	 * @param string $key Parameter key.
	 * @param mixed  $default Default value.
	 * @return mixed Sanitized value or default.
	 */
	public static function get_post( $key, $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return wp_unslash( $_POST[ $key ] );
	}

	/**
	 * Validate IP address.
	 *
	 * @param string $ip IP address to validate.
	 * @return bool True if valid IP.
	 */
	public static function validate_ip( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Check if current user can search Graylog logs.
	 *
	 * @return bool
	 */
	public static function can_search_logs() {
		return current_user_can( 'search_graylog_logs' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Debug log - only logs if WP_DEBUG is enabled.
	 *
	 * @param string $message Log message.
	 * @param string $context Context for the log.
	 */
	public static function debug_log( $message, $context = 'Graylog Search' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[%s] %s', $context, $message ) );
		}
	}
}
