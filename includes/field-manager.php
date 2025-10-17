<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Field Manager
 * Manages Graylog field discovery and metadata
 */

// Get available Graylog fields
function graylog_get_available_fields() {
    // Common Graylog fields (always available)
    $common_fields = array(
        'message' => array(
            'name' => 'message',
            'display_name' => 'Message',
            'type' => 'string',
            'operators' => array('contains', 'equals', 'regex', 'not_contains', 'not_equals'),
            'description' => 'The log message content'
        ),
        'source' => array(
            'name' => 'source',
            'display_name' => 'Source',
            'type' => 'string',
            'operators' => array('equals', 'contains', 'regex', 'not_equals'),
            'description' => 'Source of the log entry'
        ),
        'fqdn' => array(
            'name' => 'fqdn',
            'display_name' => 'Hostname (FQDN)',
            'type' => 'string',
            'operators' => array('equals', 'contains', 'regex', 'not_equals'),
            'description' => 'Fully qualified domain name'
        ),
        'timestamp' => array(
            'name' => 'timestamp',
            'display_name' => 'Timestamp',
            'type' => 'date',
            'operators' => array('greater_than', 'less_than', 'between'),
            'description' => 'Log entry timestamp'
        ),
        'level' => array(
            'name' => 'level',
            'display_name' => 'Level',
            'type' => 'string',
            'operators' => array('equals', 'not_equals'),
            'description' => 'Log level (ERROR, WARN, INFO, DEBUG)'
        ),
        'facility' => array(
            'name' => 'facility',
            'display_name' => 'Facility',
            'type' => 'string',
            'operators' => array('equals', 'not_equals'),
            'description' => 'Syslog facility'
        ),
        'application' => array(
            'name' => 'application',
            'display_name' => 'Application',
            'type' => 'string',
            'operators' => array('equals', 'contains', 'not_equals'),
            'description' => 'Application name'
        ),
        'gl2_source_input' => array(
            'name' => 'gl2_source_input',
            'display_name' => 'Input',
            'type' => 'string',
            'operators' => array('equals', 'not_equals'),
            'description' => 'Graylog input ID'
        ),
        'gl2_source_node' => array(
            'name' => 'gl2_source_node',
            'display_name' => 'Node',
            'type' => 'string',
            'operators' => array('equals', 'not_equals'),
            'description' => 'Graylog node ID'
        )
    );
    
    // Try to get custom fields from Graylog (cached for 1 hour)
    $cached_fields = get_transient('graylog_custom_fields');
    if ($cached_fields !== false) {
        return array_merge($common_fields, $cached_fields);
    }
    
    // For now, return common fields
    // In production, could query Graylog API for field list
    return $common_fields;
}

// Get fields AJAX handler
add_action('wp_ajax_graylog_get_fields', 'graylog_get_fields_handler');
function graylog_get_fields_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $fields = graylog_get_available_fields();
    
    wp_send_json_success(array('fields' => $fields));
}

// Get field operators
function graylog_get_operators() {
    return array(
        'contains' => array(
            'label' => 'Contains',
            'symbol' => '~',
            'types' => array('string'),
            'requires_value' => true
        ),
        'equals' => array(
            'label' => 'Equals',
            'symbol' => ':',
            'types' => array('string', 'number'),
            'requires_value' => true
        ),
        'not_equals' => array(
            'label' => 'Not Equals',
            'symbol' => 'NOT',
            'types' => array('string', 'number'),
            'requires_value' => true
        ),
        'not_contains' => array(
            'label' => 'Does Not Contain',
            'symbol' => 'NOT',
            'types' => array('string'),
            'requires_value' => true
        ),
        'regex' => array(
            'label' => 'Matches Regex',
            'symbol' => '~',
            'types' => array('string'),
            'requires_value' => true
        ),
        'exists' => array(
            'label' => 'Exists',
            'symbol' => '_exists_:',
            'types' => array('string', 'number', 'date'),
            'requires_value' => false
        ),
        'not_exists' => array(
            'label' => 'Does Not Exist',
            'symbol' => 'NOT _exists_:',
            'types' => array('string', 'number', 'date'),
            'requires_value' => false
        ),
        'greater_than' => array(
            'label' => 'Greater Than',
            'symbol' => '>',
            'types' => array('number', 'date'),
            'requires_value' => true
        ),
        'less_than' => array(
            'label' => 'Less Than',
            'symbol' => '<',
            'types' => array('number', 'date'),
            'requires_value' => true
        ),
        'greater_than_or_equal' => array(
            'label' => 'Greater Than or Equal',
            'symbol' => '>=',
            'types' => array('number', 'date'),
            'requires_value' => true
        ),
        'less_than_or_equal' => array(
            'label' => 'Less Than or Equal',
            'symbol' => '<=',
            'types' => array('number', 'date'),
            'requires_value' => true
        ),
        'between' => array(
            'label' => 'Between',
            'symbol' => 'BETWEEN',
            'types' => array('number', 'date'),
            'requires_value' => true,
            'requires_two_values' => true
        )
    );
}

// Get operators AJAX handler
add_action('wp_ajax_graylog_get_operators', 'graylog_get_operators_handler');
function graylog_get_operators_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $operators = graylog_get_operators();
    
    wp_send_json_success(array('operators' => $operators));
}

