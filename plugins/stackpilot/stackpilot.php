<?php
/**
 * Plugin Name: StackPilot
 * Description: Manage Portainer environments and containers (start/stop/restart) with logs from WordPress admin.
 * Version: 1.1.0
 * Author: Stephen White
 * Text Domain: stackpilot
 */

// Prevent direct access
if (!defined('WPINC')) {
	die;
}

// === Constants ===
define('SP_VERSION', '1.1.0');
define('SP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SP_PLUGIN_URL', plugin_dir_url(__FILE__));

// === Includes ===
require_once SP_PLUGIN_DIR . 'includes/helpers.php';
require_once SP_PLUGIN_DIR . 'includes/api-client.php';
require_once SP_PLUGIN_DIR . 'includes/settings.php';
require_once SP_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once SP_PLUGIN_DIR . 'includes/page-dashboard.php';

// === Activation / Deactivation ===
register_activation_hook(__FILE__, 'sp_activate');
function sp_activate() {
	if (get_option('sp_environments', null) === null) { add_option('sp_environments', array()); }
	if (get_option('sp_cache_ttl', null) === null) { add_option('sp_cache_ttl', 30); }
	if (get_option('sp_logs_tail', null) === null) { add_option('sp_logs_tail', 200); }
}

register_deactivation_hook(__FILE__, 'sp_deactivate');
function sp_deactivate() {
	// No action on deactivation; uninstall.php handles cleanup
}

// === i18n ===
add_action('plugins_loaded', 'sp_load_textdomain');
function sp_load_textdomain() {
	load_plugin_textdomain('stackpilot', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// === Assets ===
add_action('admin_enqueue_scripts', 'sp_admin_enqueue_assets');
function sp_admin_enqueue_assets($hook) {
	// Only load on StackPilot pages
	if (strpos($hook, 'stackpilot') === false) {
		return;
	}

	wp_enqueue_style(
		'sp-style',
		SP_PLUGIN_URL . 'assets/css/style.css',
		array(),
		SP_VERSION
	);

	wp_enqueue_script(
		'sp-admin',
		SP_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		SP_VERSION,
		true
	);

	// Primary data object
	wp_localize_script('sp-admin', 'sp', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('sp_nonce'),
		'logsTail' => intval(get_option('sp_logs_tail', 200)),
		'protect' => array(
			'enabled' => (bool) get_option('sp_protect_enabled', 1),
			'patterns' => (array) get_option('sp_protect_patterns', array('portainer','traefik','nginx-proxy','caddy'))
		)
	));
}


