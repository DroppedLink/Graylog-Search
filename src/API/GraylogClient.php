<?php
/**
 * Graylog API Client Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\API;

use GraylogSearch\Helpers\Security;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Graylog API Client Class
 */
class GraylogClient {

	/**
	 * API URL.
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * API Token.
	 *
	 * @var string
	 */
	private $api_token;

	/**
	 * Disable SSL verification.
	 *
	 * @var bool
	 */
	private $disable_ssl;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api_url     = get_option( 'graylog_api_url', '' );
		$this->api_token   = get_option( 'graylog_api_token', '' );
		$this->disable_ssl = '1' === get_option( 'graylog_search_disable_ssl_verify', '0' );
	}

	/**
	 * Check if API is configured.
	 *
	 * @return bool True if configured.
	 */
	public function is_configured() {
		return ! empty( $this->api_url ) && ! empty( $this->api_token );
	}

	/**
	 * Make search request to Graylog API.
	 *
	 * @param string $query Graylog query.
	 * @param int    $time_range Time range in seconds.
	 * @param int    $limit Result limit.
	 * @param int    $offset Result offset for pagination.
	 * @return array|WP_Error Results or error.
	 */
	public function search( $query, $time_range, $limit, $offset = 0 ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error(
				'not_configured',
				__( 'Graylog API not configured.', 'graylog-search' )
			);
		}

		// Clean up API URL.
		$api_url = $this->prepare_api_url();

		// Build search endpoint - Graylog 6.1+ uses /search/messages.
		$endpoint = $api_url . '/search/messages';

		// Build query parameters with pagination.
		$params = array(
			'query'  => $query,
			'fields' => 'timestamp,source,message,level',
			'size'   => $limit,
			'offset' => $offset,
		);

		$url = add_query_arg( $params, $endpoint );

		Security::debug_log( 'API Request URL: ' . $url );

		// Prepare request arguments.
		$args = array(
			'headers' => array(
				'Authorization'  => 'Basic ' . base64_encode( $this->api_token . ':token' ),
				'Accept'         => 'application/json',
				'X-Requested-By' => 'wordpress-plugin',
			),
			'timeout' => 30,
		);

		// Handle SSL verification setting.
		if ( $this->disable_ssl ) {
			$args['sslverify'] = false;
			Security::debug_log( 'SSL verification disabled' );
		}

		// Make API request.
		$response = wp_remote_get( $url, $args );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			Security::debug_log( 'WP Error: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		Security::debug_log( 'Response status: ' . $status_code );

		if ( 200 !== $status_code ) {
			Security::debug_log( 'API error response: ' . substr( $body, 0, 500 ) );
			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: %1$d: HTTP status code, %2$s: Error message */
					__( 'Graylog API returned status code %1$d: %2$s', 'graylog-search' ),
					$status_code,
					substr( $body, 0, 200 )
				)
			);
		}

		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			Security::debug_log( 'JSON decode error: ' . json_last_error_msg() );
			return new \WP_Error(
				'json_error',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse Graylog API response: %s', 'graylog-search' ),
					json_last_error_msg()
				)
			);
		}

		// Convert Graylog 6.1+ format to plugin format.
		return $this->convert_api_response( $data );
	}

	/**
	 * Test connection to Graylog API.
	 *
	 * @param string $api_url Optional API URL override.
	 * @param string $api_token Optional API token override.
	 * @param bool   $disable_ssl Optional SSL setting override.
	 * @return array Test results.
	 */
	public function test_connection( $api_url = '', $api_token = '', $disable_ssl = false ) {
		$start_time = microtime( true );

		// Use provided values or defaults.
		if ( empty( $api_url ) ) {
			$api_url = $this->api_url;
		}
		if ( empty( $api_token ) ) {
			$api_token = $this->api_token;
		}

		// Prepare API URL.
		$api_url = rtrim( $api_url, '/' );
		if ( ! preg_match( '/\/api$/', $api_url ) ) {
			$api_url .= '/api';
		}

		$suggestions = array();

		// Test 1: System endpoint.
		$response = wp_remote_get(
			$api_url . '/system',
			array(
				'headers'   => array(
					'Authorization'  => 'Basic ' . base64_encode( $api_token . ':token' ),
					'Accept'         => 'application/json',
					'X-Requested-By' => 'Graylog-Search-Plugin',
				),
				'timeout'   => 15,
				'sslverify' => ! $disable_ssl,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			// Provide helpful suggestions based on error.
			if ( false !== strpos( $error_message, 'cURL error 60' ) || false !== strpos( $error_message, 'SSL' ) ) {
				$suggestions[] = __( 'SSL certificate error detected. Try enabling "Disable SSL Verification" checkbox.', 'graylog-search' );
			}
			if ( false !== strpos( $error_message, 'Could not resolve host' ) ) {
				$suggestions[] = __( 'DNS resolution failed. Check if the hostname is correct.', 'graylog-search' );
			}
			if ( false !== strpos( $error_message, 'Connection timed out' ) || false !== strpos( $error_message, 'Operation timed out' ) ) {
				$suggestions[] = __( 'Connection timeout. Check if the server is running and accessible.', 'graylog-search' );
			}
			if ( false !== strpos( $error_message, 'Connection refused' ) ) {
				$suggestions[] = __( 'Connection refused. Check if Graylog is running on the specified port.', 'graylog-search' );
			}

			return array(
				'success'     => false,
				'message'     => $error_message,
				'details'     => sprintf(
					/* translators: %s: API URL */
					__( 'Failed to connect to: %s', 'graylog-search' ),
					$api_url . '/system'
				),
				'suggestions' => $suggestions,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $status_code ) {
			return array(
				'success'     => false,
				'message'     => __( 'Authentication failed (HTTP 401)', 'graylog-search' ),
				'details'     => __( 'The API token is invalid or expired.', 'graylog-search' ),
				'suggestions' => array(
					__( 'Generate a new API token in Graylog: System → Users → [Your User] → Edit Tokens', 'graylog-search' ),
					__( 'Make sure you copied the entire token', 'graylog-search' ),
					__( 'Check that the token has proper permissions', 'graylog-search' ),
				),
			);
		}

		if ( 200 !== $status_code ) {
			$body = wp_remote_retrieve_body( $response );
			return array(
				'success'     => false,
				'message'     => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Graylog returned HTTP %d', 'graylog-search' ),
					$status_code
				),
				'details'     => substr( $body, 0, 500 ),
				'suggestions' => array(
					__( 'Check if the API URL is correct', 'graylog-search' ),
					__( 'Verify Graylog is running properly', 'graylog-search' ),
				),
			);
		}

		// Parse system info.
		$body        = wp_remote_retrieve_body( $response );
		$system_data = json_decode( $body, true );

		$graylog_version = isset( $system_data['version'] ) ? $system_data['version'] : __( 'Unknown', 'graylog-search' );
		$hostname        = isset( $system_data['hostname'] ) ? $system_data['hostname'] : __( 'Unknown', 'graylog-search' );

		// Test 2: Try a simple search.
		$search_url = add_query_arg(
			array(
				'query' => '*',
				'range' => 300,
				'limit' => 10,
			),
			$api_url . '/search/universal/relative'
		);

		$search_response = wp_remote_get(
			$search_url,
			array(
				'headers'   => array(
					'Authorization'  => 'Basic ' . base64_encode( $api_token . ':token' ),
					'Accept'         => 'application/json',
					'X-Requested-By' => 'Graylog-Search-Plugin',
				),
				'timeout'   => 15,
				'sslverify' => ! $disable_ssl,
			)
		);

		$message_count = 0;
		if ( ! is_wp_error( $search_response ) && 200 === wp_remote_retrieve_response_code( $search_response ) ) {
			$search_body = wp_remote_retrieve_body( $search_response );
			$search_data = json_decode( $search_body, true );
			if ( isset( $search_data['messages'] ) ) {
				$message_count = count( $search_data['messages'] );
			}
		}

		$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		return array(
			'success'         => true,
			'graylog_version' => $graylog_version,
			'hostname'        => $hostname,
			'message_count'   => $message_count,
			'response_time'   => $response_time,
		);
	}

	/**
	 * Prepare API URL.
	 *
	 * @return string Prepared API URL.
	 */
	private function prepare_api_url() {
		$api_url = rtrim( $this->api_url, '/' );

		// Ensure /api is in the path.
		if ( ! preg_match( '/\/api$/', $api_url ) ) {
			$api_url .= '/api';
		}

		return $api_url;
	}

	/**
	 * Convert Graylog 6.1+ API response format to plugin-expected format.
	 *
	 * @param array $data API response data.
	 * @return array Converted data.
	 */
	private function convert_api_response( $data ) {
		// Graylog 6.1+ returns: {schema: [...], datarows: [[...]], metadata: {...}}.
		// Plugin expects: {messages: [{message: {...}, timestamp: ...}], ...}.

		if ( ! isset( $data['schema'] ) || ! isset( $data['datarows'] ) ) {
			// Legacy format or unexpected response.
			Security::debug_log( 'Unexpected API response format' );
			return $data;
		}

		$messages = array();

		// Build field map from schema.
		$field_map = array();
		foreach ( $data['schema'] as $index => $column ) {
			$field_map[ $column['field'] ] = $index;
		}

		// Convert datarows to messages array.
		foreach ( $data['datarows'] as $row ) {
			$message_obj = array();

			// Map fields from row to message object.
			if ( isset( $field_map['timestamp'] ) ) {
				$message_obj['timestamp'] = $row[ $field_map['timestamp'] ];
			}

			if ( isset( $field_map['source'] ) ) {
				$message_obj['source'] = $row[ $field_map['source'] ];
			}

			if ( isset( $field_map['message'] ) ) {
				$message_obj['message'] = $row[ $field_map['message'] ];
			}

			if ( isset( $field_map['level'] ) ) {
				$message_obj['level'] = $row[ $field_map['level'] ];
			} else {
				$message_obj['level'] = -1; // Default if not provided.
			}

			// Wrap in expected structure.
			$messages[] = array(
				'message'   => $message_obj,
				'timestamp' => $message_obj['timestamp'],
			);
		}

		// Return in expected format.
		return array(
			'messages'      => $messages,
			'total_results' => count( $messages ),
			'time'          => isset( $data['metadata']['effective_timerange'] ) ? $data['metadata']['effective_timerange'] : null,
		);
	}
}
