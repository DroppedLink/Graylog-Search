<?php
/**
 * Admin Settings Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Admin;

use GraylogSearch\Updater\GitHubUpdater;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Class
 *
 * Manages the plugin's settings page using WordPress Settings API.
 */
class Settings {

	/**
	 * Class instance.
	 *
	 * @var Settings
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_graylog_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'graylog-search',
			__( 'Graylog Settings', 'graylog-search' ),
			__( 'Settings', 'graylog-search' ),
			'manage_options',
			'graylog-search-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// API Settings Section.
		add_settings_section(
			'graylog_api_settings',
			__( 'Graylog API Configuration', 'graylog-search' ),
			array( $this, 'render_api_section' ),
			'graylog-search-settings'
		);

		register_setting(
			'graylog_search_settings',
			'graylog_api_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		add_settings_field(
			'graylog_api_url',
			__( 'Graylog API URL', 'graylog-search' ),
			array( $this, 'render_api_url_field' ),
			'graylog-search-settings',
			'graylog_api_settings'
		);

		register_setting(
			'graylog_search_settings',
			'graylog_api_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_field(
			'graylog_api_token',
			__( 'API Token', 'graylog-search' ),
			array( $this, 'render_api_token_field' ),
			'graylog-search-settings',
			'graylog_api_settings'
		);

		register_setting(
			'graylog_search_settings',
			'graylog_search_disable_ssl_verify',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		add_settings_field(
			'graylog_search_disable_ssl_verify',
			__( 'Disable SSL Verification', 'graylog-search' ),
			array( $this, 'render_ssl_field' ),
			'graylog-search-settings',
			'graylog_api_settings'
		);

		// GitHub Updater Settings Section.
		add_settings_section(
			'graylog_updater_settings',
			__( 'Auto-Update Configuration', 'graylog-search' ),
			array( $this, 'render_updater_section' ),
			'graylog-search-settings'
		);

		register_setting(
			'graylog_search_settings',
			'graylog_search_github_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_field(
			'graylog_search_github_token',
			__( 'GitHub Token', 'graylog-search' ),
			array( $this, 'render_github_token_field' ),
			'graylog-search-settings',
			'graylog_updater_settings'
		);
	}

	/**
	 * Render API settings section description.
	 */
	public function render_api_section() {
		?>
		<p><?php esc_html_e( 'Configure the connection to your Graylog server API.', 'graylog-search' ); ?></p>
		<?php
	}

	/**
	 * Render API URL field.
	 */
	public function render_api_url_field() {
		$value = get_option( 'graylog_api_url', '' );
		?>
		<input 
			type="url" 
			id="graylog_api_url" 
			name="graylog_api_url" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
			placeholder="https://graylog.example.com:9000/api"
		>
		<p class="description">
			<?php esc_html_e( 'Full URL to your Graylog API (e.g., https://graylog.example.com:9000/api)', 'graylog-search' ); ?>
		</p>
		<?php
	}

	/**
	 * Render API token field.
	 */
	public function render_api_token_field() {
		$value = get_option( 'graylog_api_token', '' );
		?>
		<input 
			type="password" 
			id="graylog_api_token" 
			name="graylog_api_token" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
			autocomplete="off"
		>
		<p class="description">
			<?php esc_html_e( 'API token from Graylog (System → Users → Edit User → Create Token)', 'graylog-search' ); ?>
		</p>
		<?php
	}

	/**
	 * Render SSL verification field.
	 */
	public function render_ssl_field() {
		$value = get_option( 'graylog_search_disable_ssl_verify', false );
		?>
		<label for="disable_ssl_verify">
			<input 
				type="checkbox" 
				id="disable_ssl_verify" 
				name="graylog_search_disable_ssl_verify" 
				value="1" 
				<?php checked( $value, true ); ?>
			>
			<?php esc_html_e( 'Disable SSL certificate verification (not recommended for production)', 'graylog-search' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Only enable this for development/testing with self-signed certificates.', 'graylog-search' ); ?>
		</p>
		<?php
	}

	/**
	 * Render updater section description.
	 */
	public function render_updater_section() {
		?>
		<p><?php esc_html_e( 'Configure automatic updates from GitHub repository.', 'graylog-search' ); ?></p>
		<?php
	}

	/**
	 * Render GitHub token field.
	 */
	public function render_github_token_field() {
		$value = get_option( 'graylog_search_github_token', '' );
		?>
		<input 
			type="password" 
			id="graylog_search_github_token" 
			name="graylog_search_github_token" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
			autocomplete="off"
		>
		<p class="description">
			<?php esc_html_e( 'GitHub Personal Access Token (optional, increases API rate limit)', 'graylog-search' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'graylog-search' ) );
		}

