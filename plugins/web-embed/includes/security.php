<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Validate a URL for embedding
 * 
 * @param string $url The URL to validate
 * @return array Array with 'valid' boolean and 'error' message if invalid
 */
function web_embed_validate_url($url) {
    $result = array('valid' => false, 'error' => '');
    
    // Check if URL is empty
    if (empty($url)) {
        $result['error'] = 'URL is required';
        return $result;
    }
    
    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $result['error'] = 'Invalid URL format';
        return $result;
    }
    
    // Parse URL
    $parsed_url = parse_url($url);
    
    // Check if scheme is present
    if (!isset($parsed_url['scheme'])) {
        $result['error'] = 'URL must include protocol (http:// or https://)';
        return $result;
    }
    
    // Check if HTTPS-only mode is enabled
    if (web_embed_is_https_only() && $parsed_url['scheme'] !== 'https') {
        $result['error'] = 'Only HTTPS URLs are allowed';
        return $result;
    }
    
    // Check against whitelist if enabled
    if (get_option('web_embed_whitelist_enabled', '0') === '1') {
        $whitelist_check = web_embed_check_whitelist($url);
        if (!$whitelist_check['allowed']) {
            $result['error'] = 'URL domain is not in the allowed list';
            return $result;
        }
    }
    
    $result['valid'] = true;
    return $result;
}

/**
 * Check if a URL is in the whitelist
 * 
 * @param string $url The URL to check
 * @return array Array with 'allowed' boolean
 */
function web_embed_check_whitelist($url) {
    $result = array('allowed' => false);
    
    // Get allowed domains
    $allowed_domains = web_embed_get_allowed_domains();
    
    // If no domains are configured, deny by default
    if (empty($allowed_domains)) {
        return $result;
    }
    
    // Parse URL to get host
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['host'])) {
        return $result;
    }
    
    $url_host = strtolower($parsed_url['host']);
    
    // Check if host matches any allowed domain
    foreach ($allowed_domains as $domain) {
        $domain = strtolower(trim($domain));
        
        // Exact match
        if ($url_host === $domain) {
            $result['allowed'] = true;
            return $result;
        }
        
        // Subdomain match (e.g., domain.com allows sub.domain.com)
        if (substr($url_host, -strlen($domain) - 1) === '.' . $domain) {
            $result['allowed'] = true;
            return $result;
        }
    }
    
    return $result;
}

/**
 * Check if HTTPS-only mode is enabled
 * 
 * @return bool
 */
function web_embed_is_https_only() {
    return get_option('web_embed_https_only', '0') === '1';
}

/**
 * Get the list of allowed domains
 * 
 * @return array Array of allowed domain strings
 */
function web_embed_get_allowed_domains() {
    $domains_text = get_option('web_embed_allowed_domains', '');
    return web_embed_sanitize_domain_list($domains_text);
}

/**
 * Parse and sanitize domain list from settings
 * 
 * @param string $domains_text Text containing domains (one per line)
 * @return array Array of sanitized domains
 */
function web_embed_sanitize_domain_list($domains_text) {
    if (empty($domains_text)) {
        return array();
    }
    
    // Split by newlines
    $domains = explode("\n", $domains_text);
    
    // Clean up each domain
    $sanitized = array();
    foreach ($domains as $domain) {
        $domain = trim($domain);
        
        // Skip empty lines
        if (empty($domain)) {
            continue;
        }
        
        // Remove protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Remove path if present
        $domain = parse_url('http://' . $domain, PHP_URL_HOST);
        
        if (!empty($domain)) {
            $sanitized[] = $domain;
        }
    }
    
    return $sanitized;
}

