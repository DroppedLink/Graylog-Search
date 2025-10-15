<?php
/**
 * Uninstall script for Web Embed plugin
 * 
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It cleans up all plugin data from the database.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin options
 */
function web_embed_uninstall_remove_options() {
    $options = array(
        'web_embed_whitelist_enabled',
        'web_embed_allowed_domains',
        'web_embed_https_only',
        'web_embed_cache_enabled',
        'web_embed_cache_duration',
        'web_embed_default_width',
        'web_embed_default_height',
        'web_embed_default_responsive',
        'web_embed_custom_css_class',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Clear all transient caches
 */
function web_embed_uninstall_clear_transients() {
    global $wpdb;
    
    // Delete all transients with our prefix
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            $wpdb->esc_like('_transient_web_embed_cache_') . '%',
            $wpdb->esc_like('_transient_timeout_web_embed_cache_') . '%'
        )
    );
}

/**
 * Drop custom database tables (if they exist)
 * Note: These tables will be created in future versions
 */
function web_embed_uninstall_drop_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'web_embed_urls',
        $wpdb->prefix . 'web_embed_analytics',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Remove scheduled cron jobs
 */
function web_embed_uninstall_clear_cron() {
    $timestamp = wp_next_scheduled('web_embed_cache_warm');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'web_embed_cache_warm');
    }
    
    $timestamp = wp_next_scheduled('web_embed_url_health_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'web_embed_url_health_check');
    }
}

/**
 * For multisite: Remove options from all sites
 */
function web_embed_uninstall_multisite_cleanup() {
    global $wpdb;
    
    if (!is_multisite()) {
        return;
    }
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        // Clean up this site
        web_embed_uninstall_remove_options();
        web_embed_uninstall_clear_transients();
        web_embed_uninstall_drop_tables();
        web_embed_uninstall_clear_cron();
        
        restore_current_blog();
    }
    
    // Clean up network options
    delete_site_option('web_embed_network_settings');
}

// Execute cleanup
if (is_multisite()) {
    web_embed_uninstall_multisite_cleanup();
} else {
    web_embed_uninstall_remove_options();
    web_embed_uninstall_clear_transients();
    web_embed_uninstall_drop_tables();
    web_embed_uninstall_clear_cron();
}

// Log uninstall (optional, for debugging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Web Embed: Plugin uninstalled and data cleaned up');
}

