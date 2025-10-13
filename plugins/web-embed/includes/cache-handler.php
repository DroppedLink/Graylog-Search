<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Get cached embed HTML for a URL
 * 
 * @param string $url The URL to retrieve from cache
 * @return string|false Cached HTML or false if not found
 */
function web_embed_get_cache($url) {
    // Check if caching is enabled
    if (get_option('web_embed_cache_enabled', '1') !== '1') {
        return false;
    }
    
    // Generate cache key
    $cache_key = web_embed_get_cache_key($url);
    
    // Get cached value
    $cached = get_transient($cache_key);
    
    return $cached;
}

/**
 * Store embed HTML in cache
 * 
 * @param string $url The URL being cached
 * @param string $html The HTML to cache
 * @return bool True if cached successfully
 */
function web_embed_set_cache($url, $html) {
    // Check if caching is enabled
    if (get_option('web_embed_cache_enabled', '1') !== '1') {
        return false;
    }
    
    // Generate cache key
    $cache_key = web_embed_get_cache_key($url);
    
    // Get cache duration
    $duration = intval(get_option('web_embed_cache_duration', '3600'));
    
    // Store in transient
    return set_transient($cache_key, $html, $duration);
}

/**
 * Clear all cached embeds
 * 
 * @return int Number of cache entries cleared
 */
function web_embed_clear_all_cache() {
    global $wpdb;
    
    // Delete all transients with our prefix
    $prefix = '_transient_web_embed_cache_';
    $timeout_prefix = '_transient_timeout_web_embed_cache_';
    
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%',
            $wpdb->esc_like($timeout_prefix) . '%'
        )
    );
    
    return $deleted;
}

/**
 * Generate a cache key for a URL
 * 
 * @param string $url The URL to generate a key for
 * @return string Cache key
 */
function web_embed_get_cache_key($url) {
    return 'web_embed_cache_' . md5($url);
}

/**
 * Clear cache for a specific URL
 * 
 * @param string $url The URL to clear from cache
 * @return bool True if deleted successfully
 */
function web_embed_clear_url_cache($url) {
    $cache_key = web_embed_get_cache_key($url);
    return delete_transient($cache_key);
}

