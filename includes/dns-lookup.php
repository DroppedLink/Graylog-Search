<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// AJAX handler for DNS lookup
add_action('wp_ajax_graylog_dns_lookup', 'graylog_dns_lookup_handler');
function graylog_dns_lookup_handler() {
    // Verify nonce
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get IP address
    $ip = sanitize_text_field($_POST['ip']);
    
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        wp_send_json_error(array('message' => 'Invalid IP address'));
        return;
    }
    
    // Perform DNS lookup with timeout
    $hostname = @gethostbyaddr($ip);
    
    // Check if resolution was successful
    if ($hostname && $hostname !== $ip) {
        // Success - got a hostname
        wp_send_json_success(array(
            'hostname' => $hostname,
            'ip' => $ip
        ));
    } else {
        // Failed to resolve
        wp_send_json_error(array(
            'message' => 'Could not resolve IP address',
            'ip' => $ip
        ));
    }
}

// Also add handler for non-logged-in users (if using shortcode on public page)
add_action('wp_ajax_nopriv_graylog_dns_lookup', 'graylog_dns_lookup_handler_public');
function graylog_dns_lookup_handler_public() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }
    
    // Call the main handler
    graylog_dns_lookup_handler();
}

