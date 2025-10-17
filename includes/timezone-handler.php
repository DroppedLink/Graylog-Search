<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Get available timezones
function graylog_get_available_timezones() {
    return array(
        'US Timezones' => array(
            'America/New_York' => 'Eastern Time (EST/EDT)',
            'America/Chicago' => 'Central Time (CST/CDT)',
            'America/Denver' => 'Mountain Time (MST/MDT)',
            'America/Phoenix' => 'Arizona Time (MST - No DST)',
            'America/Los_Angeles' => 'Pacific Time (PST/PDT)',
            'America/Anchorage' => 'Alaska Time (AKST/AKDT)',
            'Pacific/Honolulu' => 'Hawaii Time (HST)',
        ),
        'UTC/GMT' => array(
            'UTC' => 'UTC / GMT / Zulu Time',
        ),
        'India' => array(
            'Asia/Kolkata' => 'India Standard Time (IST)',
        ),
    );
}

// AJAX handler to get user's saved timezone preference
add_action('wp_ajax_graylog_get_timezone', 'graylog_get_timezone_handler');
function graylog_get_timezone_handler() {
    // Verify nonce
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $user_id = get_current_user_id();
    $timezone = get_user_meta($user_id, 'graylog_timezone', true);
    
    // Default to UTC if not set
    if (empty($timezone)) {
        $timezone = 'UTC';
    }
    
    wp_send_json_success(array('timezone' => $timezone));
}

// AJAX handler to save user's timezone preference
add_action('wp_ajax_graylog_save_timezone', 'graylog_save_timezone_handler');
function graylog_save_timezone_handler() {
    // Verify nonce
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get timezone from request
    $timezone = sanitize_text_field($_POST['timezone']);
    
    // Validate timezone
    $available_timezones = graylog_get_available_timezones();
    $valid = false;
    foreach ($available_timezones as $group => $zones) {
        if (array_key_exists($timezone, $zones)) {
            $valid = true;
            break;
        }
    }
    
    if (!$valid) {
        wp_send_json_error(array('message' => 'Invalid timezone'));
        return;
    }
    
    // Save to user meta
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'graylog_timezone', $timezone);
    
    wp_send_json_success(array(
        'message' => 'Timezone saved',
        'timezone' => $timezone
    ));
}

