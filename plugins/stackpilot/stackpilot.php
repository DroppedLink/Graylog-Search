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

// === Constants (new + legacy for BC) ===
define('SP_VERSION', '1.1.0');
define('SP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Legacy constants retained for BC with internal modules (if any reference PV_)
if (!defined('PV_VERSION')) define('PV_VERSION', SP_VERSION);
if (!defined('PV_PLUGIN_DIR')) define('PV_PLUGIN_DIR', SP_PLUGIN_DIR);
if (!defined('PV_PLUGIN_URL')) define('PV_PLUGIN_URL', SP_PLUGIN_URL);

// === Includes ===
require_once SP_PLUGIN_DIR . 'includes/helpers.php';
require_once SP_PLUGIN_DIR . 'includes/api-client.php';
require_once SP_PLUGIN_DIR . 'includes/settings.php';
require_once SP_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once SP_PLUGIN_DIR . 'includes/page-dashboard.php';

// === Activation / Deactivation ===
register_activation_hook(__FILE__, 'sp_activate');
function sp_activate() {
	// Option migration: copy pv_* to sp_* if not set
	if (get_option('sp_environments', null) === null) {
		$pv = get_option('pv_environments', array());
		add_option('sp_environments', $pv);
	}
	if (get_option('sp_cache_ttl', null) === null) {
		$pv = get_option('pv_cache_ttl', 30);
		add_option('sp_cache_ttl', $pv);
	}
	if (get_option('sp_logs_tail', null) === null) {
		$pv = get_option('pv_logs_tail', 200);
		add_option('sp_logs_tail', $pv);
	}
}

register_deactivation_hook(__FILE__, 'sp_deactivate');
function sp_deactivate() {
	// No action on deactivation; uninstall.php handles cleanup
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

	// Primary data object (new)
	wp_localize_script('sp-admin', 'sp', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('sp_nonce'),
		'logsTail' => intval(get_option('sp_logs_tail', get_option('pv_logs_tail', 200)))
	));

	// Legacy global for BC with existing JS (pv)
	wp_localize_script('sp-admin', 'pv', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('pv_nonce'),
		'logsTail' => intval(get_option('pv_logs_tail', get_option('sp_logs_tail', 200)))
	));
}


