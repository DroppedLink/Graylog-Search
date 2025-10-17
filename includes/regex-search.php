<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Regex Search Functionality
 * Provides regex pattern validation, testing, and common pattern library
 */

// Get common regex patterns
function graylog_get_regex_patterns() {
    return array(
        'IP Address (IPv4)' => array(
            'pattern' => '\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b',
            'description' => 'Matches IPv4 addresses (e.g., 192.168.1.1)',
            'example' => '192.168.1.1, 10.0.0.1'
        ),
        'IP Address (IPv6)' => array(
            'pattern' => '(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}',
            'description' => 'Matches IPv6 addresses',
            'example' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
        ),
        'Email Address' => array(
            'pattern' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}',
            'description' => 'Matches email addresses',
            'example' => 'user@example.com, admin@domain.co.uk'
        ),
        'URL' => array(
            'pattern' => 'https?://[^\s]+',
            'description' => 'Matches HTTP/HTTPS URLs',
            'example' => 'https://example.com, http://site.org/path'
        ),
        'UUID' => array(
            'pattern' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
            'description' => 'Matches UUIDs',
            'example' => '550e8400-e29b-41d4-a716-446655440000'
        ),
        'Date (ISO 8601)' => array(
            'pattern' => '\d{4}-\d{2}-\d{2}',
            'description' => 'Matches ISO date format (YYYY-MM-DD)',
            'example' => '2025-10-15, 2024-12-31'
        ),
        'Time (HH:MM:SS)' => array(
            'pattern' => '\d{2}:\d{2}:\d{2}',
            'description' => 'Matches time in HH:MM:SS format',
            'example' => '14:30:45, 09:15:22'
        ),
        'MAC Address' => array(
            'pattern' => '([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})',
            'description' => 'Matches MAC addresses',
            'example' => '00:1A:2B:3C:4D:5E, 00-1A-2B-3C-4D-5E'
        ),
        'Credit Card' => array(
            'pattern' => '\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b',
            'description' => 'Matches credit card numbers',
            'example' => '1234-5678-9012-3456, 1234 5678 9012 3456'
        ),
        'Phone Number (US)' => array(
            'pattern' => '\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}',
            'description' => 'Matches US phone numbers',
            'example' => '(555) 123-4567, 555-123-4567'
        ),
        'Social Security Number' => array(
            'pattern' => '\b\d{3}-\d{2}-\d{4}\b',
            'description' => 'Matches US SSN format',
            'example' => '123-45-6789'
        ),
        'File Path (Unix)' => array(
            'pattern' => '(?:\/[^\/\s]+)+',
            'description' => 'Matches Unix/Linux file paths',
            'example' => '/var/log/syslog, /home/user/file.txt'
        ),
        'File Path (Windows)' => array(
            'pattern' => '[a-zA-Z]:\\(?:[^\\/:*?"<>|\r\n]+\\)*[^\\/:*?"<>|\r\n]*',
            'description' => 'Matches Windows file paths',
            'example' => 'C:\\Windows\\System32, D:\\Docs\\file.txt'
        ),
        'Error Codes' => array(
            'pattern' => '\b(?:ERROR|FATAL|CRITICAL)\s*:?\s*\d+\b',
            'description' => 'Matches error codes',
            'example' => 'ERROR: 500, FATAL 404'
        ),
        'HTTP Status Codes' => array(
            'pattern' => '\b[1-5]\d{2}\b',
            'description' => 'Matches HTTP status codes',
            'example' => '200, 404, 500'
        ),
        'JSON Object' => array(
            'pattern' => '\{[^}]+\}',
            'description' => 'Matches simple JSON objects',
            'example' => '{"key": "value"}'
        ),
        'Quoted String' => array(
            'pattern' => '"[^"]*"',
            'description' => 'Matches double-quoted strings',
            'example' => '"Hello World", "Test String"'
        ),
        'Word Boundary' => array(
            'pattern' => '\bword\b',
            'description' => 'Matches whole word only (replace "word" with your term)',
            'example' => 'Matches "word" but not "keyword" or "sword"'
        )
    );
}

// Get regex patterns AJAX handler
add_action('wp_ajax_graylog_get_regex_patterns', 'graylog_get_regex_patterns_handler');
function graylog_get_regex_patterns_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    wp_send_json_success(array('patterns' => graylog_get_regex_patterns()));
}

