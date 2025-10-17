<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Batch_Processor {
    
    private $comment_processor;
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Get comment processor instance (lazy loading)
     */
    private function get_comment_processor() {
        if (!$this->comment_processor) {
            $this->comment_processor = new AI_Comment_Moderator_Comment_Processor();
        }
        return $this->comment_processor;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add AJAX handlers for batch processing
        add_action('wp_ajax_ai_moderator_start_batch', array($this, 'ajax_start_batch'));
        add_action('wp_ajax_ai_moderator_process_batch_chunk', array($this, 'ajax_process_batch_chunk'));
        add_action('wp_ajax_ai_moderator_get_batch_status', array($this, 'ajax_get_batch_status'));
    }
    
    /**
     * Start a new batch processing session
     */
    public function ajax_start_batch() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $prompt_id = intval($_POST['prompt_id']);
        $batch_count = intval($_POST['batch_count']);
        $comment_status = isset($_POST['comment_status']) ? sanitize_text_field($_POST['comment_status']) : 'all';
        $include_reviewed = isset($_POST['include_reviewed']) && $_POST['include_reviewed'] === '1';
        $comment_source = isset($_POST['comment_source']) ? sanitize_text_field($_POST['comment_source']) : 'local';
        $remote_site_id = isset($_POST['remote_site_id']) ? intval($_POST['remote_site_id']) : 0;
        
        // Validate inputs
        if (!$prompt_id || !$batch_count) {
            wp_send_json_error('Invalid parameters');
        }
        
        // Default to 'all' if not specified or empty
        if (empty($comment_status)) {
            $comment_status = 'all';
        }
        
        // Get comments based on source
        if ($comment_source === 'remote') {
            // Get remote comments
            if ($remote_site_id > 0) {
                $comments = $this->get_remote_comments($remote_site_id, $batch_count);
            } else {
                $comments = $this->get_all_remote_comments($batch_count);
            }
        } else {
            // Get local comments
            $comments = $this->get_comment_processor()->get_unreviewed_comments($batch_count, 0, $comment_status, $include_reviewed);
        }
        
        if (empty($comments)) {
            // Provide more helpful error message
            $status_label = '';
            switch ($comment_status) {
                case 'approved':
                    $status_label = 'approved';
                    break;
                case 'pending':
                    $status_label = 'pending';
                    break;
                case 'all':
                    $status_label = 'approved or pending';
                    break;
                default:
                    $status_label = $comment_status;
            }
            
            $source_label = $comment_source === 'remote' ? 'remote ' : '';
            $message = $include_reviewed 
                ? 'No ' . $source_label . $status_label . ' comments found.' 
                : 'No unreviewed ' . $source_label . $status_label . ' comments found. All ' . $status_label . ' comments may have already been reviewed by AI. Try enabling "Re-process Already Reviewed Comments" to process them again.';
            
            wp_send_json_error($message);
        }
        
        // Create batch session
        $batch_id = $this->create_batch_session($prompt_id, $comments);
        
        wp_send_json_success(array(
            'batch_id' => $batch_id,
            'total_comments' => count($comments),
            'chunk_size' => $this->get_chunk_size()
        ));
    }
    
    /**
     * Process a chunk of comments in the batch
     */
    public function ajax_process_batch_chunk() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id']);
        $chunk_offset = intval($_POST['chunk_offset']);
        
        // Get batch session
        $batch_session = $this->get_batch_session($batch_id);
        if (!$batch_session) {
            wp_send_json_error('Batch session not found');
        }
        
        // Get chunk of comments to process
        $chunk_size = $this->get_chunk_size();
        $comment_ids = array_slice($batch_session['comment_ids'], $chunk_offset, $chunk_size);
        
        if (empty($comment_ids)) {
            wp_send_json_error('No more comments to process');
        }
        
        // Process the chunk
        $start_time = microtime(true);
        $results = array(
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );
        
        foreach ($comment_ids as $comment_id) {
            $result = $this->get_comment_processor()->process_single_comment($comment_id, $batch_session['prompt_id']);
            
            $results['processed']++;
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
            
            // Get comment content for display
            global $wpdb;
            $comment_data = $wpdb->get_row($wpdb->prepare(
                "SELECT rc.comment_content, rc.comment_author, rs.site_name 
                 FROM {$wpdb->prefix}ai_remote_comments rc
                 LEFT JOIN {$wpdb->prefix}ai_remote_sites rs ON rc.site_id = rs.id
                 WHERE rc.id = %d",
                $comment_id
            ));
            
            // If not remote, try local
            if (!$comment_data) {
                $local_comment = get_comment($comment_id);
                if ($local_comment) {
                    $comment_data = (object) array(
                        'comment_content' => $local_comment->comment_content,
                        'comment_author' => $local_comment->comment_author,
                        'site_name' => get_bloginfo('name')
                    );
                }
            }
            
            $comment_snippet = '';
            $comment_author = '';
            $site_name = '';
            if ($comment_data) {
                $comment_snippet = wp_trim_words($comment_data->comment_content, 20);
                $comment_author = $comment_data->comment_author;
                $site_name = $comment_data->site_name ?? '';
            }
            
            // Format reason code display (v2.2.0+)
            $reason_display = '';
            if ($result['success'] && !empty($result['reason_code'])) {
                $reason_label = AI_Comment_Moderator_Reason_Codes::get_code_label($result['reason_code']);
                $reason_display = sprintf(
                    ' (Code %d: %s)',
                    $result['reason_code'],
                    $reason_label
                );
            }
            
            $results['details'][] = array(
                'comment_id' => $comment_id,
                'result' => $result,
                'snippet' => $comment_snippet,
                'author' => $comment_author,
                'site' => $site_name,
                'reason_display' => $reason_display
            );
            
            // Update batch progress
            $this->update_batch_progress($batch_id, $comment_id, $result);
            
            // Small delay to prevent API overload
            usleep(200000); // 0.2 second
        }
        
        $processing_time = microtime(true) - $start_time;
        
        // Update batch session
        $this->update_batch_session($batch_id, array(
            'processed_count' => $batch_session['processed_count'] + $results['processed'],
            'success_count' => $batch_session['success_count'] + $results['success'],
            'error_count' => $batch_session['error_count'] + $results['errors'],
            'last_processed_at' => current_time('mysql')
        ));
        
        wp_send_json_success(array(
            'chunk_results' => $results,
            'processing_time' => $processing_time,
            'next_offset' => $chunk_offset + $chunk_size,
            'completed' => ($chunk_offset + $chunk_size) >= count($batch_session['comment_ids'])
        ));
    }
    
    /**
     * Get batch processing status
     */
    public function ajax_get_batch_status() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id']);
        
        $batch_session = $this->get_batch_session($batch_id);
        if (!$batch_session) {
            wp_send_json_error('Batch session not found');
        }
        
        wp_send_json_success($batch_session);
    }
    
    /**
     * Create a new batch session
     */
    private function create_batch_session($prompt_id, $comments) {
        $batch_id = 'batch_' . time() . '_' . wp_generate_password(8, false);
        
        $comment_ids = array_map(function($comment) {
            return $comment->comment_ID;
        }, $comments);
        
        $session_data = array(
            'batch_id' => $batch_id,
            'prompt_id' => $prompt_id,
            'comment_ids' => $comment_ids,
            'total_count' => count($comment_ids),
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'started_at' => current_time('mysql'),
            'last_processed_at' => null,
            'completed_at' => null,
            'status' => 'running',
            'progress_log' => array()
        );
        
        // Store session in transient (expires in 1 hour)
        set_transient('ai_moderator_batch_' . $batch_id, $session_data, HOUR_IN_SECONDS);
        
        return $batch_id;
    }
    
    /**
     * Get batch session data
     */
    private function get_batch_session($batch_id) {
        return get_transient('ai_moderator_batch_' . $batch_id);
    }
    
    /**
     * Update batch session data
     */
    private function update_batch_session($batch_id, $updates) {
        $session = $this->get_batch_session($batch_id);
        if (!$session) {
            return false;
        }
        
        $session = array_merge($session, $updates);
        
        // Check if completed
        if ($session['processed_count'] >= $session['total_count']) {
            $session['status'] = 'completed';
            $session['completed_at'] = current_time('mysql');
        }
        
        set_transient('ai_moderator_batch_' . $batch_id, $session, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Update batch progress with individual comment result
     */
    private function update_batch_progress($batch_id, $comment_id, $result) {
        $session = $this->get_batch_session($batch_id);
        if (!$session) {
            return false;
        }
        
        $comment = get_comment($comment_id);
        $log_entry = array(
            'comment_id' => $comment_id,
            'author' => $comment ? $comment->comment_author : 'Unknown',
            'success' => $result['success'],
            'decision' => $result['success'] ? $result['decision'] : 'error',
            'action' => $result['success'] ? $result['action'] : 'none',
            'error' => $result['success'] ? null : $result['error'],
            'processed_at' => current_time('mysql')
        );
        
        $session['progress_log'][] = $log_entry;
        
        // Keep only last 100 entries to prevent memory issues
        if (count($session['progress_log']) > 100) {
            $session['progress_log'] = array_slice($session['progress_log'], -100);
        }
        
        set_transient('ai_moderator_batch_' . $batch_id, $session, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Get chunk size for processing
     */
    private function get_chunk_size() {
        // Process in small chunks to provide better progress feedback
        return 3;
    }
    
    /**
     * Clean up old batch sessions
     */
    public function cleanup_old_sessions() {
        global $wpdb;
        
        // Delete transients older than 1 hour
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_ai_moderator_batch_%' 
            AND option_value < " . (time() - HOUR_IN_SECONDS)
        );
    }
    
    /**
     * Get batch processing statistics
     */
    public function get_processing_stats($days = 7) {
        global $wpdb;
        
        $since_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_processed,
                SUM(CASE WHEN action_taken != 'error' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN action_taken = 'error' THEN 1 ELSE 0 END) as errors,
                AVG(processing_time) as avg_processing_time,
                SUM(CASE WHEN action_taken = 'approve' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN action_taken = 'spam' THEN 1 ELSE 0 END) as spam,
                SUM(CASE WHEN action_taken = 'trash' THEN 1 ELSE 0 END) as trashed
            FROM {$wpdb->prefix}ai_comment_logs 
            WHERE created_at >= %s
        ", $since_date));
    }
    
    /**
     * Get remote comments from specific site
     */
    private function get_remote_comments($site_id, $limit) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                id as comment_ID,
                comment_author,
                comment_author_email,
                comment_content,
                comment_date,
                post_title,
                'remote' as comment_type,
                site_id,
                remote_comment_id
            FROM {$wpdb->prefix}ai_remote_comments
            WHERE site_id = %d
            AND moderation_status = 'pending'
            ORDER BY comment_date DESC
            LIMIT %d
        ", $site_id, $limit));
    }
    
    /**
     * Get remote comments from all sites
     */
    private function get_all_remote_comments($limit) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                rc.id as comment_ID,
                rc.comment_author,
                rc.comment_author_email,
                rc.comment_content,
                rc.comment_date,
                rc.post_title,
                'remote' as comment_type,
                rc.site_id,
                rc.remote_comment_id,
                rs.site_name
            FROM {$wpdb->prefix}ai_remote_comments rc
            INNER JOIN {$wpdb->prefix}ai_remote_sites rs ON rc.site_id = rs.id
            WHERE rc.moderation_status = 'pending'
            AND rs.is_active = 1
            ORDER BY rc.comment_date DESC
            LIMIT %d
        ", $limit));
    }
}

// Initialize the batch processor when WordPress is ready
add_action('init', function() {
    new AI_Comment_Moderator_Batch_Processor();
});
