<?php
/**
 * Admin Search Page Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Admin;

use GraylogSearch\Helpers\Timezone;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Search Page Class
 *
 * Manages the main Graylog search interface in WordPress admin.
 */
class SearchPage {

	/**
	 * Class instance.
	 *
	 * @var SearchPage
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return SearchPage
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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Graylog Search', 'graylog-search' ),
			__( 'Graylog Search', 'graylog-search' ),
			'search_graylog_logs',
			'graylog-search',
			array( $this, 'render_page' ),
			'dashicons-search',
			30
		);
	}

	/**
	 * Render the search page.
	 */
	public function render_page() {
		// Check permissions.
		if ( ! current_user_can( 'search_graylog_logs' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'graylog-search' ) );
		}

		// Get timezone helper.
		$timezone = Timezone::get_instance();

		?>
		<div class="wrap graylog-search-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php $this->check_configuration(); ?>

			<div class="graylog-search-tabs">
				<button class="graylog-tab-btn active" data-tab="simple-search">
					<?php esc_html_e( 'Simple Search', 'graylog-search' ); ?>
				</button>
				<button class="graylog-tab-btn" data-tab="regex-search">
					<?php esc_html_e( 'Regex Search', 'graylog-search' ); ?>
					<span class="beta-badge"><?php esc_html_e( 'BETA', 'graylog-search' ); ?></span>
				</button>
				<button class="graylog-tab-btn" data-tab="query-builder">
					<?php esc_html_e( 'Query Builder', 'graylog-search' ); ?>
					<span class="beta-badge"><?php esc_html_e( 'BETA', 'graylog-search' ); ?></span>
				</button>
			</div>

			<!-- Simple Search Tab -->
			<div id="simple-search" class="graylog-tab-content active">
				<?php $this->render_simple_search_form(); ?>
			</div>

			<!-- Regex Search Tab -->
			<div id="regex-search" class="graylog-tab-content">
				<?php $this->render_regex_search_form(); ?>
			</div>

			<!-- Query Builder Tab -->
			<div id="query-builder" class="graylog-tab-content">
				<?php $this->render_query_builder_form(); ?>
			</div>

			<!-- Search Results -->
			<div id="search-results" style="display: none;">
				<h2><?php esc_html_e( 'Search Results', 'graylog-search' ); ?></h2>
				<div class="results-header">
					<div class="results-info">
						<span id="result-count"></span>
						<span id="search-time"></span>
					</div>
					<div class="results-actions">
						<button type="button" id="export-btn" class="button">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export', 'graylog-search' ); ?>
						</button>
						<div id="export-menu" class="export-menu" style="display: none;">
							<button type="button" data-format="csv"><?php esc_html_e( 'Export as CSV', 'graylog-search' ); ?></button>
							<button type="button" data-format="json"><?php esc_html_e( 'Export as JSON', 'graylog-search' ); ?></button>
							<button type="button" data-format="txt"><?php esc_html_e( 'Export as Text', 'graylog-search' ); ?></button>
						</div>
					</div>
				</div>
				<div id="results-content"></div>
			</div>

			<div id="loading-indicator" style="display: none;">
				<div class="loading-spinner"></div>
				<p><?php esc_html_e( 'Searching Graylog logs...', 'graylog-search' ); ?></p>
			</div>
		</div>

