<?php
/**
 * Search AJAX Handler Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Ajax;

use GraylogSearch\API\GraylogClient;
use GraylogSearch\API\QueryBuilder;
use GraylogSearch\Helpers\Security;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search AJAX Handler Class
 */
class SearchHandler {

	/**
	 * Class instance.
	 *
	 * @var SearchHandler
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return SearchHandler
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
		add_action( 'wp_ajax_graylog_search_logs', array( $this, 'handle_search' ) );
		add_action( 'wp_ajax_nopriv_graylog_search_logs', array( $this, 'handle_public_search' ) );
	}

	/**
	 * Handle search AJAX request.
	 */
	public function handle_search() {
		Security::verify_ajax_request( 'search_graylog_logs' );

		// Get search parameters.
		$search_query = Security::sanitize_search_query( Security::get_post( 'search_query', '' ) );
		$search_mode  = Security::sanitize_text( Security::get_post( 'search_mode', 'simple' ) );
		$filter_out   = Security::sanitize_multiline_input( Security::get_post( 'filter_out', '' ) );
		$time_range   = absint( Security::get_post( 'time_range', 86400 ) );
		$limit        = absint( Security::get_post( 'limit', 100 ) );
		$offset       = absint( Security::get_post( 'offset', 0 ) );

		Security::debug_log( '===== GRAYLOG SEARCH DEBUG =====' );
		Security::debug_log( 'Search Query: "' . $search_query . '"' );
		Security::debug_log( 'Search Mode: "' . $search_mode . '"' );
		Security::debug_log( 'Filter Out: "' . $filter_out . '"' );
		Security::debug_log( 'Time Range: ' . $time_range );
		Security::debug_log( 'Limit: ' . $limit );

		// Build Graylog query.
		$query = QueryBuilder::build_query( $search_query, $search_mode, $filter_out );
		Security::debug_log( 'Final Graylog Query: ' . $query );
		Security::debug_log( '================================' );

		// Check cache first (5-minute TTL).
		$cache_key      = 'graylog_search_' . md5( $query . $time_range . $limit . $offset );
		$cached_results = get_transient( $cache_key );

		if ( false !== $cached_results ) {
			Security::debug_log( 'Returning cached results' );
			wp_send_json_success( $cached_results );
			return;
		}

		// Make API request.
		$client  = new GraylogClient();
		$results = $client->search( $query, $time_range, $limit, $offset );

		if ( is_wp_error( $results ) ) {
			Security::debug_log( 'API error: ' . $results->get_error_message() );
			wp_send_json_error(
				array(
					'message' => $results->get_error_message(),
				)
			);
			return;
		}

		// Cache results for 5 minutes.
		set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );

		// Track recent search.
		if ( is_user_logged_in() ) {
			$this->track_recent_search(
				array(
					'search_query' => $search_query,
					'search_mode'  => $search_mode,
					'filter_out'   => $filter_out,
					'time_range'   => $time_range,
				)
			);

			// Log to search history database.
			$execution_time = floatval( Security::get_post( 'execution_time', 0 ) );
			$this->log_to_history(
				array(
					'search_query' => $search_query,
					'search_mode'  => $search_mode,
					'filter_out'   => $filter_out,
					'time_range'   => $time_range,
					'limit'        => $limit,
					'offset'       => $offset,
				),
				$query,
				isset( $results['total_results'] ) ? $results['total_results'] : count( $results['messages'] ?? array() ),
				$execution_time
			);
		}

		Security::debug_log( 'Success - ' . count( $results['messages'] ?? array() ) . ' messages' );
		wp_send_json_success( $results );
	}

	/**
	 * Handle public (non-admin) search AJAX request.
	 */
	public function handle_public_search() {
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
		$this->handle_search();
	}

	/**
	 * Track recent search in user meta.
	 *
	 * @param array $search_data Search data.
	 */
	private function track_recent_search( $search_data ) {
		$user_id = get_current_user_id();

		$recent_searches = get_user_meta( $user_id, 'graylog_recent_searches', true );
		if ( ! is_array( $recent_searches ) ) {
			$recent_searches = array();
		}

		// Add timestamp.
		$search_data['timestamp'] = current_time( 'mysql' );

		// Add to beginning of array.
		array_unshift( $recent_searches, $search_data );

		// Keep only last 10.
		$recent_searches = array_slice( $recent_searches, 0, 10 );

		// Update user meta.
		update_user_meta( $user_id, 'graylog_recent_searches', $recent_searches );
	}

	/**
	 * Log search to history database (if search history functions exist).
	 *
	 * @param array  $search_params Search parameters.
	 * @param string $query Final query.
	 * @param int    $results_count Results count.
	 * @param float  $execution_time Execution time.
	 */
	private function log_to_history( $search_params, $query, $results_count, $execution_time ) {
		// Check if legacy function exists.
		if ( function_exists( 'graylog_log_search_to_history' ) ) {
			graylog_log_search_to_history( $search_params, $query, $results_count, $execution_time );
		}
	}
}
