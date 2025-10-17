<?php
/**
 * Saved Searches AJAX Handler Class
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
 * Saved Searches AJAX Handler Class
 */
class SavedSearches {

	/**
	 * Class instance.
	 *
	 * @var SavedSearches
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return SavedSearches
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
		add_action( 'wp_ajax_graylog_save_search', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_graylog_get_saved_searches', array( $this, 'handle_get' ) );
		add_action( 'wp_ajax_graylog_delete_saved_search', array( $this, 'handle_delete' ) );
		add_action( 'wp_ajax_graylog_get_recent_searches', array( $this, 'handle_get_recent' ) );
		add_action( 'wp_ajax_graylog_get_quick_filters', array( $this, 'handle_get_quick_filters' ) );
	}

	/**
	 * Handle save search AJAX request.
	 */
	public function handle_save() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$search_name = Security::sanitize_text( Security::get_post( 'name', '' ) );
		$search_data = array(
			'search_query' => Security::sanitize_search_query( Security::get_post( 'search_query', '' ) ),
			'search_mode'  => Security::sanitize_text( Security::get_post( 'search_mode', 'simple' ) ),
			'filter_out'   => Security::sanitize_multiline_input( Security::get_post( 'filter_out', '' ) ),
			'time_range'   => absint( Security::get_post( 'time_range', 86400 ) ),
			'created'      => current_time( 'mysql' ),
		);

		// Get existing saved searches.
		$user_id         = get_current_user_id();
		$saved_searches  = get_user_meta( $user_id, 'graylog_saved_searches', true );
		if ( ! is_array( $saved_searches ) ) {
			$saved_searches = array();
		}

		// Add new search.
		$saved_searches[ $search_name ] = $search_data;

		// Update user meta.
		update_user_meta( $user_id, 'graylog_saved_searches', $saved_searches );

		wp_send_json_success(
			array(
				'message'  => esc_html__( 'Search saved successfully.', 'graylog-search' ),
				'searches' => $saved_searches,
			)
		);
	}

	/**
	 * Handle get saved searches AJAX request.
	 */
	public function handle_get() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$user_id        = get_current_user_id();
		$saved_searches = get_user_meta( $user_id, 'graylog_saved_searches', true );
		if ( ! is_array( $saved_searches ) ) {
			$saved_searches = array();
		}

		wp_send_json_success( array( 'searches' => $saved_searches ) );
	}

	/**
	 * Handle delete saved search AJAX request.
	 */
	public function handle_delete() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$search_name = Security::sanitize_text( Security::get_post( 'name', '' ) );

		// Get existing saved searches.
		$user_id        = get_current_user_id();
		$saved_searches = get_user_meta( $user_id, 'graylog_saved_searches', true );
		if ( ! is_array( $saved_searches ) ) {
			$saved_searches = array();
		}

		// Remove search.
		unset( $saved_searches[ $search_name ] );

		// Update user meta.
		update_user_meta( $user_id, 'graylog_saved_searches', $saved_searches );

		wp_send_json_success(
			array(
				'message'  => esc_html__( 'Search deleted successfully.', 'graylog-search' ),
				'searches' => $saved_searches,
			)
		);
	}

	/**
	 * Handle get recent searches AJAX request.
	 */
	public function handle_get_recent() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$user_id         = get_current_user_id();
		$recent_searches = get_user_meta( $user_id, 'graylog_recent_searches', true );
		if ( ! is_array( $recent_searches ) ) {
			$recent_searches = array();
		}

		wp_send_json_success( array( 'searches' => $recent_searches ) );
	}

	/**
	 * Handle get quick filters AJAX request.
	 */
	public function handle_get_quick_filters() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		$filters = array(
			array(
				'name' => __( 'Errors (Last Hour)', 'graylog-search' ),
				'data' => array(
					'search_query' => 'error',
					'search_mode'  => 'simple',
					'filter_out'   => '',
					'time_range'   => 3600,
				),
			),
			array(
				'name' => __( 'Warnings (Last Hour)', 'graylog-search' ),
				'data' => array(
					'search_query' => 'warning',
					'search_mode'  => 'simple',
					'filter_out'   => '',
					'time_range'   => 3600,
				),
			),
			array(
				'name' => __( 'Errors (Today)', 'graylog-search' ),
				'data' => array(
					'search_query' => 'error',
					'search_mode'  => 'simple',
					'filter_out'   => '',
					'time_range'   => 86400,
				),
			),
			array(
				'name' => __( 'All Logs (Last Hour)', 'graylog-search' ),
				'data' => array(
					'search_query' => '',
					'search_mode'  => 'simple',
					'filter_out'   => '',
					'time_range'   => 3600,
				),
			),
		);

		wp_send_json_success( array( 'filters' => $filters ) );
	}
}