// Validate regex pattern
add_action('wp_ajax_graylog_validate_regex', 'graylog_validate_regex_handler');
function graylog_validate_regex_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $pattern = isset($_POST['pattern']) ? stripslashes($_POST['pattern']) : '';
    
    if (empty($pattern)) {
        wp_send_json_error(array('message' => 'Pattern is required'));
        return;
    }
    
    // Test if the pattern is valid
    $test_result = @preg_match('/' . $pattern . '/', '');
    
    if ($test_result === false) {
        $error = error_get_last();
        wp_send_json_error(array(
            'message' => 'Invalid regex pattern',
            'error' => $error['message'] ?? 'Unknown error'
        ));
        return;
    }
    
    wp_send_json_success(array('message' => 'Valid regex pattern'));
}

// Test regex pattern against sample text
add_action('wp_ajax_graylog_test_regex', 'graylog_test_regex_handler');
function graylog_test_regex_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $pattern = isset($_POST['pattern']) ? stripslashes($_POST['pattern']) : '';
    $test_text = isset($_POST['test_text']) ? stripslashes($_POST['test_text']) : '';
    
    if (empty($pattern)) {
        wp_send_json_error(array('message' => 'Pattern is required'));
        return;
    }
    
    if (empty($test_text)) {
        wp_send_json_error(array('message' => 'Test text is required'));
        return;
    }
    
    // Test the pattern
    $matches = array();
    $result = @preg_match_all('/' . $pattern . '/', $test_text, $matches, PREG_OFFSET_CAPTURE);
    
    if ($result === false) {
        $error = error_get_last();
        wp_send_json_error(array(
            'message' => 'Regex error',
            'error' => $error['message'] ?? 'Unknown error'
        ));
        return;
    }
    
    // Format matches for display
    $formatted_matches = array();
    if (!empty($matches[0])) {
        foreach ($matches[0] as $match) {
            $formatted_matches[] = array(
                'text' => $match[0],
                'position' => $match[1]
            );
        }
    }
    
    wp_send_json_success(array(
        'match_count' => count($formatted_matches),
        'matches' => $formatted_matches
    ));
}

// Save user's custom regex patterns
add_action('wp_ajax_graylog_save_regex_pattern', 'graylog_save_regex_pattern_handler');
function graylog_save_regex_pattern_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $name = sanitize_text_field($_POST['name']);
    $pattern = stripslashes(sanitize_text_field($_POST['pattern']));
    $description = sanitize_text_field($_POST['description']);
    
    if (empty($name) || empty($pattern)) {
        wp_send_json_error(array('message' => 'Name and pattern are required'));
        return;
    }
    
    // Validate pattern
    if (@preg_match('/' . $pattern . '/', '') === false) {
        wp_send_json_error(array('message' => 'Invalid regex pattern'));
        return;
    }
    
    // Get existing custom patterns
    $custom_patterns = get_user_meta(get_current_user_id(), 'graylog_custom_regex_patterns', true);
    if (!is_array($custom_patterns)) {
        $custom_patterns = array();
    }
    
    // Add new pattern
    $custom_patterns[$name] = array(
        'pattern' => $pattern,
        'description' => $description,
        'created' => current_time('mysql')
    );
    
    // Save
    update_user_meta(get_current_user_id(), 'graylog_custom_regex_patterns', $custom_patterns);
    
    wp_send_json_success(array(
        'message' => 'Pattern saved successfully',
        'patterns' => $custom_patterns
    ));
}

// Get user's custom regex patterns
add_action('wp_ajax_graylog_get_custom_regex_patterns', 'graylog_get_custom_regex_patterns_handler');
function graylog_get_custom_regex_patterns_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $custom_patterns = get_user_meta(get_current_user_id(), 'graylog_custom_regex_patterns', true);
    if (!is_array($custom_patterns)) {
        $custom_patterns = array();
    }
    
    wp_send_json_success(array('patterns' => $custom_patterns));
}

// Delete custom regex pattern
add_action('wp_ajax_graylog_delete_regex_pattern', 'graylog_delete_regex_pattern_handler');
function graylog_delete_regex_pattern_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $name = sanitize_text_field($_POST['name']);
    
    // Get existing custom patterns
    $custom_patterns = get_user_meta(get_current_user_id(), 'graylog_custom_regex_patterns', true);
    if (!is_array($custom_patterns)) {
        $custom_patterns = array();
    }
    
    // Remove pattern
    unset($custom_patterns[$name]);
    
    // Save
    update_user_meta(get_current_user_id(), 'graylog_custom_regex_patterns', $custom_patterns);
    
    wp_send_json_success(array(
        'message' => 'Pattern deleted successfully',
        'patterns' => $custom_patterns
    ));
}

