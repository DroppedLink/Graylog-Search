<?php
/**
 * Plugin Name: Graylog Search
 * Description: Simple interface for non-technical users to search Graylog logs via API
 * Version: 1.6.1
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('GRAYLOG_SEARCH_VERSION', '1.6.1');
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
    
    // Enqueue CSS
    wp_enqueue_style(
        'graylog-search-style',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/style.css',
        array(),
        GRAYLOG_SEARCH_VERSION
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'graylog-search-script',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search.js',
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
