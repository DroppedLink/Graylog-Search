<?php
/**
 * Rate limiting for Web Embed plugin
 * 
 * Prevents abuse of AJAX endpoints and resource-intensive operations.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Check if user has exceeded rate limit
 * 
 * Uses transients to track request counts per user per action.
 *
 * @since 1.0.0
 * @param string $action  The action being rate limited.
 * @param int    $limit   Maximum requests allowed.
 * @param int    $window  Time window in seconds.
 * @return array {
 *     Rate limit check result.
 *     
 *     @type bool   $allowed   Whether request is allowed.
 *     @type int    $remaining Requests remaining.
 *     @type int    $reset_in  Seconds until limit resets.
 * }
 */
function web_embed_check_rate_limit($action, $limit, $window) {
    $user_id = get_current_user_id();
    
    // Admins bypass rate limiting
    if (current_user_can('manage_options')) {
        return array(
            'allowed' => true,
            'remaining' => $limit,
            'reset_in' => 0
        );
    }
    
    // Generate unique key for this user and action
    $key = 'web_embed_rate_limit_' . $action . '_' . $user_id;
    
    // Get current count
    $data = get_transient($key);
    
    if ($data === false) {
        // First request in this window
        $data = array(
            'count' => 1,
            'first_request' => time()
        );
        set_transient($key, $data, $window);
        
        return array(
            'allowed' => true,
            'remaining' => $limit - 1,
            'reset_in' => $window
        );
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $limit) {
        $reset_in = $window - (time() - $data['first_request']);
        
        return array(
            'allowed' => false,
            'remaining' => 0,
            'reset_in' => max(0, $reset_in)
        );
    }
    
    // Increment count
    $data['count']++;
    set_transient($key, $data, $window);
    
    return array(
        'allowed' => true,
        'remaining' => $limit - $data['count'],
        'reset_in' => $window - (time() - $data['first_request'])
    );
}

/**
 * Enforce rate limit and send error if exceeded
 * 
 * Call this at the start of rate-limited operations.
 *
 * @since 1.0.0
 * @param string $action The action being rate limited.
 * @param int    $limit  Maximum requests allowed.
 * @param int    $window Time window in seconds.
 * @return bool True if allowed, exits with error if not.
 */
function web_embed_enforce_rate_limit($action, $limit, $window) {
    $check = web_embed_check_rate_limit($action, $limit, $window);
    
    if (!$check['allowed']) {
        $minutes = ceil($check['reset_in'] / 60);
        
        wp_send_json_error(array(
            'message' => sprintf(
                /* translators: %d: minutes until rate limit resets */
                __('Rate limit exceeded. Please wait %d minutes before trying again.', 'web-embed'),
                $minutes
            ),
            'rate_limit' => $check
        ), 429);
    }
    
    return true;
}

/**
 * Get rate limit status for display
 * 
 * Shows current usage for an action.
 *
 * @since 1.0.0
 * @param string $action The action to check.
 * @param int    $limit  Maximum requests allowed.
 * @param int    $window Time window in seconds.
 * @return array Rate limit status.
 */
function web_embed_get_rate_limit_status($action, $limit, $window) {
    return web_embed_check_rate_limit($action, $limit, $window);
}

