<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// AJAX handler for log search
add_action('wp_ajax_graylog_search_logs', 'graylog_search_logs_handler');
function graylog_search_logs_handler() {
    // Log the start
    error_log('Graylog Search: AJAX handler called');
    
    // Verify nonce
    check_ajax_referer('graylog_search_nonce', 'nonce');
    error_log('Graylog Search: Nonce verified');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        error_log('Graylog Search: Permission check failed');
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    error_log('Graylog Search: Permission check passed');
    
    // Get search parameters
    $fqdn = sanitize_text_field($_POST['fqdn']);
    $search_terms = sanitize_text_field($_POST['search_terms']);
    $filter_out = sanitize_text_field($_POST['filter_out']);
    $time_range = intval($_POST['time_range']);
    $limit = intval($_POST['limit']);
    
    // Build Graylog query
    $query = graylog_build_query($fqdn, $search_terms, $filter_out);
    error_log('Graylog Search: Query built: ' . $query);
    
    // Get API settings
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    
    if (empty($api_url) || empty($api_token)) {
        error_log('Graylog Search: API not configured');
        wp_send_json_error(array('message' => 'Graylog API not configured'));
        return;
    }
    
    error_log('Graylog Search: Making API request');
    // Make API request
    $results = graylog_api_search($api_url, $api_token, $query, $time_range, $limit);
    
    if (is_wp_error($results)) {
        error_log('Graylog Search: API error: ' . $results->get_error_message());
        wp_send_json_error(array('message' => $results->get_error_message()));
        return;
    }
    
    error_log('Graylog Search: Success - ' . count($results['messages'] ?? []) . ' messages');
    wp_send_json_success($results);
}

// Build Graylog search query
function graylog_build_query($fqdn, $search_terms, $filter_out) {
    $query_parts = array();
    
    // Add FQDN search
    if (!empty($fqdn)) {
        // Only add quotes if the value contains spaces or special characters
        if (preg_match('/\s/', $fqdn)) {
            $query_parts[] = 'source:"' . $fqdn . '"';
        } else {
            $query_parts[] = 'source:' . $fqdn;
        }
    }
    
    // Add additional search terms
    if (!empty($search_terms)) {
        $terms = explode(' ', $search_terms);
        foreach ($terms as $term) {
            $term = trim($term);
            if (!empty($term)) {
                $query_parts[] = $term;
            }
        }
    }
    
    // Add filter out terms (NOT)
    if (!empty($filter_out)) {
        $filters = explode(' ', $filter_out);
        foreach ($filters as $filter) {
            $filter = trim($filter);
            if (!empty($filter)) {
                $query_parts[] = 'NOT ' . $filter;
            }
        }
    }
    
    // If no query parts, search for everything
    if (empty($query_parts)) {
        return '*';
    }
    
    return implode(' AND ', $query_parts);
}

// Make Graylog API search request
function graylog_api_search($api_url, $api_token, $query, $time_range, $limit) {
    // Clean up API URL
    $api_url = rtrim($api_url, '/');
    
    // Ensure /api is in the path
    if (!preg_match('/\/api$/', $api_url)) {
        $api_url .= '/api';
    }
    
    // Build search endpoint
    $endpoint = $api_url . '/search/universal/relative';
    
    // Build query parameters
    $params = array(
        'query' => $query,
        'range' => $time_range,
        'limit' => $limit,
        'sort' => 'timestamp:desc'
    );
    
    $url = add_query_arg($params, $endpoint);
    
    // Make API request
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_token . ':token'),
            'Accept' => 'application/json'
        ),
        'timeout' => 30
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error('api_error', 'Graylog API returned status code: ' . $status_code);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Failed to parse Graylog API response');
    }
    
    return $data;
}

