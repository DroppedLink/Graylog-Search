<?php
/**
 * Audit logging for Web Embed plugin
 * 
 * Logs important settings changes for security and troubleshooting.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Log a settings change
 * 
 * Stores last 50 audit entries in WordPress options.
 *
 * @since 1.0.0
 * @param string $action      The action performed.
 * @param string $description Description of the change.
 * @param array  $metadata    Optional additional data.
 * @return void
 */
function web_embed_log_audit($action, $description, $metadata = array()) {
    $user = wp_get_current_user();
    
    $entry = array(
        'timestamp' => current_time('mysql'),
        'user_id' => $user->ID,
        'user_name' => $user->display_name,
        'action' => $action,
        'description' => $description,
        'metadata' => $metadata,
        'ip' => web_embed_get_user_ip()
    );
    
    // Get existing log
    $log = get_option('web_embed_audit_log', array());
    
    // Add new entry at the beginning
    array_unshift($log, $entry);
    
    // Keep only last 50 entries
    $log = array_slice($log, 0, 50);
    
    // Save back
    update_option('web_embed_audit_log', $log);
}

/**
 * Get audit log entries
 * 
 * Retrieves audit log with optional filtering.
 *
 * @since 1.0.0
 * @param int $limit Maximum number of entries to return.
 * @return array Audit log entries.
 */
function web_embed_get_audit_log($limit = 50) {
    $log = get_option('web_embed_audit_log', array());
    
    if ($limit > 0) {
        $log = array_slice($log, 0, $limit);
    }
    
    return $log;
}

/**
 * Clear audit log
 * 
 * Removes all audit log entries.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_clear_audit_log() {
    delete_option('web_embed_audit_log');
    
    web_embed_log_audit(
        'audit_log_cleared',
        __('Audit log cleared', 'web-embed')
    );
}

/**
 * Get user IP address
 * 
 * Attempts to get real IP address even behind proxies.
 *
 * @since 1.0.0
 * @return string IP address or 'unknown'.
 */
function web_embed_get_user_ip() {
    // Check for shared internet/proxy
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // Sanitize IP
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

/**
 * Format audit log entry for display
 * 
 * Converts audit entry to human-readable format.
 *
 * @since 1.0.0
 * @param array $entry Audit log entry.
 * @return string Formatted entry.
 */
function web_embed_format_audit_entry($entry) {
    $time = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $entry['timestamp']);
    
    return sprintf(
        '[%s] %s - %s (User: %s, IP: %s)',
        $time,
        esc_html($entry['action']),
        esc_html($entry['description']),
        esc_html($entry['user_name']),
        esc_html($entry['ip'])
    );
}

