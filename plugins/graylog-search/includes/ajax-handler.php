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

// Parse multi-value input (newlines, commas, or spaces)
function graylog_parse_multivalue_input($input) {
    $values = array();
    
    // First split by newlines
    $lines = preg_split('/\r\n|\r|\n/', $input);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        // Check if line contains commas
        if (strpos($line, ',') !== false) {
            // Split by comma
            $parts = explode(',', $line);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    $values[] = $part;
                }
            }
        } else {
            // Check if line contains spaces (for backward compatibility)
            if (strpos($line, ' ') !== false) {
                // Split by space
                $parts = explode(' ', $line);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $values[] = $part;
                    }
                }
            } else {
                // Single value
                $values[] = $line;
            }
        }
    }
    
    return $values;
}

// Build Graylog search query
function graylog_build_query($fqdn, $search_terms, $filter_out) {
    $query_parts = array();
    
    // Add hostname/FQDN search (searches the fqdn field with trailing wildcard)
    if (!empty($fqdn)) {
        // Parse multiple values: newlines, commas, and spaces
        $fqdn_values = graylog_parse_multivalue_input($fqdn);
        
        foreach ($fqdn_values as $fqdn_item) {
            // Add trailing wildcard for partial matching unless user already specified wildcards
            // Only trailing wildcard to avoid expensive leading wildcard searches
            if (strpos($fqdn_item, '*') === false && strpos($fqdn_item, '?') === false) {
                $fqdn_item = $fqdn_item . '*';
            }
            
            // Only add quotes if the value contains spaces or special characters (rare for hostnames)
            if (preg_match('/\s/', $fqdn_item)) {
                $query_parts[] = 'fqdn:"' . $fqdn_item . '"';
            } else {
                $query_parts[] = 'fqdn:' . $fqdn_item;
            }
        }
    }
    
    // Add additional search terms
    if (!empty($search_terms)) {
        $terms = graylog_parse_multivalue_input($search_terms);
        foreach ($terms as $term) {
            if (!empty($term)) {
                $query_parts[] = $term;
            }
        }
    }
    
    // Add filter out terms (NOT)
    if (!empty($filter_out)) {
        $filters = graylog_parse_multivalue_input($filter_out);
        foreach ($filters as $filter) {
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
    
    // Build search endpoint - Graylog 6.1+ uses /search/messages
    $endpoint = $api_url . '/search/messages';
    
    // Build query parameters
    $params = array(
        'query' => $query,
        'fields' => 'timestamp,source,message,level',
        'size' => $limit
    );
    
    $url = add_query_arg($params, $endpoint);
    
    error_log('Graylog Search: API URL: ' . $url);
    
    // Prepare request arguments
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_token . ':token'),
            'Accept' => 'application/json',
            'X-Requested-By' => 'wordpress-plugin'
        ),
        'timeout' => 30
    );
    
    // Handle SSL verification setting
    $disable_ssl = get_option('graylog_search_disable_ssl_verify', false);
    if ($disable_ssl) {
        $args['sslverify'] = false;
        error_log('Graylog Search: SSL verification disabled');
    }
    
    // Make API request with required X-Requested-By header for Graylog 6.1+
    $response = wp_remote_get($url, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        error_log('Graylog Search: WP Error: ' . $response->get_error_message());
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Graylog Search: Response status: ' . $status_code);
    
    if ($status_code !== 200) {
        error_log('Graylog Search: API error response: ' . substr($body, 0, 500));
        return new WP_Error('api_error', 'Graylog API returned status code ' . $status_code . ': ' . substr($body, 0, 200));
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Graylog Search: JSON decode error: ' . json_last_error_msg());
        return new WP_Error('json_error', 'Failed to parse Graylog API response: ' . json_last_error_msg());
    }
    
    // Convert Graylog 6.1+ format (schema + datarows) to expected format
    $converted_data = graylog_convert_api_response($data);
    
    return $converted_data;
}

// Convert Graylog 6.1+ API response format to plugin-expected format
function graylog_convert_api_response($data) {
    // Graylog 6.1+ returns: {schema: [...], datarows: [[...]], metadata: {...}}
    // Plugin expects: {messages: [{message: {...}, timestamp: ...}], ...}
    
    if (!isset($data['schema']) || !isset($data['datarows'])) {
        // Legacy format or unexpected response
        error_log('Graylog Search: Unexpected API response format');
        return $data;
    }
    
    $messages = array();
    
    // Build field map from schema
    $field_map = array();
    foreach ($data['schema'] as $index => $column) {
        $field_map[$column['field']] = $index;
    }
    
    // Convert datarows to messages array
    foreach ($data['datarows'] as $row) {
        $message_obj = array();
        
        // Map fields from row to message object
        if (isset($field_map['timestamp'])) {
            $message_obj['timestamp'] = $row[$field_map['timestamp']];
        }
        
        if (isset($field_map['source'])) {
            $message_obj['source'] = $row[$field_map['source']];
        }
        
        if (isset($field_map['message'])) {
            $message_obj['message'] = $row[$field_map['message']];
        }
        
        if (isset($field_map['level'])) {
            $message_obj['level'] = $row[$field_map['level']];
        } else {
            $message_obj['level'] = -1; // Default if not provided
        }
        
        // Wrap in expected structure
        $messages[] = array(
            'message' => $message_obj,
            'timestamp' => $message_obj['timestamp']
        );
    }
    
    // Return in expected format
    return array(
        'messages' => $messages,
        'total_results' => count($messages),
        'time' => $data['metadata']['effective_timerange'] ?? null
    );
}

