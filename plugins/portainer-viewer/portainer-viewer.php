<?php
/**
 * Plugin Name: Portainer Viewer
 * Description: View Portainer environments and manage containers (start/stop/restart) with logs from WordPress admin.
 * Version: 1.0.0
 * Author: Stephen White
 */

// Prevent direct access
if (!defined('WPINC')) {
	die;
}

// === Constants ===
define('PV_VERSION', '1.0.0');
define('PV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PV_PLUGIN_URL', plugin_dir_url(__FILE__));

// === Includes ===
require_once PV_PLUGIN_DIR . 'includes/helpers.php';
require_once PV_PLUGIN_DIR . 'includes/api-client.php';
require_once PV_PLUGIN_DIR . 'includes/settings.php';
require_once PV_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once PV_PLUGIN_DIR . 'includes/page-dashboard.php';

// === Activation / Deactivation ===
register_activation_hook(__FILE__, 'pv_activate');
function pv_activate() {
	// Initialize default options if not present
	if (get_option('pv_environments', null) === null) {
		add_option('pv_environments', array());
	}
	if (get_option('pv_cache_ttl', null) === null) {
		add_option('pv_cache_ttl', 30);
	}
	if (get_option('pv_logs_tail', null) === null) {
		add_option('pv_logs_tail', 200);
	}
}

register_deactivation_hook(__FILE__, 'pv_deactivate');
function pv_deactivate() {
	// Nothing to clean on deactivation (uninstall.php handles full cleanup)
}

// === Assets ===
add_action('admin_enqueue_scripts', 'pv_admin_enqueue_assets');
function pv_admin_enqueue_assets($hook) {
	// Only load on Portainer Viewer pages
	if (strpos($hook, 'portainer-viewer') === false) {
		return;
	}

	wp_enqueue_style(
		'pv-style',
		PV_PLUGIN_URL . 'assets/css/style.css',
		array(),
		PV_VERSION
	);

	wp_enqueue_script(
		'pv-admin',
		PV_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		PV_VERSION,
		true
	);

	wp_localize_script('pv-admin', 'pv', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('pv_nonce'),
		'logsTail' => intval(get_option('pv_logs_tail', 200))
	));
}


