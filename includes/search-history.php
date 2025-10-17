<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Search History Management
 * Tracks all searches with database storage, favorites, and re-run capability
 */

// Create database table on plugin activation
function graylog_search_history_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        search_params text NOT NULL,
        query_string text,
        result_count int(11) DEFAULT 0,
        is_favorite tinyint(1) DEFAULT 0,
        search_date datetime NOT NULL,
        execution_time float DEFAULT 0,
        notes text,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY search_date (search_date),
        KEY is_favorite (is_favorite)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook into plugin activation
register_activation_hook(GRAYLOG_SEARCH_PLUGIN_DIR . 'graylog-search.php', 'graylog_search_history_create_table');

// Helper: Check if history table exists and is accessible
function graylog_search_history_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    
    // Suppress errors for this check
    $wpdb->hide_errors();
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    $wpdb->show_errors();
    
    return $exists;
}

// Log a search to history
function graylog_log_search_to_history($search_params, $query_string, $result_count, $execution_time = 0) {
    global $wpdb;
    
    if (!is_user_logged_in()) {
        return false;
    }
    
    $table_name = $wpdb->prefix . 'graylog_search_history';
    
    // Check if table exists - if not, try to create it
    // Suppress errors to prevent them from being output in AJAX responses
    $wpdb->hide_errors();
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        error_log('Graylog Search: History table does not exist, attempting to create...');
        graylog_search_history_create_table();
        
        // Check again
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('Graylog Search: Failed to create history table, skipping history logging');
            $wpdb->show_errors(); // Re-enable for debugging elsewhere
            return false;
        }
    }
    $wpdb->show_errors(); // Re-enable for debugging elsewhere
    
    $data = array(
        'user_id' => get_current_user_id(),
        'search_params' => maybe_serialize($search_params),
        'query_string' => $query_string,
        'result_count' => $result_count,
        'search_date' => current_time('mysql'),
        'execution_time' => $execution_time
    );
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result) {
        // Clean up old entries (keep last 100 per user)
        graylog_cleanup_old_history();
        return $wpdb->insert_id;
    }
    
    return false;
}

// Clean up old search history (keep last 100 per user)
function graylog_cleanup_old_history() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    // Get IDs to keep (last 100 non-favorite + all favorites)
    $keep_ids = $wpdb->get_col($wpdb->prepare("
        (SELECT id FROM $table_name 
         WHERE user_id = %d AND is_favorite = 0 
         ORDER BY search_date DESC 
         LIMIT 100)
        UNION
        (SELECT id FROM $table_name 
         WHERE user_id = %d AND is_favorite = 1)
    ", $user_id, $user_id));
    
    if (!empty($keep_ids)) {
        $placeholders = implode(',', array_fill(0, count($keep_ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id = %d AND id NOT IN ($placeholders)",
            array_merge(array($user_id), $keep_ids)
        );
        $wpdb->query($query);
    }
}

// Get search history
function graylog_get_search_history($filters = array()) {
    // Check if table exists first
    if (!graylog_search_history_table_exists()) {
        error_log('Graylog Search: Cannot get history - table does not exist');
        return array(); // Return empty array instead of error
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    // Build query
    $where = array("user_id = %d");
    $params = array($user_id);
    
    // Date range filter
    if (!empty($filters['date_from'])) {
        $where[] = "search_date >= %s";
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "search_date <= %s";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    
    // Favorites only filter
    if (!empty($filters['favorites_only'])) {
        $where[] = "is_favorite = 1";
    }
    
    // Search filter (search within query strings)
    if (!empty($filters['search'])) {
        $where[] = "(query_string LIKE %s OR search_params LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    // Pagination
    $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
    $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
    
    // Order by
    $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'search_date';
    $order_dir = isset($filters['order_dir']) && $filters['order_dir'] === 'ASC' ? 'ASC' : 'DESC';
    
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where_clause ORDER BY $order_by $order_dir LIMIT %d OFFSET %d",
        array_merge($params, array($limit, $offset))
    );
    
    $results = $wpdb->get_results($query);
    
    // Unserialize search params
    foreach ($results as &$result) {
        $result->search_params = maybe_unserialize($result->search_params);
    }
    
    return $results;
}

// Get search history count
function graylog_get_search_history_count($filters = array()) {
    // Check if table exists first
    if (!graylog_search_history_table_exists()) {
        return 0; // Return 0 instead of error
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    $where = array("user_id = %d");
    $params = array($user_id);
    
    if (!empty($filters['date_from'])) {
        $where[] = "search_date >= %s";
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "search_date <= %s";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    
    if (!empty($filters['favorites_only'])) {
        $where[] = "is_favorite = 1";
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(query_string LIKE %s OR search_params LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where_clause", $params);
    
    return $wpdb->get_var($query);
}

// Toggle favorite status
function graylog_toggle_favorite($history_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    // Verify ownership
    $search = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $history_id,
        $user_id
    ));
    
    if (!$search) {
        return false;
    }
    
    $new_status = $search->is_favorite ? 0 : 1;
    
    $result = $wpdb->update(
        $table_name,
        array('is_favorite' => $new_status),
        array('id' => $history_id, 'user_id' => $user_id),
        array('%d'),
        array('%d', '%d')
    );
    
    return $result !== false ? $new_status : false;
}

// Add note to search history
function graylog_add_search_note($history_id, $note) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    $result = $wpdb->update(
        $table_name,
        array('notes' => sanitize_textarea_field($note)),
        array('id' => $history_id, 'user_id' => $user_id),
        array('%s'),
        array('%d', '%d')
    );
    
    return $result !== false;
}

// Delete search from history
function graylog_delete_search_history($history_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $history_id, 'user_id' => $user_id),
        array('%d', '%d')
    );
    
    return $result !== false;
}

// Get search statistics
function graylog_get_search_statistics() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'graylog_search_history';
    $user_id = get_current_user_id();
    
    $stats = array();
    
    // Total searches
    $stats['total_searches'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    // Searches today
    $stats['searches_today'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND DATE(search_date) = CURDATE()",
        $user_id
    ));
    
    // Searches this week
    $stats['searches_week'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND search_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        $user_id
    ));
    
    // Favorites count
    $stats['favorites_count'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_favorite = 1",
        $user_id
    ));
    
    // Average execution time
    $stats['avg_execution_time'] = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(execution_time) FROM $table_name WHERE user_id = %d AND execution_time > 0",
        $user_id
    ));
    
    // Most searched terms (from query_string)
    $stats['top_queries'] = $wpdb->get_results($wpdb->prepare(
        "SELECT query_string, COUNT(*) as count FROM $table_name 
         WHERE user_id = %d AND query_string != '' 
         GROUP BY query_string 
         ORDER BY count DESC 
         LIMIT 5",
        $user_id
    ));
    
    return $stats;
}