		<!-- Regex Helper Modal -->
		<div id="regex-helper-modal" class="regex-helper-modal" style="display: none;">
			<div class="regex-helper-content">
				<div class="regex-helper-header">
					<h3><?php esc_html_e( 'Regex Pattern Helper', 'graylog-search' ); ?></h3>
					<button type="button" class="regex-helper-close">&times;</button>
				</div>
				<div class="regex-helper-body">
					<!-- Content loaded by JavaScript -->
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if plugin is configured.
	 */
	private function check_configuration() {
		$api_url   = get_option( 'graylog_api_url' );
		$api_token = get_option( 'graylog_api_token' );

		if ( empty( $api_url ) || empty( $api_token ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Configuration Required:', 'graylog-search' ); ?></strong>
					<?php
					printf(
						/* translators: %s: settings page URL */
						esc_html__( 'Please configure your Graylog API settings on the %s before using the search.', 'graylog-search' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=graylog-search-settings' ) ) . '">' . esc_html__( 'Settings page', 'graylog-search' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render simple search form.
	 */
	private function render_simple_search_form() {
		$timezone = Timezone::get_instance();
		?>
		<div class="tab-help-text">
			<p><?php esc_html_e( 'Enter one or more search terms. The search will find logs containing ANY of these terms.', 'graylog-search' ); ?></p>
		</div>

		<div class="graylog-search-form-container">
			<form id="graylog-search-form" method="post">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="search-query"><?php esc_html_e( 'Search Query:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<textarea 
									name="search_query" 
									id="search-query" 
									class="regular-text search-query-input" 
									rows="3"
									placeholder="<?php esc_attr_e( 'e.g., server01, error, 192.168.1.1', 'graylog-search' ); ?>"></textarea>
								<p class="description"><?php esc_html_e( 'Multiple terms: one per line or comma-separated. Examples: server01, error', 'graylog-search' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="filter-out"><?php esc_html_e( 'Filter Out:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<textarea 
									name="filter_out" 
									id="filter-out" 
									class="regular-text" 
									rows="2"
									placeholder="<?php esc_attr_e( 'e.g., debug, info', 'graylog-search' ); ?>"></textarea>
								<p class="description"><?php esc_html_e( 'Exclude logs containing these terms (optional)', 'graylog-search' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="time-range"><?php esc_html_e( 'Time Range:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<select name="time_range" id="time-range" class="regular-text">
									<option value="last_hour"><?php esc_html_e( 'Last Hour', 'graylog-search' ); ?></option>
									<option value="last_day" selected><?php esc_html_e( 'Last Day', 'graylog-search' ); ?></option>
									<option value="last_week"><?php esc_html_e( 'Last Week', 'graylog-search' ); ?></option>
									<option value="last_month"><?php esc_html_e( 'Last Month', 'graylog-search' ); ?></option>
									<option value="custom"><?php esc_html_e( 'Custom', 'graylog-search' ); ?></option>
								</select>
							</td>
						</tr>
						<tr class="custom-time-range" style="display: none;">
							<th scope="row">
								<label for="start-time"><?php esc_html_e( 'Start Time:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<input type="datetime-local" name="start_time" id="start-time" class="regular-text">
							</td>
						</tr>
						<tr class="custom-time-range" style="display: none;">
							<th scope="row">
								<label for="end-time"><?php esc_html_e( 'End Time:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<input type="datetime-local" name="end_time" id="end-time" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="result-limit"><?php esc_html_e( 'Result Limit:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<select name="result_limit" id="result-limit" class="regular-text">
									<option value="50">50</option>
									<option value="100" selected>100</option>
									<option value="200">200</option>
									<option value="500">500</option>
									<option value="1000">1000</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="timezone"><?php esc_html_e( 'Timezone:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<select name="timezone" id="timezone" class="regular-text">
									<?php
									$user_timezone       = $timezone->get_user_timezone();
									$available_timezones = $timezone->get_available_timezones();
									foreach ( $available_timezones as $value => $label ) {
										printf(
											'<option value="%s"%s>%s</option>',
											esc_attr( $value ),
											selected( $user_timezone, $value, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
								<p class="description"><?php esc_html_e( 'Results will be displayed in this timezone', 'graylog-search' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<input type="hidden" name="search_mode" value="simple">

				<p class="submit">
					<button type="submit" id="search-button" class="button button-primary">
						<?php esc_html_e( 'Search Logs', 'graylog-search' ); ?>
					</button>
					<button type="button" id="clear-button" class="button">
						<?php esc_html_e( 'Clear', 'graylog-search' ); ?>
					</button>
					<button type="button" id="save-search-button" class="button" disabled>
						<?php esc_html_e( 'Save Search', 'graylog-search' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render regex search form.
	 */
	private function render_regex_search_form() {
		$timezone = Timezone::get_instance();
		?>
		<div class="tab-help-text">
			<p><?php esc_html_e( 'Use regular expressions for advanced pattern matching in logs.', 'graylog-search' ); ?></p>
		</div>

		<div class="graylog-search-form-container">
			<form id="regex-search-form" method="post">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="regex-pattern"><?php esc_html_e( 'Regex Pattern:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<textarea 
									name="regex_pattern" 
									id="regex-pattern" 
									class="regular-text" 
									rows="3"
									placeholder="<?php esc_attr_e( 'e.g., error|warning|critical', 'graylog-search' ); ?>"></textarea>
								<p class="description">
									<?php esc_html_e( 'Enter your regex pattern.', 'graylog-search' ); ?>
									<button type="button" id="show-regex-helper" class="button button-small">
										<?php esc_html_e( 'Pattern Helper', 'graylog-search' ); ?>
									</button>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="regex-time-range"><?php esc_html_e( 'Time Range:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<select name="time_range" id="regex-time-range" class="regular-text">
									<option value="last_hour"><?php esc_html_e( 'Last Hour', 'graylog-search' ); ?></option>
									<option value="last_day" selected><?php esc_html_e( 'Last Day', 'graylog-search' ); ?></option>
									<option value="last_week"><?php esc_html_e( 'Last Week', 'graylog-search' ); ?></option>
									<option value="last_month"><?php esc_html_e( 'Last Month', 'graylog-search' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="regex-result-limit"><?php esc_html_e( 'Result Limit:', 'graylog-search' ); ?></label>
							</th>
							<td>
								<select name="result_limit" id="regex-result-limit" class="regular-text">
									<option value="50">50</option>
									<option value="100" selected>100</option>
									<option value="200">200</option>
									<option value="500">500</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<input type="hidden" name="search_mode" value="regex">

				<p class="submit">
					<button type="submit" id="regex-search-button" class="button button-primary">
						<?php esc_html_e( 'Search with Regex', 'graylog-search' ); ?>
					</button>
					<button type="button" id="regex-clear-button" class="button">
						<?php esc_html_e( 'Clear', 'graylog-search' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render query builder form.
	 */
	private function render_query_builder_form() {
		?>
		<div class="tab-help-text">
			<p><?php esc_html_e( 'Build complex queries using a visual interface.', 'graylog-search' ); ?></p>
		</div>

		<div class="graylog-search-form-container">
			<div id="query-builder-container">
				<div class="query-builder-rules">
					<div class="query-rule">
						<select class="field-select">
							<option value="message"><?php esc_html_e( 'Message', 'graylog-search' ); ?></option>
							<option value="source"><?php esc_html_e( 'Source', 'graylog-search' ); ?></option>
							<option value="level"><?php esc_html_e( 'Level', 'graylog-search' ); ?></option>
						</select>
						<select class="operator-select">
							<option value="contains"><?php esc_html_e( 'contains', 'graylog-search' ); ?></option>
							<option value="equals"><?php esc_html_e( 'equals', 'graylog-search' ); ?></option>
							<option value="not_equals"><?php esc_html_e( 'not equals', 'graylog-search' ); ?></option>
						</select>
						<input type="text" class="value-input" placeholder="<?php esc_attr_e( 'Value', 'graylog-search' ); ?>">
						<button type="button" class="button remove-rule"><?php esc_html_e( 'Remove', 'graylog-search' ); ?></button>
					</div>
				</div>

				<p>
					<button type="button" id="add-rule-button" class="button">
						<?php esc_html_e( '+ Add Rule', 'graylog-search' ); ?>
					</button>
					<button type="button" id="builder-search-button" class="button button-primary">
						<?php esc_html_e( 'Search', 'graylog-search' ); ?>
					</button>
				</p>
			</div>
		</div>
		<?php
	}
}
