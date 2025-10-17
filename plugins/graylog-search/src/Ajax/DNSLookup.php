<?php
/**
 * DNS Lookup AJAX Handler Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Ajax;

use GraylogSearch\Helpers\Security;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DNS Lookup AJAX Handler Class
 */
class DNSLookup {

	/**
	 * Class instance.
	 *
	 * @var DNSLookup
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return DNSLookup
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_graylog_dns_lookup', array( $this, 'handle_lookup' ) );
		add_action( 'wp_ajax_nopriv_graylog_dns_lookup', array( $this, 'handle_public_lookup' ) );
	}

	/**
	 * Handle DNS lookup AJAX request.
	 */
	public function handle_lookup() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		// Get IP address.
		$ip = Security::sanitize_text( Security::get_post( 'ip', '' ) );

		// Validate IP address.
		if ( ! Security::validate_ip( $ip ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid IP address.', 'graylog-search' ),
				)
			);
			return;
		}

		// Perform DNS lookup with timeout.
		$hostname = gethostbyaddr( $ip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.gethostbyaddr_gethostbyaddr

		// Check if resolution was successful.
		if ( $hostname && $hostname !== $ip ) {
			// Success - got a hostname.
			wp_send_json_success(
				array(
					'hostname' => $hostname,
					'ip'       => $ip,
				)
			);
		} else {
			// Failed to resolve.
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Could not resolve IP address.', 'graylog-search' ),
					'ip'      => $ip,
				)
			);
		}
	}

	/**
	 * Handle public (non-admin) DNS lookup AJAX request.
	 */
	public function handle_public_lookup() {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You must be logged in.', 'graylog-search' ),
				)
			);
			return;
		}

		// Call the main handler.
		$this->handle_lookup();
	}
}
