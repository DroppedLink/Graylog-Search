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
    // Note: Using wp_strip_all_tags instead of sanitize_textarea_field to preserve newlines
    // sanitize_textarea_field converts newlines to spaces, breaking multi-line search
    $search_query = trim(wp_strip_all_tags(wp_unslash($_POST['search_query'])));
    $search_mode = sanitize_text_field($_POST['search_mode']); // 'simple', 'advanced', or 'query_builder'
    $filter_out = trim(wp_strip_all_tags(wp_unslash($_POST['filter_out'])));
    $time_range = intval($_POST['time_range']);
    $limit = intval($_POST['limit']);
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    
    // DEBUG: Log incoming parameters
    error_log('===== GRAYLOG SEARCH DEBUG =====');
    error_log('Search Query: "' . $search_query . '"');
    error_log('Search Mode: "' . $search_mode . '"');
    error_log('Filter Out: "' . $filter_out . '"');
    error_log('Time Range: ' . $time_range);
    error_log('Limit: ' . $limit);
    
    // Build Graylog query
    $query = graylog_build_query($search_query, $search_mode, $filter_out);
    error_log('Final Graylog Query: ' . $query);
    error_log('================================');
    
    // Check cache first (5-minute TTL)
    $cache_key = 'graylog_search_' . md5($query . $time_range . $limit . $offset);
    $cached_results = get_transient($cache_key);
    
    if ($cached_results !== false) {
        error_log('Graylog Search: Returning cached results');
        wp_send_json_success($cached_results);
        return;
    }
    
    // Get API settings
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    
    if (empty($api_url) || empty($api_token)) {
        error_log('Graylog Search: API not configured');
        wp_send_json_error(array('message' => 'Graylog API not configured'));
        return;
    }
    
    error_log('Graylog Search: Making API request');
    // Make API request with pagination
    $results = graylog_api_search($api_url, $api_token, $query, $time_range, $limit, $offset);
    
    if (is_wp_error($results)) {
        error_log('Graylog Search: API error: ' . $results->get_error_message());
        wp_send_json_error(array('message' => $results->get_error_message()));
        return;
    }
    
    // Cache results for 5 minutes
    set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
    
    // Track recent search
    if (is_user_logged_in()) {
        graylog_add_to_recent_searches(array(
            'search_query' => $search_query,
            'search_mode' => $search_mode,
            'filter_out' => $filter_out,
            'time_range' => $time_range
        ));
        
        // Log to search history database
        $execution_time = isset($_POST['execution_time']) ? floatval($_POST['execution_time']) : 0;
        graylog_log_search_to_history(
            array(
                'search_query' => $search_query,
                'search_mode' => $search_mode,
                'filter_out' => $filter_out,
                'time_range' => $time_range,
                'limit' => $limit,
                'offset' => $offset
            ),
            $query,
            isset($results['total_results']) ? $results['total_results'] : count($results['messages'] ?? []),
            $execution_time
        );
    }
    
    error_log('Graylog Search: Success - ' . count($results['messages'] ?? []) . ' messages');
    wp_send_json_success($results);
}

// Parse multi-value input (newlines, commas, or optionally spaces)
function graylog_parse_multivalue_input($input, $split_on_spaces = true) {
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
            // Check if line contains spaces (for backward compatibility with hostnames/filters)
            // For search terms, we DON'T split on spaces to support phrase searches
            if ($split_on_spaces && strpos($line, ' ') !== false) {
                // Split by space
                $parts = explode(' ', $line);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $values[] = $part;
                    }
                }
            } else {
                // Single value (or phrase with spaces if split_on_spaces is false)
                $values[] = $line;
            }
        }
    }
    
    return $values;
}

// Build Graylog search query
function graylog_build_query($search_query, $search_mode, $filter_out) {
    $query_parts = array();
    
    // Handle search query based on mode
    if (!empty($search_query)) {
        if ($search_mode === 'advanced' || $search_mode === 'query_builder') {
            // Advanced mode: user provides full Lucene syntax
            // Pass through as-is (user has full control)
            $query_parts[] = trim($search_query);
        } else {
            // Simple mode: search across multiple common fields
            // Use only trailing wildcards to avoid expensive leading wildcard queries
            $terms = graylog_parse_multivalue_input($search_query, false); // Don't split on spaces
            
            $term_queries = array();
            foreach ($terms as $term) {
                $term = trim($term);
                if (empty($term)) {
                    continue;
                }
                
                // Check if term contains spaces (phrase search)
                $has_spaces = preg_match('/\s/', $term);
                
                // Escape special Lucene characters except * and ?
                $term = preg_replace('/([+\-&|!(){}\[\]^"~:\\\])/', '\\\\$1', $term);
                
                // Build simplified query without field specifications
                // Graylog will search all fields automatically
                if ($has_spaces) {
                    // Phrase search - use quotes, no wildcards
                    $term_queries[] = '"' . str_replace('*', '', $term) . '"';
                } else {
                    // Single word - add trailing wildcard for partial matching
                    if (strpos($term, '*') === false && strpos($term, '?') === false) {
                        $term = $term . '*';
                    }
                    $term_queries[] = $term;
                }
            }
            
            // Combine all term queries
            if (count($term_queries) > 1) {
                $query_parts[] = '(' . implode(' OR ', $term_queries) . ')';
            } elseif (count($term_queries) === 1) {
                $query_parts[] = $term_queries[0];
            }
        }
    }
    
    // Add filter out terms (NOT) - these are AND'ed with NOT prefix
    // This works for all modes
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
    
    // AND together all parts
    $final_query = implode(' AND ', $query_parts);
    error_log('Graylog Search: Built query (mode: ' . $search_mode . '): ' . $final_query);
    return $final_query;
}

// Make Graylog API search request
function graylog_api_search($api_url, $api_token, $query, $time_range, $limit, $offset = 0) {
    // Clean up API URL
    $api_url = rtrim($api_url, '/');
    
    // Ensure /api is in the path
    if (!preg_match('/\/api$/', $api_url)) {
        $api_url .= '/api';
    }
    
    // Build search endpoint - Graylog 6.1+ uses /search/messages
    $endpoint = $api_url . '/search/messages';
    
    // Build query parameters with pagination
    $params = array(
        'query' => $query,
        'fields' => 'timestamp,source,message,level',
        'size' => $limit,
        'offset' => $offset
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

