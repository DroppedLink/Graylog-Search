<?php
/**
 * Admin Assets Management Class
 *
 * @package GraylogSearch
 */

namespace GraylogSearch\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Assets Management Class
 */
class Assets {

	/**
	 * Class instance.
	 *
	 * @var Assets
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Assets
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages.
		$allowed_hooks = array(
			'toplevel_page_graylog-search',
			'graylog-search_page_graylog-search-settings',
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		$this->enqueue_common_assets();
	}

	/**
	 * Enqueue common assets (used by both admin and shortcode).
	 */
	public function enqueue_common_assets() {
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

		// Enqueue admin settings script if on settings page.
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'graylog-search-settings' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script(
				'graylog-search-admin-settings',
				$url . 'assets/js/admin-settings.js',
				array( 'jquery' ),
				$version,
				true
			);
		}

		// Pass AJAX URL and nonce to JavaScript.
		wp_localize_script(
			'graylog-search-script',
			'graylogSearch',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'graylog_search_nonce' ),
			)
		);
	}
}
