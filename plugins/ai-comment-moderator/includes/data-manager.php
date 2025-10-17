<?php
/**
 * Data Manager
 * 
 * Manages plugin data, including reset and statistics
 * 
 * @package AI_Comment_Moderator
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Data_Manager {
    
    /**
     * Reset all processing data while preserving configuration
     * 
     * Clears:
     * - AI review history
     * - Provider usage statistics
     * - Correction tracking
     * - Notifications
     * - Remote comments cache
     * - Comment meta added by plugin
     * 
     * Preserves:
     * - Plugin settings (AI providers, API keys, etc.)
     * - Remote site configurations
     * - Custom prompts
     * - User preferences
     * 
     * @return bool True on success, false on failure
     */
    public static function reset_processing_data() {
        global $wpdb;
        
        try {
            // Clear AI review data
            $wpdb->query("DELETE FROM {$wpdb->prefix}ai_comment_reviews");
            
            // Clear provider usage stats
            $wpdb->query("DELETE FROM {$wpdb->prefix}ai_provider_usage");
            
            // Clear corrections tracking
            $wpdb->query("DELETE FROM {$wpdb->prefix}ai_corrections");
            
            // Clear notifications
            $wpdb->query("DELETE FROM {$wpdb->prefix}ai_notifications");
            
            // Clear remote comments cache
            $wpdb->query("DELETE FROM {$wpdb->prefix}ai_remote_comments");
            
            // Clear WordPress comment meta added by plugin
            $wpdb->query("DELETE FROM {$wpdb->prefix}commentmeta 
                          WHERE meta_key LIKE 'ai_moderator_%'");
            
            // Reset auto-increment IDs for clean slate
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ai_comment_reviews AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ai_provider_usage AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ai_corrections AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ai_notifications AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ai_remote_comments AUTO_INCREMENT = 1");
            
            // Log the reset action
            error_log('AI Comment Moderator: Processing data reset by user ' . get_current_user_id());
            
            return true;
        } catch (Exception $e) {
            error_log('AI Comment Moderator: Data reset failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current data statistics
     * 
     * @return array Statistics for each data type
     */
    public static function get_data_stats() {
        global $wpdb;
        
        return [
            'reviews' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_comment_reviews"),
            'usage' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_provider_usage"),
            'corrections' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_corrections"),
            'remote_comments' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_remote_comments"),
            'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_notifications"),
        ];
    }
    
    /**
     * Get total storage size used by plugin data (approximate)
     * 
     * @return array Storage information
     */
    public static function get_storage_info() {
        global $wpdb;
        
        $tables = [
            'ai_comment_reviews',
            'ai_provider_usage',
            'ai_corrections',
            'ai_notifications',
            'ai_remote_comments'
        ];
        
        $total_size = 0;
        $table_sizes = [];
        
        foreach ($tables as $table) {
            $result = $wpdb->get_row("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                AND table_name = '{$wpdb->prefix}{$table}'
            ");
            
            if ($result) {
                $size = (float) $result->size_mb;
                $table_sizes[$table] = $size;
                $total_size += $size;
            }
        }
        
        return [
            'total_mb' => round($total_size, 2),
            'tables' => $table_sizes
        ];
    }
    
    /**
     * Verify data integrity after reset
     * 
     * @return array Integrity check results
     */
    public static function verify_reset() {
        $stats = self::get_data_stats();
        
        return [
            'success' => (
                $stats['reviews'] === 0 &&
                $stats['usage'] === 0 &&
                $stats['corrections'] === 0 &&
                $stats['remote_comments'] === 0
            ),
            'stats' => $stats
        ];
    }
}