// AJAX Handlers

// Get search history
add_action('wp_ajax_graylog_get_search_history', 'graylog_get_search_history_handler');
function graylog_get_search_history_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $filters = array(
        'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
        'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
        'favorites_only' => isset($_POST['favorites_only']) && $_POST['favorites_only'] === 'true',
        'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
        'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
        'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
        'order_by' => isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : 'search_date',
        'order_dir' => isset($_POST['order_dir']) ? sanitize_text_field($_POST['order_dir']) : 'DESC'
    );
    
    $history = graylog_get_search_history($filters);
    $total = graylog_get_search_history_count($filters);
    
    wp_send_json_success(array(
        'history' => $history,
        'total' => $total,
        'has_more' => ($filters['offset'] + $filters['limit']) < $total
    ));
}

// Toggle favorite
add_action('wp_ajax_graylog_toggle_favorite', 'graylog_toggle_favorite_handler');
function graylog_toggle_favorite_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $history_id = intval($_POST['history_id']);
    $new_status = graylog_toggle_favorite($history_id);
    
    if ($new_status !== false) {
        wp_send_json_success(array(
            'is_favorite' => $new_status,
            'message' => $new_status ? 'Added to favorites' : 'Removed from favorites'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to toggle favorite'));
    }
}

// Add note
add_action('wp_ajax_graylog_add_search_note', 'graylog_add_search_note_handler');
function graylog_add_search_note_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $history_id = intval($_POST['history_id']);
    $note = sanitize_textarea_field($_POST['note']);
    
    if (graylog_add_search_note($history_id, $note)) {
        wp_send_json_success(array('message' => 'Note saved'));
    } else {
        wp_send_json_error(array('message' => 'Failed to save note'));
    }
}

// Delete search
add_action('wp_ajax_graylog_delete_search_history', 'graylog_delete_search_history_handler');
function graylog_delete_search_history_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $history_id = intval($_POST['history_id']);
    
    if (graylog_delete_search_history($history_id)) {
        wp_send_json_success(array('message' => 'Search deleted'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete search'));
    }
}

// Get statistics
add_action('wp_ajax_graylog_get_search_statistics', 'graylog_get_search_statistics_handler');
function graylog_get_search_statistics_handler() {
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $stats = graylog_get_search_statistics();
    
    wp_send_json_success(array('statistics' => $stats));
}

