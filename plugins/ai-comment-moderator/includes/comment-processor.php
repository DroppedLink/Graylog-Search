<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Comment_Processor {
    
    private $ollama_client;
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Get Ollama client instance (lazy loading)
     */
    private function get_ollama_client() {
        if (!$this->ollama_client) {
            $this->ollama_client = new AI_Comment_Moderator_Ollama_Client();
        }
        return $this->ollama_client;
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Process new comments if auto-processing is enabled
        add_action('wp_insert_comment', array($this, 'process_new_comment'), 10, 2);
        
        // Add comment meta box to edit comment screen
        add_action('add_meta_boxes_comment', array($this, 'add_comment_meta_box'));
        
        // Save comment meta when comment is updated
        add_action('edit_comment', array($this, 'save_comment_meta'));
    }
    
    /**
     * Process a new comment
     */
    public function process_new_comment($comment_id, $comment_approved) {
        // Only process if auto-processing is enabled and comment is approved
        if (!get_option('ai_comment_moderator_auto_process', '0') || $comment_approved !== 1) {
            return;
        }
        
        // Get the default prompt for auto-processing
        global $wpdb;
        $prompt = $wpdb->get_row("
            SELECT * FROM {$wpdb->prefix}ai_comment_prompts 
            WHERE is_active = 1 AND category = 'general' 
            ORDER BY id ASC 
            LIMIT 1
        ");
        
        if (!$prompt) {
            // No suitable prompt found, skip processing
            return;
        }
        
        // Process the comment
        $this->process_single_comment($comment_id, $prompt->id);
    }
    
    /**
     * Process a single comment with a specific prompt
     */
    public function process_single_comment($comment_id, $prompt_id) {
        global $wpdb;
        
        // Get the prompt
        $prompt = AI_Comment_Moderator_Prompt_Manager::get_prompt($prompt_id);
        if (!$prompt) {
            return array(
                'success' => false,
                'error' => 'Prompt not found'
            );
        }
        
        // Get the comment
        $comment = get_comment($comment_id);
        if (!$comment) {
            return array(
                'success' => false,
                'error' => 'Comment not found'
            );
        }
        
        // Check if already processed
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ai_comment_reviews 
            WHERE comment_id = %d
        ", $comment_id));
        
        $start_time = microtime(true);
        
        // Process the prompt template
        $processed_prompt = AI_Comment_Moderator_Prompt_Manager::process_prompt_template($prompt->prompt_text, $comment_id);
        
        // Send to Ollama
        $ai_response = $this->get_ollama_client()->generate_response($processed_prompt);
        
        if (!$ai_response['success']) {
            // Log the error
            $this->log_processing_error($comment_id, $prompt_id, $ai_response['error']);
            return $ai_response;
        }
        
        // Parse the AI response
        $decision_data = AI_Comment_Moderator_Prompt_Manager::parse_ai_response($ai_response['response'], $prompt);
        
        // Check if this is a remote comment
        $remote_comment = $wpdb->get_row($wpdb->prepare(
            "SELECT site_id, remote_comment_id FROM {$wpdb->prefix}ai_remote_comments WHERE id = %d",
            $comment_id
        ));
        
        // Apply the action
        if ($remote_comment) {
            // For remote comments, update local database and sync back
            $this->apply_remote_comment_action($remote_comment->site_id, $remote_comment->remote_comment_id, $decision_data['action']);
            
            // Update local remote comment record
            $wpdb->update(
                $wpdb->prefix . 'ai_remote_comments',
                array(
                    'ai_decision' => $decision_data['action'],
                    'moderation_status' => 'processed'
                ),
                array('id' => $comment_id)
            );
        } else {
            // For local comments, apply action normally
            $action_result = $this->apply_comment_action($comment_id, $decision_data['action']);
        }
        
        // Record the review
        $review_data = array(
            'comment_id' => $comment_id,
            'ai_reviewed' => 1,
            'ai_decision' => $decision_data['decision'],
            'ai_confidence' => $decision_data['confidence'],
            'prompt_id' => $prompt_id,
            'processed_at' => current_time('mysql')
        );
        
        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'ai_comment_reviews',
                $review_data,
                array('comment_id' => $comment_id)
            );
        } else {
            $wpdb->insert($wpdb->prefix . 'ai_comment_reviews', $review_data);
        }
        
        // Log the processing
        $this->log_processing_result(
            $comment_id,
            $prompt_id,
            $ai_response['response'],
            $decision_data['action'],
            $ai_response['processing_time']
        );
        
        return array(
            'success' => true,
            'decision' => $decision_data['decision'],
            'action' => $decision_data['action'],
            'confidence' => $decision_data['confidence'],
            'ai_response' => $ai_response['response'],
            'processing_time' => $ai_response['processing_time'],
            'action_result' => $action_result
        );
    }
    
    /**
     * Apply an action to a comment
     */
    private function apply_comment_action($comment_id, $action) {
        switch ($action) {
            case 'approve':
                return wp_set_comment_status($comment_id, 'approve');
                
            case 'spam':
                return wp_spam_comment($comment_id);
                
            case 'trash':
                return wp_trash_comment($comment_id);
                
            case 'hold':
                return wp_set_comment_status($comment_id, 'hold');
                
            default:
                return false;
        }
    }
    
    /**
     * Log processing result
     */
    private function log_processing_result($comment_id, $prompt_id, $ai_response, $action_taken, $processing_time) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ai_comment_logs',
            array(
                'comment_id' => $comment_id,
                'prompt_id' => $prompt_id,
                'ai_response' => $ai_response,
                'action_taken' => $action_taken,
                'processing_time' => $processing_time,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Log processing error
     */
    private function log_processing_error($comment_id, $prompt_id, $error) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ai_comment_logs',
            array(
                'comment_id' => $comment_id,
                'prompt_id' => $prompt_id,
                'ai_response' => 'ERROR: ' . $error,
                'action_taken' => 'error',
                'processing_time' => 0,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get unreviewed comments
     * 
     * @param int $limit Number of comments to retrieve
     * @param int $offset Offset for pagination
     * @param string $status Filter by comment status: 'approved', 'all', 'pending', 'spam'
     * @param bool $include_reviewed Include already reviewed comments (for re-processing)
     */
    public function get_unreviewed_comments($limit = 10, $offset = 0, $status = 'approved', $include_reviewed = false) {
        global $wpdb;
        
        $status_clause = '';
        switch ($status) {
            case 'all':
                $status_clause = "AND c.comment_approved IN ('1', '0')";
                break;
            case 'pending':
                $status_clause = "AND c.comment_approved = '0'";
                break;
            case 'spam':
                $status_clause = "AND c.comment_approved = 'spam'";
                break;
            case 'approved':
            default:
                $status_clause = "AND c.comment_approved = '1'";
                break;
        }
        
        // If include_reviewed is true, get all comments; otherwise only get unreviewed
        $review_clause = $include_reviewed ? '' : 'AND r.comment_id IS NULL';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, p.post_title
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id
            LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE 1=1
            {$review_clause}
            {$status_clause}
            ORDER BY c.comment_date DESC
            LIMIT %d OFFSET %d
        ", $limit, $offset));
    }
    
    /**
     * Get unreviewed comments count
     * 
     * @param string $status Filter by comment status: 'approved', 'all', 'pending', 'spam'
     * @param bool $include_reviewed Include already reviewed comments (for re-processing)
     */
    public function get_unreviewed_count($status = 'approved', $include_reviewed = false) {
        global $wpdb;
        
        $status_clause = '';
        switch ($status) {
            case 'all':
                $status_clause = "AND c.comment_approved IN ('1', '0')";
                break;
            case 'pending':
                $status_clause = "AND c.comment_approved = '0'";
                break;
            case 'spam':
                $status_clause = "AND c.comment_approved = 'spam'";
                break;
            case 'approved':
            default:
                $status_clause = "AND c.comment_approved = '1'";
                break;
        }
        
        // If include_reviewed is true, get all comments; otherwise only get unreviewed
        $review_clause = $include_reviewed ? '' : 'AND r.comment_id IS NULL';
        
        return $wpdb->get_var("
            SELECT COUNT(c.comment_ID)
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id
            WHERE 1=1
            {$review_clause}
            {$status_clause}
        ");
    }
    
    /**
     * Process multiple comments in batch
     */
    public function process_batch($comment_ids, $prompt_id) {
        $results = array(
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );
        
        foreach ($comment_ids as $comment_id) {
            $result = $this->process_single_comment($comment_id, $prompt_id);
            
            $results['processed']++;
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
            
            $results['details'][] = array(
                'comment_id' => $comment_id,
                'result' => $result
            );
            
            // Small delay to prevent overwhelming the API
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }
    
    /**
     * Add meta box to comment edit screen
     */
    public function add_comment_meta_box() {
        add_meta_box(
            'ai-comment-moderator-meta',
            'AI Moderation Status',
            array($this, 'render_comment_meta_box'),
            'comment',
            'normal',
            'high'
        );
    }
    
    /**
     * Render comment meta box
     */
    public function render_comment_meta_box($comment) {
        global $wpdb;
        
        // Get AI review data
        $review = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, p.name as prompt_name
            FROM {$wpdb->prefix}ai_comment_reviews r
            LEFT JOIN {$wpdb->prefix}ai_comment_prompts p ON r.prompt_id = p.id
            WHERE r.comment_id = %d
        ", $comment->comment_ID));
        
        // Get processing logs
        $logs = $wpdb->get_results($wpdb->prepare("
            SELECT l.*, p.name as prompt_name
            FROM {$wpdb->prefix}ai_comment_logs l
            LEFT JOIN {$wpdb->prefix}ai_comment_prompts p ON l.prompt_id = p.id
            WHERE l.comment_id = %d
            ORDER BY l.created_at DESC
        ", $comment->comment_ID));
        
        ?>
        <div class="ai-moderator-meta">
            <?php if ($review && $review->ai_reviewed): ?>
                <div class="ai-status reviewed">
                    <h4>✓ AI Reviewed</h4>
                    <p>
                        <strong>Decision:</strong> <?php echo esc_html(ucfirst($review->ai_decision)); ?><br>
                        <strong>Confidence:</strong> <?php echo number_format($review->ai_confidence * 100, 1); ?>%<br>
                        <strong>Prompt:</strong> <?php echo esc_html($review->prompt_name); ?><br>
                        <strong>Processed:</strong> <?php echo date('M j, Y H:i', strtotime($review->processed_at)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="ai-status not-reviewed">
                    <h4>⚠ Not AI Reviewed</h4>
                    <p>This comment has not been processed by AI moderation.</p>
                    
                    <div class="ai-actions">
                        <select id="ai-prompt-select">
                            <option value="">Select a prompt...</option>
                            <?php
                            $prompts = AI_Comment_Moderator_Prompt_Manager::get_prompts(true);
                            foreach ($prompts as $prompt) {
                                echo '<option value="' . esc_attr($prompt->id) . '">' . esc_html($prompt->name) . '</option>';
                            }
                            ?>
                        </select>
                        <button type="button" class="button" onclick="processComment(<?php echo $comment->comment_ID; ?>)">
                            Process Now
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($logs)): ?>
                <div class="ai-logs">
                    <h4>Processing History</h4>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-header">
                                <strong><?php echo esc_html($log->prompt_name); ?></strong>
                                <span class="log-date"><?php echo date('M j, Y H:i', strtotime($log->created_at)); ?></span>
                                <span class="log-action action-<?php echo esc_attr($log->action_taken); ?>">
                                    <?php echo esc_html(ucfirst($log->action_taken)); ?>
                                </span>
                            </div>
                            <div class="log-response">
                                <?php echo esc_html(wp_trim_words($log->ai_response, 20)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        function processComment(commentId) {
            var promptId = document.getElementById('ai-prompt-select').value;
            if (!promptId) {
                alert('Please select a prompt first.');
                return;
            }
            
            var button = event.target;
            button.disabled = true;
            button.textContent = 'Processing...';
            
            jQuery.post(ajaxurl, {
                action: 'ai_moderator_process_comment',
                comment_id: commentId,
                prompt_id: promptId,
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    button.disabled = false;
                    button.textContent = 'Process Now';
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Save comment meta
     */
    public function save_comment_meta($comment_id) {
        // This can be used to save additional meta data if needed
    }
    
    /**
     * Apply action to remote comment
     */
    private function apply_remote_comment_action($site_id, $remote_comment_id, $action) {
        // Use the remote site manager to sync the decision back
        $result = AI_Comment_Moderator_Remote_Site_Manager::sync_decision_to_remote($site_id, $remote_comment_id, $action);
        
        if (!$result['success']) {
            error_log('AI Moderator: Failed to sync decision to remote site ' . $site_id . ' for comment ' . $remote_comment_id . ': ' . $result['error']);
        }
        
        return $result;
    }
}

// Initialize the comment processor when WordPress is ready
add_action('init', function() {
    new AI_Comment_Moderator_Comment_Processor();
});
