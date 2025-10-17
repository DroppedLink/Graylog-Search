<?php
/**
 * Main Plugin Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class - Singleton
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private to enforce singleton.
	 */
	private function __construct() {
		$this->plugin_dir = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url = plugin_dir_url( dirname( __FILE__ ) );

		$this->define_constants();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		if ( ! defined( 'GRAYLOG_SEARCH_VERSION' ) ) {
			define( 'GRAYLOG_SEARCH_VERSION', self::VERSION );
		}
		if ( ! defined( 'GRAYLOG_SEARCH_PLUGIN_DIR' ) ) {
			define( 'GRAYLOG_SEARCH_PLUGIN_DIR', $this->plugin_dir );
		}
		if ( ! defined( 'GRAYLOG_SEARCH_PLUGIN_URL' ) ) {
			define( 'GRAYLOG_SEARCH_PLUGIN_URL', $this->plugin_url );
		}
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		// Admin classes.
		require_once $this->plugin_dir . 'src/Admin/Settings.php';
		require_once $this->plugin_dir . 'src/Admin/SearchPage.php';
		require_once $this->plugin_dir . 'src/Admin/Assets.php';

		// API classes.
		require_once $this->plugin_dir . 'src/API/GraylogClient.php';
		require_once $this->plugin_dir . 'src/API/QueryBuilder.php';

		// AJAX handlers.
		require_once $this->plugin_dir . 'src/Ajax/SearchHandler.php';
		require_once $this->plugin_dir . 'src/Ajax/DNSLookup.php';
		require_once $this->plugin_dir . 'src/Ajax/SavedSearches.php';

		// Frontend.
		require_once $this->plugin_dir . 'src/Frontend/Shortcode.php';

		// Updater.
		require_once $this->plugin_dir . 'src/Updater/GitHubUpdater.php';

		// Helpers.
		require_once $this->plugin_dir . 'src/Helpers/Security.php';
		require_once $this->plugin_dir . 'src/Helpers/Timezone.php';

		// Load legacy includes for backward compatibility (will be migrated).
		$legacy_includes = array(
			'regex-search.php',
			'field-manager.php',
			'search-history.php',
			'export-pdf.php',
		);

		foreach ( $legacy_includes as $file ) {
			$file_path = $this->plugin_dir . 'includes/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Load text domain for translations (init action required in WP 6.7+).
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init_components' ) );

		// Activation/Deactivation hooks.
		register_activation_hook( $this->plugin_dir . 'graylog-search.php', array( $this, 'activate' ) );
		register_deactivation_hook( $this->plugin_dir . 'graylog-search.php', array( $this, 'deactivate' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'graylog-search',
			false,
			dirname( plugin_basename( $this->plugin_dir . 'graylog-search.php' ) ) . '/languages'
		);
	}

	/**
	 * Initialize plugin components.
	 */
	public function init_components() {
		// Initialize admin components.
		if ( is_admin() ) {
			Admin\Settings::get_instance();
			Admin\SearchPage::get_instance();
			Admin\Assets::get_instance();
			Updater\GitHubUpdater::get_instance();
		}

		// Initialize AJAX handlers (work for both admin and frontend).
		Ajax\SearchHandler::get_instance();
		Ajax\DNSLookup::get_instance();
		Ajax\SavedSearches::get_instance();

		// Initialize frontend components.
		Frontend\Shortcode::get_instance();
	}

	/**
	 * Plugin activation callback.
	 */
	public function activate() {
		// Set default options if they don't exist.
		if ( false === get_option( 'graylog_api_url' ) ) {
			add_option( 'graylog_api_url', '' );
		}
		if ( false === get_option( 'graylog_api_token' ) ) {
			add_option( 'graylog_api_token', '' );
		}
		if ( false === get_option( 'graylog_search_disable_ssl_verify' ) ) {
			add_option( 'graylog_search_disable_ssl_verify', '0' );
		}
		if ( false === get_option( 'graylog_search_github_token' ) ) {
			add_option( 'graylog_search_github_token', '' );
		}

		// Add custom capability to administrator role.
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'search_graylog_logs' );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public function deactivate() {
		// Clear any cached data.
		$this->clear_caches();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear all plugin caches.
	 */
	private function clear_caches() {
		global $wpdb;

		// Clear all transients related to this plugin.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%graylog_search_%'
			)
		);
	}

	/**
	 * Get plugin directory path.
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}
}

