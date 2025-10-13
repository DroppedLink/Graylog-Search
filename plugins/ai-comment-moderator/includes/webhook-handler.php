<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Webhook_Handler {
    
    /**
     * Send webhook notification
     */
    public static function send_webhook($event_type, $data) {
        $webhook_url = get_option('ai_comment_moderator_webhook_url', '');
        
        if (empty($webhook_url)) {
            return false;
        }
        
        // Check if this event type should trigger webhook
        $enabled_events = get_option('ai_comment_moderator_webhook_events', 'toxic,spam_high');
        $enabled_events = array_map('trim', explode(',', $enabled_events));
        
        if (!in_array($event_type, $enabled_events) && !in_array('all', $enabled_events)) {
            return false;
        }
        
        // Prepare payload
        $payload = array(
            'event' => $event_type,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'data' => $data
        );
        
        // Send webhook asynchronously
        $response = wp_remote_post($webhook_url, array(
            'method' => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Comment-Moderator/' . AI_COMMENT_MODERATOR_VERSION
            ),
            'body' => json_encode($payload)
        ));
        
        // Log webhook call
        self::log_webhook(
            $event_type,
            $webhook_url,
            $payload,
            $response
        );
        
        return !is_wp_error($response);
    }
    
    /**
     * Log webhook call to database
     */
    private static function log_webhook($event_type, $webhook_url, $payload, $response) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_webhook_log';
        
        $success = !is_wp_error($response);
        $response_code = $success ? wp_remote_retrieve_response_code($response) : 0;
        $response_body = $success ? wp_remote_retrieve_body($response) : $response->get_error_message();
        
        $wpdb->insert($table, array(
            'event_type' => $event_type,
            'webhook_url' => $webhook_url,
            'payload' => json_encode($payload),
            'response_code' => $response_code,
            'response_body' => substr($response_body, 0, 1000), // Limit to 1000 chars
            'success' => $success ? 1 : 0,
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Test webhook connection
     */
    public static function test_webhook($webhook_url) {
        $test_payload = array(
            'event' => 'test',
            'message' => 'This is a test webhook from AI Comment Moderator',
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name')
        );
        
        $response = wp_remote_post($webhook_url, array(
            'method' => 'POST',
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Comment-Moderator/' . AI_COMMENT_MODERATOR_VERSION
            ),
            'body' => json_encode($test_payload)
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return array(
            'success' => $response_code >= 200 && $response_code < 300,
            'response_code' => $response_code,
            'response_body' => wp_remote_retrieve_body($response)
        );
    }
    
    /**
     * Get recent webhook logs
     */
    public static function get_recent_logs($limit = 50) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_webhook_log';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get webhook statistics
     */
    public static function get_statistics($days = 7) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_webhook_log';
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_calls,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_calls,
                COUNT(DISTINCT event_type) as unique_events
            FROM $table
            WHERE created_at >= %s
        ", $since));
    }
    
    /**
     * Clear old webhook logs
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_webhook_log';
        $before = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $before
        ));
    }
    
    /**
     * Format payload for different webhook types
     */
    public static function format_comment_webhook($comment, $ai_decision, $action_taken) {
        return array(
            'comment_id' => $comment->comment_ID,
            'comment_author' => $comment->comment_author,
            'comment_author_email' => $comment->comment_author_email,
            'comment_content' => $comment->comment_content,
            'comment_date' => $comment->comment_date,
            'post_id' => $comment->comment_post_ID,
            'post_title' => get_the_title($comment->comment_post_ID),
            'post_url' => get_permalink($comment->comment_post_ID),
            'comment_url' => admin_url('comment.php?action=editcomment&c=' . $comment->comment_ID),
            'ai_decision' => $ai_decision,
            'action_taken' => $action_taken
        );
    }
}

// Trigger webhooks on specific events
add_action('ai_moderator_toxic_detected', function($comment, $ai_response) {
    AI_Comment_Moderator_Webhook_Handler::send_webhook(
        'toxic',
        AI_Comment_Moderator_Webhook_Handler::format_comment_webhook(
            $comment,
            'toxic',
            'flagged'
        )
    );
}, 10, 2);

add_action('ai_moderator_spam_detected', function($comment, $ai_response) {
    AI_Comment_Moderator_Webhook_Handler::send_webhook(
        'spam',
        AI_Comment_Moderator_Webhook_Handler::format_comment_webhook(
            $comment,
            'spam',
            'flagged'
        )
    );
}, 10, 2);

