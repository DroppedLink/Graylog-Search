<?php
/**
 * Plugin Name: Graylog Search
 * Plugin URI: https://github.com/DroppedLink/Graylog-Search
 * Description: Simple interface for non-technical users to search Graylog logs via API
 * Version: 1.0.7
 * Author: DroppedLink
 * Author URI: https://github.com/DroppedLink
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: graylog-search
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Update URI: https://github.com/DroppedLink/Graylog-Search
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('GRAYLOG_SEARCH_VERSION', '1.0.7');
define('GRAYLOG_SEARCH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GRAYLOG_SEARCH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/settings.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/search-page.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/shortcode.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/dns-lookup.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/timezone-handler.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/github-updater.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/saved-searches.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/regex-search.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/field-manager.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/search-history.php';
require_once GRAYLOG_SEARCH_PLUGIN_DIR . 'includes/export-pdf.php';

// Activation hook
register_activation_hook(__FILE__, 'graylog_search_activate');
function graylog_search_activate() {
    // Set default options
    add_option('graylog_api_url', '');
    add_option('graylog_api_token', '');
    add_option('graylog_search_disable_ssl_verify', '0');
    add_option('graylog_search_github_token', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'graylog_search_deactivate');
function graylog_search_deactivate() {
    // Cleanup if needed
}

// Initialize the plugin
add_action('plugins_loaded', 'graylog_search_init');
function graylog_search_init() {
    // Plugin initialization code
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'graylog_search_enqueue_assets');
function graylog_search_enqueue_assets($hook) {
    // Only load on our plugin pages
    if ($hook !== 'toplevel_page_graylog-search' && $hook !== 'graylog-search_page_graylog-search-settings') {
        return;
    }
    
    graylog_search_enqueue_common_assets();
}

// Also enqueue on frontend for shortcode
add_action('wp_enqueue_scripts', 'graylog_search_enqueue_common_assets');

// Common asset enqueuing function
function graylog_search_enqueue_common_assets() {
    // Enqueue CSS
    wp_enqueue_style(
        'graylog-search-style',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/style.css',
        array(),
        GRAYLOG_SEARCH_VERSION
    );
    
    // Enqueue Query Builder CSS
    wp_enqueue_style(
        'graylog-search-query-builder',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/query-builder.css',
        array('graylog-search-style'),
        GRAYLOG_SEARCH_VERSION
    );
    
    // Enqueue main JavaScript
    wp_enqueue_script(
        'graylog-search-script',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Enqueue keyboard shortcuts
    wp_enqueue_script(
        'graylog-search-keyboard',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/keyboard-shortcuts.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Enqueue regex helper
    wp_enqueue_script(
        'graylog-search-regex',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/regex-helper.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Enqueue query builder
    wp_enqueue_script(
        'graylog-search-query-builder',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/query-builder.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Enqueue search history CSS
    wp_enqueue_style(
        'graylog-search-history',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/search-history.css',
        array('graylog-search-style'),
        GRAYLOG_SEARCH_VERSION
    );
    
    // Enqueue search history
    wp_enqueue_script(
        'graylog-search-history',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search-history.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Pass AJAX URL to JavaScript
    wp_localize_script('graylog-search-script', 'graylogSearch', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('graylog_search_nonce')
    ));
}
