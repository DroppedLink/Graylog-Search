<?php
/**
 * Cache handling for Web Embed plugin
 * 
 * Implements caching using WordPress transients with optional object cache support.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Get cached embed HTML
 * 
 * Retrieves cached HTML for an embed if available.
 *
 * @since 1.0.0
 * @param string $cache_key Unique cache key for this embed.
 * @return string|false Cached HTML or false if not found.
 */
function web_embed_get_cache($cache_key) {
    // Check if object cache is available
    if (wp_using_ext_object_cache()) {
        $cached = wp_cache_get($cache_key, 'web_embed');
        if ($cached !== false) {
            return $cached;
        }
    }
    
    // Fall back to transients
    return get_transient($cache_key);
}

/**
 * Set cache for embed HTML
 * 
 * Stores embed HTML in cache with expiration.
 *
 * @since 1.0.0
 * @param string $cache_key   Unique cache key for this embed.
 * @param string $html        HTML to cache.
 * @param int    $expiration  Cache expiration in seconds.
 * @return bool True on success, false on failure.
 */
function web_embed_set_cache($cache_key, $html, $expiration = null) {
    if ($expiration === null) {
        $expiration = absint(get_option('web_embed_cache_duration', 3600));
    }
    
    // Set in object cache if available
    if (wp_using_ext_object_cache()) {
        wp_cache_set($cache_key, $html, 'web_embed', $expiration);
    }
    
    // Always set in transients as fallback
    return set_transient($cache_key, $html, $expiration);
}

/**
 * Generate cache key for embed
 * 
 * Creates a unique cache key based on embed attributes.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string Cache key.
 */
function web_embed_generate_cache_key($atts) {
    // Create unique key from attributes
    ksort($atts);
    $key_string = serialize($atts);
    return 'web_embed_cache_' . md5($key_string);
}

/**
 * Clear all Web Embed caches
 * 
 * Removes all cached embed HTML.
 *
 * @since 1.0.0
 * @return int Number of caches cleared.
 */
function web_embed_clear_all_cache() {
    global $wpdb;
    
    // Clear object cache group if available
    if (wp_using_ext_object_cache()) {
        wp_cache_flush_group('web_embed');
    }
    
    // Clear transients
    $count = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            $wpdb->esc_like('_transient_web_embed_cache_') . '%',
            $wpdb->esc_like('_transient_timeout_web_embed_cache_') . '%'
        )
    );
    
    return absint($count / 2); // Divide by 2 because each transient has a timeout
}

/**
 * Clear cache for a specific URL
 * 
 * Removes cached HTML for a specific URL.
 *
 * @since 1.0.0
 * @param string $url The URL to clear cache for.
 * @return bool True on success, false on failure.
 */
function web_embed_clear_url_cache($url) {
    global $wpdb;
    
    $url_hash = md5($url);
    
    // Clear from object cache if available
    if (wp_using_ext_object_cache()) {
        // We can't easily pattern-match in object cache, so this is limited
        // This would require iterating which isn't efficient
    }
    
    // Clear from transients (pattern match on URL hash)
    $count = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE (option_name LIKE %s OR option_name LIKE %s) 
            AND option_value LIKE %s",
            $wpdb->esc_like('_transient_web_embed_cache_') . '%',
            $wpdb->esc_like('_transient_timeout_web_embed_cache_') . '%',
            '%' . $wpdb->esc_like($url) . '%'
        )
    );
    
    return $count > 0;
}

/**
 * Get cache statistics
 * 
 * Returns information about cache usage.
 *
 * @since 1.0.0
 * @return array {
 *     Cache statistics.
 *     
 *     @type int    $total_cached Number of cached items.
 *     @type string $cache_size   Human-readable cache size.
 *     @type bool   $object_cache Whether object cache is available.
 * }
 */
function web_embed_get_cache_stats() {
    global $wpdb;
    
    $stats = array(
        'total_cached' => 0,
        'cache_size' => '0 KB',
        'object_cache' => wp_using_ext_object_cache(),
    );
    
    // Count transients
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_web_embed_cache_') . '%'
        )
    );
    
    $stats['total_cached'] = absint($count);
    
    // Get approximate size
    $size = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
            WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_web_embed_cache_') . '%'
        )
    );
    
    if ($size) {
        $stats['cache_size'] = size_format($size);
    }
    
    return $stats;
}

