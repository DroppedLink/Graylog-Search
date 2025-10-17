<?php
/**
 * Frontend Shortcode Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Frontend;

use GraylogSearch\Helpers\Timezone;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Shortcode Class
 *
 * Handles the [graylog_search] shortcode for displaying search interface on frontend pages.
 */
class Shortcode {

	/**
	 * Class instance.
	 *
	 * @var Shortcode
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Shortcode
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
		add_shortcode( 'graylog_search', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Conditionally enqueue assets if shortcode is present.
	 */
	public function maybe_enqueue_assets() {
		global $post;

		// Check if shortcode is in the current post content.
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'graylog_search' ) ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Enqueue frontend assets.
	 */
	private function enqueue_assets() {
		$version = GRAYLOG_SEARCH_VERSION;
		$url     = GRAYLOG_SEARCH_PLUGIN_URL;

		// Enqueue CSS.
		wp_enqueue_style(
			'graylog-search-style',
			$url . 'assets/css/style.css',
			array(),
			$version
		);

		wp_enqueue_style(
			'graylog-search-query-builder',
			$url . 'assets/css/query-builder.css',
			array( 'graylog-search-style' ),
			$version
		);

		wp_enqueue_style(
			'graylog-search-history',
			$url . 'assets/css/search-history.css',
			array( 'graylog-search-style' ),
			$version
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'graylog-search-script',
			$url . 'assets/js/search.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script(
			'graylog-search-keyboard',
			$url . 'assets/js/keyboard-shortcuts.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script(
			'graylog-search-regex',
			$url . 'assets/js/regex-helper.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script(
			'graylog-search-query-builder',
			$url . 'assets/js/query-builder.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script(
			'graylog-search-history',
			$url . 'assets/js/search-history.js',
			array( 'jquery' ),
			$version,
			true
		);

		// Localize script.
		wp_localize_script(
			'graylog-search-script',
			'graylogSearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'graylog_search_nonce' ),
			)
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		// Check permissions.
		if ( ! current_user_can( 'search_graylog_logs' ) && ! current_user_can( 'read' ) ) {
			return '<div class="graylog-search-error">' . esc_html__( 'You do not have permission to access this search.', 'graylog-search' ) . '</div>';
		}

		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'title'        => __( 'Graylog Search', 'graylog-search' ),
				'show_filters' => 'yes',
				'result_limit' => '100',
				'time_range'   => 'last_day',
			),
			$atts,
			'graylog_search'
		);

		// Force enqueue assets if not already enqueued.
		if ( ! wp_script_is( 'graylog-search-script', 'enqueued' ) ) {
			$this->enqueue_assets();
		}

		// Get timezone helper.
		$timezone = Timezone::get_instance();

		ob_start();
		?>
		<div class="graylog-search-shortcode">
			<div class="graylog-search-compact-form">
				<?php if ( ! empty( $atts['title'] ) ) : ?>
					<h2 class="graylog-search-title"><?php echo esc_html( $atts['title'] ); ?></h2>
				<?php endif; ?>

				<form id="graylog-search-form" method="post">
					<div class="graylog-form-row">
						<div class="graylog-form-col" style="flex: 2;">
							<label for="search-query"><?php esc_html_e( 'Search Query:', 'graylog-search' ); ?></label>
							<textarea 
								name="search_query" 
								id="search-query" 
								class="graylog-input search-query-input" 
								placeholder="<?php esc_attr_e( 'e.g., server01, error, 192.168.1.1', 'graylog-search' ); ?>"
								rows="2"><?php echo esc_textarea( isset( $_POST['search_query'] ) ? sanitize_textarea_field( wp_unslash( $_POST['search_query'] ) ) : '' ); ?></textarea>
							<small class="description"><?php esc_html_e( 'Multiple terms: one per line or comma-separated', 'graylog-search' ); ?></small>
						</div>
					</div>

					<?php if ( 'yes' === $atts['show_filters'] ) : ?>
					<div class="graylog-form-row">
						<div class="graylog-form-col">
							<label for="filter-out"><?php esc_html_e( 'Filter Out:', 'graylog-search' ); ?></label>
							<textarea 
								name="filter_out" 
								id="filter-out" 
								class="graylog-input" 
								placeholder="<?php esc_attr_e( 'e.g., debug, info', 'graylog-search' ); ?>"
								rows="2"><?php echo esc_textarea( isset( $_POST['filter_out'] ) ? sanitize_textarea_field( wp_unslash( $_POST['filter_out'] ) ) : '' ); ?></textarea>
						</div>
					</div>
					<?php endif; ?>

					<div class="graylog-form-row">
						<div class="graylog-form-col">
							<label for="time-range"><?php esc_html_e( 'Time Range:', 'graylog-search' ); ?></label>
							<select name="time_range" id="time-range" class="graylog-select">
								<option value="last_hour" <?php selected( $atts['time_range'], 'last_hour' ); ?>><?php esc_html_e( 'Last Hour', 'graylog-search' ); ?></option>
								<option value="last_day" <?php selected( $atts['time_range'], 'last_day' ); ?>><?php esc_html_e( 'Last Day', 'graylog-search' ); ?></option>
								<option value="last_week" <?php selected( $atts['time_range'], 'last_week' ); ?>><?php esc_html_e( 'Last Week', 'graylog-search' ); ?></option>
								<option value="last_month" <?php selected( $atts['time_range'], 'last_month' ); ?>><?php esc_html_e( 'Last Month', 'graylog-search' ); ?></option>
								<option value="custom" <?php selected( $atts['time_range'], 'custom' ); ?>><?php esc_html_e( 'Custom', 'graylog-search' ); ?></option>
							</select>
						</div>

						<div class="graylog-form-col">
							<label for="result-limit"><?php esc_html_e( 'Result Limit:', 'graylog-search' ); ?></label>
							<select name="result_limit" id="result-limit" class="graylog-select">
								<option value="50" <?php selected( $atts['result_limit'], '50' ); ?>>50 <?php esc_html_e( 'results', 'graylog-search' ); ?></option>
								<option value="100" <?php selected( $atts['result_limit'], '100' ); ?>>100 <?php esc_html_e( 'results', 'graylog-search' ); ?></option>
								<option value="200" <?php selected( $atts['result_limit'], '200' ); ?>>200 <?php esc_html_e( 'results', 'graylog-search' ); ?></option>
								<option value="500" <?php selected( $atts['result_limit'], '500' ); ?>>500 <?php esc_html_e( 'results', 'graylog-search' ); ?></option>
							</select>
						</div>

						<div class="graylog-form-col">
							<label for="search-mode"><?php esc_html_e( 'Search Mode:', 'graylog-search' ); ?></label>
							<select name="search_mode" id="search-mode" class="graylog-select">
								<option value="simple"><?php esc_html_e( 'Simple Search', 'graylog-search' ); ?></option>
								<option value="regex"><?php esc_html_e( 'Regex Search', 'graylog-search' ); ?></option>
							</select>
						</div>
					</div>

					<div class="graylog-form-row custom-time-range" style="display: none;">
						<div class="graylog-form-col">
							<label for="start-time"><?php esc_html_e( 'Start Time:', 'graylog-search' ); ?></label>
							<input type="datetime-local" name="start_time" id="start-time" class="graylog-input">
						</div>
						<div class="graylog-form-col">
							<label for="end-time"><?php esc_html_e( 'End Time:', 'graylog-search' ); ?></label>
							<input type="datetime-local" name="end_time" id="end-time" class="graylog-input">
						</div>
					</div>

					<div class="graylog-form-row">
						<div class="graylog-form-col">
							<label for="timezone"><?php esc_html_e( 'Timezone:', 'graylog-search' ); ?></label>
							<select name="timezone" id="timezone" class="graylog-select">
								<?php
								$user_timezone    = $timezone->get_user_timezone();
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
						</div>
					</div>

					<div class="graylog-form-actions">
						<button type="submit" id="search-button" class="graylog-btn graylog-btn-primary">
							<?php esc_html_e( 'Search Logs', 'graylog-search' ); ?>
						</button>
						<button type="button" id="clear-button" class="graylog-btn graylog-btn-secondary">
							<?php esc_html_e( 'Clear', 'graylog-search' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div id="search-results" class="graylog-results-container" style="display: none;">
				<div class="results-header">
					<h3><?php esc_html_e( 'Search Results', 'graylog-search' ); ?></h3>
					<div class="results-info">
						<span id="result-count"></span>
						<div class="results-actions">
							<button type="button" id="export-btn" class="graylog-btn graylog-btn-small">
								<?php esc_html_e( 'Export', 'graylog-search' ); ?>
							</button>
						</div>
					</div>
				</div>
				<div id="results-content"></div>
			</div>

			<div id="loading-indicator" style="display: none;">
				<div class="graylog-spinner"></div>
				<p><?php esc_html_e( 'Searching logs...', 'graylog-search' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