		// Handle settings update.
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error(
				'graylog_search_messages',
				'graylog_search_message',
				__( 'Settings saved successfully!', 'graylog-search' ),
				'success'
			);
		}

		$update_status = GitHubUpdater::get_update_status();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'graylog_search_messages' ); ?>

			<!-- Update Notice -->
			<?php if ( $update_status['update_available'] ) : ?>
				<div class="notice notice-info">
					<p>
						<strong><?php esc_html_e( 'Update Available!', 'graylog-search' ); ?></strong>
						<?php
						printf(
							/* translators: 1: current version, 2: new version */
							esc_html__( 'Version %1$s is available (you have %2$s).', 'graylog-search' ),
							'<strong>' . esc_html( $update_status['new_version'] ) . '</strong>',
							esc_html( $update_status['current_version'] )
						);
						?>
						<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Update Now', 'graylog-search' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div class="graylog-settings-container">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'graylog_search_settings' );
					do_settings_sections( 'graylog-search-settings' );
					?>

					<!-- Connection Test -->
					<div class="connection-test-section">
						<h3><?php esc_html_e( 'Test Connection', 'graylog-search' ); ?></h3>
						<p><?php esc_html_e( 'Test the connection with your current settings before saving.', 'graylog-search' ); ?></p>
						<button type="button" id="test-graylog-connection" class="button button-secondary">
							<?php esc_html_e( 'Test Connection', 'graylog-search' ); ?>
						</button>
						<span id="connection-test-spinner" class="spinner" style="visibility: hidden;"></span>
						<div id="connection-test-result" style="margin-top: 15px;"></div>
					</div>

					<?php submit_button( __( 'Save Settings', 'graylog-search' ) ); ?>
				</form>

				<!-- Additional Information -->
				<div class="graylog-info-sections">
					<!-- Shortcode Usage -->
					<div class="graylog-info-box">
						<h3><?php esc_html_e( 'Shortcode Usage', 'graylog-search' ); ?></h3>
						<p><?php esc_html_e( 'Use this shortcode to display the search interface on any page or post:', 'graylog-search' ); ?></p>
						<code class="graylog-shortcode-example">[graylog_search]</code>
						<button type="button" class="button button-small" onclick="copyShortcode('[graylog_search]')">
							<?php esc_html_e( 'Copy', 'graylog-search' ); ?>
						</button>

						<h4><?php esc_html_e( 'Shortcode Parameters:', 'graylog-search' ); ?></h4>
						<ul>
							<li>
								<code>title</code> - <?php esc_html_e( 'Custom title for the search form', 'graylog-search' ); ?>
								<br><small><?php esc_html_e( 'Example:', 'graylog-search' ); ?> <code>[graylog_search title="Search Logs"]</code></small>
							</li>
							<li>
								<code>show_filters</code> - <?php esc_html_e( 'Show filter options (yes/no, default: yes)', 'graylog-search' ); ?>
								<br><small><?php esc_html_e( 'Example:', 'graylog-search' ); ?> <code>[graylog_search show_filters="no"]</code></small>
							</li>
							<li>
								<code>result_limit</code> - <?php esc_html_e( 'Default result limit (50-500, default: 100)', 'graylog-search' ); ?>
								<br><small><?php esc_html_e( 'Example:', 'graylog-search' ); ?> <code>[graylog_search result_limit="200"]</code></small>
							</li>
							<li>
								<code>time_range</code> - <?php esc_html_e( 'Default time range (last_hour/last_day/last_week/last_month)', 'graylog-search' ); ?>
								<br><small><?php esc_html_e( 'Example:', 'graylog-search' ); ?> <code>[graylog_search time_range="last_hour"]</code></small>
							</li>
						</ul>
					</div>

					<!-- Plugin Info -->
					<div class="graylog-info-box">
						<h3><?php esc_html_e( 'Plugin Information', 'graylog-search' ); ?></h3>
						<table class="widefat">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Version:', 'graylog-search' ); ?></th>
									<td><?php echo esc_html( GRAYLOG_SEARCH_VERSION ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Repository:', 'graylog-search' ); ?></th>
									<td><a href="https://github.com/DroppedLink/Graylog-Search" target="_blank">GitHub</a></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Updates:', 'graylog-search' ); ?></th>
									<td>
										<?php if ( $update_status['update_available'] ) : ?>
											<span style="color: #d63638;">
												<?php esc_html_e( 'Update available', 'graylog-search' ); ?>
											</span>
										<?php else : ?>
											<span style="color: #00a32a;">
												<?php esc_html_e( 'Up to date', 'graylog-search' ); ?>
											</span>
										<?php endif; ?>
										<button type="button" id="check-for-updates" class="button button-small">
											<?php esc_html_e( 'Check Now', 'graylog-search' ); ?>
										</button>
										<span id="update-check-status"></span>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Keyboard Shortcuts -->
					<div class="graylog-info-box">
						<h3><?php esc_html_e( 'Keyboard Shortcuts', 'graylog-search' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Shortcut', 'graylog-search' ); ?></th>
									<th><?php esc_html_e( 'Action', 'graylog-search' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>Ctrl/Cmd + Enter</code></td>
									<td><?php esc_html_e( 'Submit search', 'graylog-search' ); ?></td>
								</tr>
								<tr>
									<td><code>Ctrl/Cmd + K</code></td>
									<td><?php esc_html_e( 'Clear search form', 'graylog-search' ); ?></td>
								</tr>
								<tr>
									<td><code>Ctrl/Cmd + S</code></td>
									<td><?php esc_html_e( 'Save current search', 'graylog-search' ); ?></td>
								</tr>
								<tr>
									<td><code>Escape</code></td>
									<td><?php esc_html_e( 'Close modals/popups', 'graylog-search' ); ?></td>
								</tr>
								<tr>
									<td><code>?</code></td>
									<td><?php esc_html_e( 'Show keyboard shortcuts', 'graylog-search' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Features & Tips -->
					<div class="graylog-info-box">
						<h3><?php esc_html_e( 'Features & Tips', 'graylog-search' ); ?></h3>
						<ul>
							<li><strong><?php esc_html_e( 'Multiple Search Terms:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Enter one per line or comma-separated for OR searches', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'Saved Searches:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Save frequently used searches for quick access', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'Interactive Filtering:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Right-click on any result value to filter/exclude', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'DNS Lookup:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Hover over IP addresses to resolve hostnames', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'Export Results:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Export search results as CSV, JSON, or plain text', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'Regex Search:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Use the Regex tab for advanced pattern matching', 'graylog-search' ); ?></li>
							<li><strong><?php esc_html_e( 'Timezone Support:', 'graylog-search' ); ?></strong> <?php esc_html_e( 'Results are displayed in your selected timezone', 'graylog-search' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<style>
		.graylog-settings-container {
			max-width: 1200px;
		}
		.connection-test-section {
			background: #f8f9fa;
			padding: 20px;
			margin: 20px 0;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.graylog-info-sections {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 20px;
			margin-top: 30px;
		}
		.graylog-info-box {
			background: #fff;
			border: 1px solid #ddd;
			padding: 20px;
			border-radius: 4px;
		}
		.graylog-info-box h3 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 2px solid #0073aa;
		}
		.graylog-info-box h4 {
			margin-top: 15px;
			margin-bottom: 10px;
		}
		.graylog-info-box ul {
			margin-left: 20px;
		}
		.graylog-info-box ul li {
			margin-bottom: 10px;
		}
		.graylog-info-box code {
			background: #f0f0f1;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 13px;
		}
		.graylog-shortcode-example {
			display: inline-block;
			background: #f0f0f1;
			padding: 8px 12px;
			border-radius: 4px;
			font-size: 14px;
			margin-right: 10px;
		}
		.graylog-info-box table {
			margin-top: 15px;
		}
		.graylog-info-box table th {
			width: 140px;
			text-align: left;
			font-weight: 600;
		}
		</style>
		<?php

		// Localize script for AJAX.
		$this->localize_settings_script();
	}

	/**
	 * Localize script for settings page.
	 */
	private function localize_settings_script() {
		wp_localize_script(
			'graylog-search-admin-settings',
			'graylogSearch',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'graylog_search_nonce' ),
				'settingsNonce' => wp_create_nonce( 'graylog_test_connection_nonce' ),
				'strings'       => array(
					'error'                 => __( 'Error', 'graylog-search' ),
					'enterApiUrlToken'      => __( 'Please enter both API URL and Token.', 'graylog-search' ),
					'connectionSuccess'     => __( 'Connection Successful', 'graylog-search' ),
					'graylogVersion'        => __( 'Graylog Version', 'graylog-search' ),
					'serverHostname'        => __( 'Server Hostname', 'graylog-search' ),
					'testSearch'            => __( 'Test Search', 'graylog-search' ),
					'found'                 => __( 'Found', 'graylog-search' ),
					'messages'              => __( 'messages', 'graylog-search' ),
					'responseTime'          => __( 'Response Time', 'graylog-search' ),
					'connectionFailed'      => __( 'Connection Failed', 'graylog-search' ),
					'unknownError'          => __( 'Unknown error occurred', 'graylog-search' ),
					'details'               => __( 'Details', 'graylog-search' ),
					'suggestions'           => __( 'Suggestions', 'graylog-search' ),
					'requestFailed'         => __( 'Request Failed', 'graylog-search' ),
					'status'                => __( 'Status', 'graylog-search' ),
					'timeoutMessage'        => __( 'Request timed out. Please check your server and network.', 'graylog-search' ),
					'checking'              => __( 'Checking for updates...', 'graylog-search' ),
					'checkComplete'         => __( 'Check complete', 'graylog-search' ),
					'checkFailed'           => __( 'Check failed', 'graylog-search' ),
					'errorCheckingUpdates'  => __( 'Error checking for updates', 'graylog-search' ),
					'copied'                => __( 'Copied!', 'graylog-search' ),
					'shortcodeCopied'       => __( 'Shortcode copied to clipboard', 'graylog-search' ),
					'failedToCopy'          => __( 'Failed to copy', 'graylog-search' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for connection test.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'graylog_test_connection_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Insufficient permissions.', 'graylog-search' ),
				)
			);
		}

		$api_url    = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';
		$api_token  = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : '';
		$disable_ssl = isset( $_POST['disable_ssl'] ) && '1' === $_POST['disable_ssl'];

		if ( empty( $api_url ) || empty( $api_token ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'API URL and Token are required.', 'graylog-search' ),
				)
			);
		}

		$start_time = microtime( true );

		// Test 1: Get system information.
		$system_url = trailingslashit( $api_url ) . 'system';
		$args       = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_token . ':token' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		);

		if ( $disable_ssl ) {
			$args['sslverify'] = false;
		}

		$response = wp_remote_get( $system_url, $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message'     => $response->get_error_message(),
					'details'     => sprintf(
						/* translators: %s: API URL */
						__( 'Failed to connect to: %s', 'graylog-search' ),
						$system_url
					),
					'suggestions' => array(
						__( 'Verify the API URL is correct and accessible', 'graylog-search' ),
						__( 'Check if Graylog server is running', 'graylog-search' ),
						__( 'Ensure there are no firewall rules blocking the connection', 'graylog-search' ),
						__( 'Try enabling "Disable SSL Verification" if using self-signed certificates', 'graylog-search' ),
					),
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 401 === $status_code ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Authentication failed', 'graylog-search' ),
					'details'     => __( 'The API token is invalid or has expired.', 'graylog-search' ),
					'suggestions' => array(
						__( 'Verify the API token is correct', 'graylog-search' ),
						__( 'Generate a new token in Graylog (System → Users → Edit User → Create Token)', 'graylog-search' ),
						__( 'Ensure the token has not expired', 'graylog-search' ),
					),
				)
			);
		}

		if ( 200 !== $status_code ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: HTTP status code */
						__( 'HTTP Error %d', 'graylog-search' ),
						$status_code
					),
					'details' => $body,
				)
			);
		}

		$system_data = json_decode( $body, true );

		// Test 2: Perform a simple search.
		$search_url = trailingslashit( $api_url ) . 'search/universal/relative';
		$search_args = array_merge(
			$args,
			array(
				'body' => array(
					'query'  => '*',
					'range'  => 300,
					'limit'  => 1,
					'fields' => 'message,timestamp',
				),
			)
		);

		$search_response = wp_remote_get( add_query_arg( $search_args['body'], $search_url ), $args );
		$search_data     = array();

		if ( ! is_wp_error( $search_response ) && 200 === wp_remote_retrieve_response_code( $search_response ) ) {
			$search_body = wp_remote_retrieve_body( $search_response );
			$search_data = json_decode( $search_body, true );
		}

		$response_time = round( ( microtime( true ) - $start_time ) * 1000 );

		wp_send_json_success(
			array(
				'graylog_version' => isset( $system_data['version'] ) ? $system_data['version'] : __( 'Unknown', 'graylog-search' ),
				'hostname'        => isset( $system_data['hostname'] ) ? $system_data['hostname'] : __( 'Unknown', 'graylog-search' ),
				'message_count'   => isset( $search_data['total_results'] ) ? $search_data['total_results'] : 0,
				'response_time'   => $response_time,
			)
		);
	}
}