// Build Lucene query from query structure
function graylog_build_lucene_query($query_structure) {
    if (empty($query_structure) || !isset($query_structure['groups'])) {
        return '';
    }
    
    $query_parts = array();
    
    foreach ($query_structure['groups'] as $group) {
        $group_parts = array();
        
        foreach ($group['conditions'] as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = isset($condition['value']) ? $condition['value'] : '';
            
            $lucene_part = graylog_build_condition($field, $operator, $value);
            
            if (!empty($lucene_part)) {
                $group_parts[] = $lucene_part;
            }
        }
        
        if (!empty($group_parts)) {
            $group_operator = isset($group['operator']) ? $group['operator'] : 'AND';
            $group_query = implode(' ' . $group_operator . ' ', $group_parts);
            
            // Wrap in parentheses if multiple conditions
            if (count($group_parts) > 1) {
                $group_query = '(' . $group_query . ')';
            }
            
            $query_parts[] = $group_query;
        }
    }
    
    if (empty($query_parts)) {
        return '';
    }
    
    // Join groups with top-level operator (default AND)
    $top_operator = isset($query_structure['operator']) ? $query_structure['operator'] : 'AND';
    return implode(' ' . $top_operator . ' ', $query_parts);
}

// Build individual condition
function graylog_build_condition($field, $operator, $value) {
    $operators = graylog_get_operators();
    
    if (!isset($operators[$operator])) {
        return '';
    }
    
    $op_info = $operators[$operator];
    
    // Handle operators that don't need a value
    if (!$op_info['requires_value']) {
        if ($operator === 'exists') {
            return '_exists_:' . $field;
        } elseif ($operator === 'not_exists') {
            return 'NOT _exists_:' . $field;
        }
    }
    
    // Escape value
    $escaped_value = graylog_escape_lucene_value($value);
    
    // Build query based on operator
    switch ($operator) {
        case 'contains':
            return $field . ':*' . $escaped_value . '*';
            
        case 'equals':
            return $field . ':' . $escaped_value;
            
        case 'not_equals':
            return 'NOT ' . $field . ':' . $escaped_value;
            
        case 'not_contains':
            return 'NOT ' . $field . ':*' . $escaped_value . '*';
            
        case 'regex':
            return $field . ':/' . $value . '/';
            
        case 'greater_than':
            return $field . ':>' . $escaped_value;
            
        case 'less_than':
            return $field . ':<' . $escaped_value;
            
        case 'greater_than_or_equal':
            return $field . ':>=' . $escaped_value;
            
        case 'less_than_or_equal':
            return $field . ':<=' . $escaped_value;
            
        case 'between':
            // Value should be array [min, max]
            if (is_array($value) && count($value) === 2) {
                return $field . ':[' . graylog_escape_lucene_value($value[0]) . ' TO ' . graylog_escape_lucene_value($value[1]) . ']';
            }
            return '';
            
        default:
            return '';
    }
}

// Escape Lucene special characters
function graylog_escape_lucene_value($value) {
    // Lucene special characters that need escaping
    $special_chars = array('+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/');
    
    $escaped = $value;
    
    // Escape backslash first
    $escaped = str_replace('\\', '\\\\', $escaped);
    
    // Escape other special characters
    foreach ($special_chars as $char) {
        if ($char !== '\\') {
            $escaped = str_replace($char, '\\' . $char, $escaped);
        }
    }
    
    // Wrap in quotes if contains spaces
    if (strpos($escaped, ' ') !== false) {
        $escaped = '"' . $escaped . '"';
    }
    
    return $escaped;
}

// Convert visual query to Lucene - AJAX handler
add_action('wp_ajax_graylog_build_query', 'graylog_build_query_handler');
function graylog_build_query_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $query_structure = json_decode(stripslashes($_POST['query_structure']), true);
    
    if (!$query_structure) {
        wp_send_json_error(array('message' => 'Invalid query structure'));
        return;
    }
    
    $lucene_query = graylog_build_lucene_query($query_structure);
    
    wp_send_json_success(array(
        'query' => $lucene_query,
        'structure' => $query_structure
    ));
}

// Save query template
add_action('wp_ajax_graylog_save_query_template', 'graylog_save_query_template_handler');
function graylog_save_query_template_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_text_field($_POST['description']);
    $query_structure = json_decode(stripslashes($_POST['query_structure']), true);
    
    if (empty($name) || !$query_structure) {
        wp_send_json_error(array('message' => 'Name and query structure required'));
        return;
    }
    
    // Get existing templates
    $templates = get_user_meta(get_current_user_id(), 'graylog_query_templates', true);
    if (!is_array($templates)) {
        $templates = array();
    }
    
    // Add new template
    $templates[$name] = array(
        'name' => $name,
        'description' => $description,
        'structure' => $query_structure,
        'created' => current_time('mysql')
    );
    
    // Save
    update_user_meta(get_current_user_id(), 'graylog_query_templates', $templates);
    
    wp_send_json_success(array(
        'message' => 'Template saved successfully',
        'templates' => $templates
    ));
}

// Get query templates
add_action('wp_ajax_graylog_get_query_templates', 'graylog_get_query_templates_handler');
function graylog_get_query_templates_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $templates = get_user_meta(get_current_user_id(), 'graylog_query_templates', true);
    if (!is_array($templates)) {
        $templates = array();
    }
    
    wp_send_json_success(array('templates' => $templates));
}

// Delete query template
add_action('wp_ajax_graylog_delete_query_template', 'graylog_delete_query_template_handler');
function graylog_delete_query_template_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $name = sanitize_text_field($_POST['name']);
    
    // Get existing templates
    $templates = get_user_meta(get_current_user_id(), 'graylog_query_templates', true);
    if (!is_array($templates)) {
        $templates = array();
    }
    
    // Remove template
    unset($templates[$name]);
    
    // Save
    update_user_meta(get_current_user_id(), 'graylog_query_templates', $templates);
    
    wp_send_json_success(array(
        'message' => 'Template deleted successfully',
        'templates' => $templates
    ));
}

