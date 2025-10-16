<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Saved Searches Functionality
 * Stores searches in WordPress user meta (Phase 1 - no DB tables)
 */

// Save a search
add_action('wp_ajax_graylog_save_search', 'graylog_save_search_handler');
function graylog_save_search_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $search_name = sanitize_text_field($_POST['name']);
    $search_data = array(
        'search_query' => sanitize_textarea_field($_POST['search_query']),
        'search_mode' => sanitize_text_field($_POST['search_mode']),
        'filter_out' => sanitize_text_field($_POST['filter_out']),
        'time_range' => intval($_POST['time_range']),
        'created' => current_time('mysql')
    );
    
    // Get existing saved searches
    $saved_searches = get_user_meta(get_current_user_id(), 'graylog_saved_searches', true);
    if (!is_array($saved_searches)) {
        $saved_searches = array();
    }
    
    // Add new search
    $saved_searches[$search_name] = $search_data;
    
    // Update user meta
    update_user_meta(get_current_user_id(), 'graylog_saved_searches', $saved_searches);
    
    wp_send_json_success(array(
        'message' => 'Search saved successfully',
        'searches' => $saved_searches
    ));
}

// Get saved searches
add_action('wp_ajax_graylog_get_saved_searches', 'graylog_get_saved_searches_handler');
function graylog_get_saved_searches_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $saved_searches = get_user_meta(get_current_user_id(), 'graylog_saved_searches', true);
    if (!is_array($saved_searches)) {
        $saved_searches = array();
    }
    
    wp_send_json_success(array('searches' => $saved_searches));
}

// Delete a saved search
add_action('wp_ajax_graylog_delete_saved_search', 'graylog_delete_saved_search_handler');
function graylog_delete_saved_search_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $search_name = sanitize_text_field($_POST['name']);
    
    // Get existing saved searches
    $saved_searches = get_user_meta(get_current_user_id(), 'graylog_saved_searches', true);
    if (!is_array($saved_searches)) {
        $saved_searches = array();
    }
    
    // Remove search
    unset($saved_searches[$search_name]);
    
    // Update user meta
    update_user_meta(get_current_user_id(), 'graylog_saved_searches', $saved_searches);
    
    wp_send_json_success(array(
        'message' => 'Search deleted successfully',
        'searches' => $saved_searches
    ));
}

// Save recent search (automatically track last 10)
function graylog_add_to_recent_searches($search_data) {
    $recent_searches = get_user_meta(get_current_user_id(), 'graylog_recent_searches', true);
    if (!is_array($recent_searches)) {
        $recent_searches = array();
    }
    
    // Add timestamp
    $search_data['timestamp'] = current_time('mysql');
    
    // Add to beginning of array
    array_unshift($recent_searches, $search_data);
    
    // Keep only last 10
    $recent_searches = array_slice($recent_searches, 0, 10);
    
    // Update user meta
    update_user_meta(get_current_user_id(), 'graylog_recent_searches', $recent_searches);
}

// Get recent searches
add_action('wp_ajax_graylog_get_recent_searches', 'graylog_get_recent_searches_handler');
function graylog_get_recent_searches_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $recent_searches = get_user_meta(get_current_user_id(), 'graylog_recent_searches', true);
    if (!is_array($recent_searches)) {
        $recent_searches = array();
    }
    
    wp_send_json_success(array('searches' => $recent_searches));
}

// Get quick filters
function graylog_get_quick_filters() {
    return array(
        array(
            'name' => 'Errors (Last Hour)',
            'data' => array(
                'search_query' => 'error',
                'search_mode' => 'simple',
                'filter_out' => '',
                'time_range' => 3600
            )
        ),
        array(
            'name' => 'Warnings (Last Hour)',
            'data' => array(
                'search_query' => 'warning',
                'search_mode' => 'simple',
                'filter_out' => '',
                'time_range' => 3600
            )
        ),
        array(
            'name' => 'Errors (Today)',
            'data' => array(
                'search_query' => 'error',
                'search_mode' => 'simple',
                'filter_out' => '',
                'time_range' => 86400
            )
        ),
        array(
            'name' => 'All Logs (Last Hour)',
            'data' => array(
                'search_query' => '',
                'search_mode' => 'simple',
                'filter_out' => '',
                'time_range' => 3600
            )
        )
    );
}

// Get quick filters AJAX
add_action('wp_ajax_graylog_get_quick_filters', 'graylog_get_quick_filters_handler');
function graylog_get_quick_filters_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    wp_send_json_success(array('filters' => graylog_get_quick_filters()));
}

// Save dark mode preference
add_action('wp_ajax_graylog_save_dark_mode', 'graylog_save_dark_mode_handler');
function graylog_save_dark_mode_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $enabled = $_POST['enabled'] === '1';
    
    // Save to user meta
    update_user_meta(get_current_user_id(), 'graylog_dark_mode', $enabled);
    
    wp_send_json_success(array('message' => 'Dark mode preference saved'));
}

