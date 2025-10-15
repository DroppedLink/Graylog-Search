<?php
/**
 * Plugin Name: Web Embed
 * Plugin URI: https://example.com/web-embed
 * Description: Professional URL embedding with visual builder, security controls, caching, and smart fallback handling for enterprise applications.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: web-embed
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version constant
 */
define('WEB_EMBED_VERSION', '1.0.0');

/**
 * Plugin directory path
 */
define('WEB_EMBED_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL
 */
define('WEB_EMBED_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Include required files
 */
require_once WEB_EMBED_PLUGIN_DIR . 'includes/security.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/cache-handler.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/settings.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/shortcode.php';
require_once WEB_EMBED_PLUGIN_DIR . 'includes/shortcode-builder.php';

/**
 * Plugin activation hook
 * 
 * Sets up default options when the plugin is activated.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_activate() {
    // Set default options (don't overwrite if they exist)
    add_option('web_embed_whitelist_mode', '0');
    add_option('web_embed_allowed_domains', '');
    add_option('web_embed_https_only', '1');
    add_option('web_embed_cache_duration', 3600); // 1 hour
    add_option('web_embed_default_width', '100%');
    add_option('web_embed_default_height', '600px');
    add_option('web_embed_default_responsive', '1');
    add_option('web_embed_custom_css_class', '');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'web_embed_activate');

/**
 * Plugin deactivation hook
 * 
 * Cleanup tasks when the plugin is deactivated.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_deactivate() {
    // Clear scheduled cron jobs
    $timestamp = wp_next_scheduled('web_embed_cache_warm');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'web_embed_cache_warm');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'web_embed_deactivate');

/**
 * Initialize the plugin
 * 
 * Loads text domain for translations.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_init() {
    // Load plugin text domain for translations
    load_plugin_textdomain(
        'web-embed',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'web_embed_init');

/**
 * Enqueue frontend assets
 * 
 * Loads CSS and JavaScript for the frontend only when shortcode is present.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_enqueue_frontend_assets() {
    global $post;
    
    // Only load if shortcode is present in the content
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'web_embed')) {
        // Determine which CSS file to load based on debug mode
        $css_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'style.css' : 'style.min.css';
        
        wp_enqueue_style(
            'web-embed-frontend',
            WEB_EMBED_PLUGIN_URL . 'assets/css/' . $css_file,
            array(),
            WEB_EMBED_VERSION
        );
        
        // Determine which JS file to load
        $js_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'embed.js' : 'embed.min.js';
        
        wp_enqueue_script(
            'web-embed-frontend',
            WEB_EMBED_PLUGIN_URL . 'assets/js/' . $js_file,
            array('jquery'),
            WEB_EMBED_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'web_embed_enqueue_frontend_assets');

