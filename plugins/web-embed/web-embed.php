<?php
/**
 * Plugin Name: Web Embed
 * Description: Embed external URLs into WordPress pages using modern object/embed tags with advanced security and caching
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('WEB_EMBED_VERSION', '1.0.0');
define('WEB_EMBED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEB_EMBED_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WEB_EMBED_PLUGIN_DIR . 'includes/security.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/cache-handler.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/settings.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/shortcode.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/shortcode-builder.php';

// Activation hook
register_activation_hook(__FILE__, 'web_embed_activate');
function web_embed_activate() {
    // Set default options
    add_option('web_embed_whitelist_enabled', '0');
    add_option('web_embed_allowed_domains', '');
    add_option('web_embed_https_only', '0');
    add_option('web_embed_cache_enabled', '1');
    add_option('web_embed_cache_duration', '3600');
    add_option('web_embed_default_width', '100%');
    add_option('web_embed_default_height', '600px');
    add_option('web_embed_default_responsive', '1');
    add_option('web_embed_custom_css_class', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'web_embed_deactivate');
function web_embed_deactivate() {
    // Clear all cached embeds
    web_embed_clear_all_cache();
}

// Initialize the plugin
add_action('plugins_loaded', 'web_embed_init');
function web_embed_init() {
    // Plugin initialization code
}

// Admin assets are now enqueued in shortcode-builder.php

