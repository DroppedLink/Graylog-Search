<?php
/**
 * Security functions for Web Embed plugin
 * 
 * Handles URL validation, sanitization, and whitelist enforcement.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Validate and sanitize a URL
 * 
 * Checks if a URL is valid and optionally enforces HTTPS and whitelist rules.
 *
 * @since 1.0.0
 * @param string $url The URL to validate.
 * @return array {
 *     Validation result array.
 *     
 *     @type bool   $valid   Whether the URL is valid.
 *     @type string $url     The sanitized URL.
 *     @type string $message Error message if invalid.
 * }
 */
function web_embed_validate_url($url) {
    // Sanitize the URL
    $url = esc_url_raw($url);
    
    // Check if URL is empty
    if (empty($url)) {
        return array(
            'valid' => false,
            'url' => '',
            'message' => __('URL cannot be empty.', 'web-embed')
        );
    }
    
    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return array(
            'valid' => false,
            'url' => $url,
            'message' => __('Invalid URL format.', 'web-embed')
        );
    }
    
    // Parse URL
    $parsed = parse_url($url);
    
    if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return array(
            'valid' => false,
            'url' => $url,
            'message' => __('Invalid URL structure.', 'web-embed')
        );
    }
    
    // Check HTTPS requirement
    $https_only = get_option('web_embed_https_only', '1');
    if ($https_only === '1' && $parsed['scheme'] !== 'https') {
        return array(
            'valid' => false,
            'url' => $url,
            'message' => __('Only HTTPS URLs are allowed. Enable HTTP in settings if needed.', 'web-embed')
        );
    }
    
    // Check whitelist if enabled
    $whitelist_mode = get_option('web_embed_whitelist_mode', '0');
    if ($whitelist_mode === '1') {
        $allowed = web_embed_check_whitelist($parsed['host']);
        if (!$allowed) {
            return array(
                'valid' => false,
                'url' => $url,
                'message' => sprintf(
                    /* translators: %s: domain name */
                    __('Domain "%s" is not in the whitelist. Add it in settings.', 'web-embed'),
                    $parsed['host']
                )
            );
        }
    }
    
    return array(
        'valid' => true,
        'url' => $url,
        'message' => __('URL is valid.', 'web-embed')
    );
}

/**
 * Check if a domain is in the whitelist
 * 
 * Compares the domain against the list of allowed domains.
 *
 * @since 1.0.0
 * @param string $domain The domain to check.
 * @return bool True if domain is allowed, false otherwise.
 */
function web_embed_check_whitelist($domain) {
    $allowed_domains = get_option('web_embed_allowed_domains', '');
    
    if (empty($allowed_domains)) {
        return false;
    }
    
    // Split domains by newline or comma
    $domains = preg_split('/[\r\n,]+/', $allowed_domains);
    $domains = array_map('trim', $domains);
    $domains = array_filter($domains);
    
    foreach ($domains as $allowed_domain) {
        // Exact match
        if (strtolower($domain) === strtolower($allowed_domain)) {
            return true;
        }
        
        // Wildcard subdomain match (*.example.com)
        if (strpos($allowed_domain, '*.') === 0) {
            $pattern = str_replace('*.', '', $allowed_domain);
            if (substr(strtolower($domain), -strlen($pattern)) === strtolower($pattern)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Sanitize embed attributes
 * 
 * Ensures all shortcode attributes are properly sanitized.
 *
 * @since 1.0.0
 * @param array $atts Raw attributes from shortcode.
 * @return array Sanitized attributes.
 */
function web_embed_sanitize_attributes($atts) {
    $sanitized = array();
    
    // Sanitize URL
    if (isset($atts['url'])) {
        $sanitized['url'] = esc_url_raw($atts['url']);
    }
    
    // Sanitize dimensions
    if (isset($atts['width'])) {
        $sanitized['width'] = web_embed_sanitize_dimension($atts['width']);
    }
    if (isset($atts['height'])) {
        $sanitized['height'] = web_embed_sanitize_dimension($atts['height']);
    }
    
    // Sanitize boolean values
    $booleans = array('responsive', 'loading');
    foreach ($booleans as $key) {
        if (isset($atts[$key])) {
            $sanitized[$key] = in_array($atts[$key], array('true', '1', 'yes')) ? 'true' : 'false';
        }
    }
    
    // Sanitize text fields
    if (isset($atts['title'])) {
        $sanitized['title'] = sanitize_text_field($atts['title']);
    }
    if (isset($atts['class'])) {
        $sanitized['class'] = sanitize_html_class($atts['class']);
    }
    
    // Sanitize CSS values
    if (isset($atts['border'])) {
        $sanitized['border'] = wp_strip_all_tags($atts['border']);
    }
    if (isset($atts['border_radius'])) {
        $sanitized['border_radius'] = wp_strip_all_tags($atts['border_radius']);
    }
    
    // Sanitize HTML fallback (allow safe HTML)
    if (isset($atts['fallback'])) {
        $sanitized['fallback'] = wp_kses_post($atts['fallback']);
    }
    
    return $sanitized;
}

/**
 * Sanitize dimension value
 * 
 * Ensures dimension values are safe (px, %, em, rem, etc.).
 *
 * @since 1.0.0
 * @param string $dimension The dimension value.
 * @return string Sanitized dimension.
 */
function web_embed_sanitize_dimension($dimension) {
    // Allow percentage, pixels, and other CSS units
    if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem|vw|vh)?$/i', $dimension, $matches)) {
        return $matches[1] . ($matches[2] ?? 'px');
    }
    
    // Return as-is if it looks safe, otherwise return default
    return preg_match('/^[\d\s.%a-z-]+$/i', $dimension) ? $dimension : '100%';
}

/**
 * Check user capability for specific action
 * 
 * Determines if current user has permission for an action.
 *
 * @since 1.0.0
 * @param string $action The action to check (builder, settings, cache).
 * @return bool True if user has permission, false otherwise.
 */
function web_embed_user_can($action) {
    switch ($action) {
        case 'builder':
            // Who can use the builder
            return current_user_can('edit_posts');
            
        case 'settings':
            // Who can modify settings
            return current_user_can('manage_options');
            
        case 'cache':
            // Who can clear cache
            return current_user_can('manage_options');
            
        default:
            return false;
    }
}

