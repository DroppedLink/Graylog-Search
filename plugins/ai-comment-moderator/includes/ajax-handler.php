<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Ajax_Handler {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        // Ollama connection and model management
        add_action('wp_ajax_ai_moderator_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_ai_moderator_get_models', array($this, 'ajax_get_models'));
        
        // Remote site connection testing
        add_action('wp_ajax_ai_moderator_test_remote_connection', array($this, 'ajax_test_remote_connection'));
        
        // Single comment processing
        add_action('wp_ajax_ai_moderator_process_comment', array($this, 'ajax_process_comment'));
        
        // Prompt management
        add_action('wp_ajax_ai_moderator_test_prompt', array($this, 'ajax_test_prompt'));
        add_action('wp_ajax_ai_moderator_preview_prompt', array($this, 'ajax_preview_prompt'));
        
        // Dashboard data
        add_action('wp_ajax_ai_moderator_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_ai_moderator_get_recent_activity', array($this, 'ajax_get_recent_activity'));
        
        // Batch processing (handled in batch-processor.php)
        // add_action('wp_ajax_ai_moderator_start_batch', array($this, 'ajax_start_batch'));
        // add_action('wp_ajax_ai_moderator_process_batch_chunk', array($this, 'ajax_process_batch_chunk'));
        // add_action('wp_ajax_ai_moderator_get_batch_status', array($this, 'ajax_get_batch_status'));
    }
    
    /**
     * Test Ollama connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $ollama_url = sanitize_url($_POST['ollama_url']);
        if ($ollama_url) {
            // Temporarily update the URL for testing
            $original_url = get_option('ai_comment_moderator_ollama_url');
            update_option('ai_comment_moderator_ollama_url', $ollama_url);
        }
        
        $client = new AI_Comment_Moderator_Ollama_Client();
        $result = $client->test_connection();
        
        if ($ollama_url) {
            // Restore original URL
            update_option('ai_comment_moderator_ollama_url', $original_url);
        }
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Connection successful!',
                'models_count' => $result['models_count']
            ));
        } else {
            wp_send_json_error('Connection failed: ' . $result['error']);
        }
    }
    
    /**
     * Get available Ollama models
     */
    public function ajax_get_models() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $ollama_url = sanitize_url($_POST['ollama_url']);
        if ($ollama_url) {
            // Temporarily update the URL for getting models
            $original_url = get_option('ai_comment_moderator_ollama_url');
            update_option('ai_comment_moderator_ollama_url', $ollama_url);
        }
        
        $client = new AI_Comment_Moderator_Ollama_Client();
        $models = $client->get_available_models();
        
        if ($ollama_url) {
            // Restore original URL
            update_option('ai_comment_moderator_ollama_url', $original_url);
        }
        
        if ($models !== false) {
            wp_send_json_success(array(
                'models' => $models
            ));
        } else {
            wp_send_json_error('Failed to fetch models. Please check your connection.');
        }
    }
    
    /**
     * Process a single comment
     */
    public function ajax_process_comment() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $comment_id = intval($_POST['comment_id']);
        $prompt_id = intval($_POST['prompt_id']);
        
        if (!$comment_id || !$prompt_id) {
            wp_send_json_error('Invalid parameters');
        }
        
        $processor = new AI_Comment_Moderator_Comment_Processor();
        $result = $processor->process_single_comment($comment_id, $prompt_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Comment processed successfully',
                'decision' => $result['decision'],
                'action' => $result['action'],
                'confidence' => $result['confidence'],
                'processing_time' => $result['processing_time']
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Test a prompt with sample data
     */
    public function ajax_test_prompt() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $prompt_text = wp_kses_post($_POST['prompt_text']);
        $test_comment_id = intval($_POST['test_comment_id']);
        
        if (!$prompt_text) {
            wp_send_json_error('No prompt text provided');
        }
        
        // Use a real comment for testing if provided, otherwise use sample data
        if ($test_comment_id) {
            $processed_prompt = AI_Comment_Moderator_Prompt_Manager::process_prompt_template($prompt_text, $test_comment_id);
        } else {
            // Use sample data
            $sample_variables = array(
                '{comment_content}' => 'This is a great article! Thanks for sharing.',
                '{author_name}' => 'John Doe',
                '{author_email}' => 'john@example.com',
                '{post_title}' => 'Sample Blog Post',
                '{comment_date}' => current_time('mysql'),
                '{site_name}' => get_bloginfo('name'),
                '{site_url}' => get_site_url()
            );
            
            $processed_prompt = str_replace(array_keys($sample_variables), array_values($sample_variables), $prompt_text);
        }
        
        // Send to Ollama for testing
        $client = new AI_Comment_Moderator_Ollama_Client();
        $result = $client->generate_response($processed_prompt);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'processed_prompt' => $processed_prompt,
                'ai_response' => $result['response'],
                'processing_time' => $result['processing_time'],
                'model_used' => $result['model_used']
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Preview a prompt with variable substitution
     */
    public function ajax_preview_prompt() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $prompt_text = wp_kses_post($_POST['prompt_text']);
        $comment_id = intval($_POST['comment_id']);
        
        if (!$prompt_text) {
            wp_send_json_error('No prompt text provided');
        }
        
        if ($comment_id) {
            $processed_prompt = AI_Comment_Moderator_Prompt_Manager::process_prompt_template($prompt_text, $comment_id);
        } else {
            // Use sample data
            $sample_variables = array(
                '{comment_content}' => 'This is a sample comment for testing the prompt template.',
                '{author_name}' => 'Sample Author',
                '{author_email}' => 'author@example.com',
                '{post_title}' => 'Sample Post Title',
                '{comment_date}' => current_time('mysql'),
                '{site_name}' => get_bloginfo('name'),
                '{site_url}' => get_site_url()
            );
            
            $processed_prompt = str_replace(array_keys($sample_variables), array_values($sample_variables), $prompt_text);
        }
        
        wp_send_json_success(array(
            'processed_prompt' => $processed_prompt
        ));
    }
    
    /**
     * Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get basic statistics
        $total_comments = wp_count_comments();
        $reviewed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_comment_reviews WHERE ai_reviewed = 1");
        $pending_count = $wpdb->get_var("
            SELECT COUNT(c.comment_ID) 
            FROM {$wpdb->comments} c 
            LEFT JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id 
            WHERE r.comment_id IS NULL AND c.comment_approved = '1'
        ");
        
        // Get processing stats for the last 7 days
        $batch_processor = new AI_Comment_Moderator_Batch_Processor();
        $processing_stats = $batch_processor->get_processing_stats(7);
        
        wp_send_json_success(array(
            'total_comments' => $total_comments->total_comments,
            'reviewed_count' => $reviewed_count,
            'pending_count' => $pending_count,
            'processing_stats' => $processing_stats
        ));
    }
    
    /**
     * Get recent activity
     */
    public function ajax_get_recent_activity() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $limit = intval($_POST['limit']) ?: 10;
        
        $recent_logs = $wpdb->get_results($wpdb->prepare("
            SELECT l.*, c.comment_author, c.comment_content, p.name as prompt_name 
            FROM {$wpdb->prefix}ai_comment_logs l
            LEFT JOIN {$wpdb->comments} c ON l.comment_id = c.comment_ID
            LEFT JOIN {$wpdb->prefix}ai_comment_prompts p ON l.prompt_id = p.id
            ORDER BY l.created_at DESC 
            LIMIT %d
        ", $limit));
        
        wp_send_json_success(array(
            'recent_logs' => $recent_logs
        ));
    }
    
    /**
     * Get comments for selection (used in various interfaces)
     */
    public function ajax_get_comments() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $type = sanitize_text_field($_POST['type']);
        $limit = intval($_POST['limit']) ?: 10;
        $offset = intval($_POST['offset']) ?: 0;
        
        global $wpdb;
        
        switch ($type) {
            case 'unreviewed':
                $comments = $wpdb->get_results($wpdb->prepare("
                    SELECT c.*, p.post_title
                    FROM {$wpdb->comments} c
                    LEFT JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id
                    LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
                    WHERE r.comment_id IS NULL 
                    AND c.comment_approved = '1'
                    ORDER BY c.comment_date DESC
                    LIMIT %d OFFSET %d
                ", $limit, $offset));
                break;
                
            case 'recent':
                $comments = $wpdb->get_results($wpdb->prepare("
                    SELECT c.*, p.post_title
                    FROM {$wpdb->comments} c
                    LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
                    WHERE c.comment_approved = '1'
                    ORDER BY c.comment_date DESC
                    LIMIT %d OFFSET %d
                ", $limit, $offset));
                break;
                
            default:
                wp_send_json_error('Invalid comment type');
        }
        
        wp_send_json_success(array(
            'comments' => $comments
        ));
    }
    
    /**
     * Test remote site connection
     */
    public function ajax_test_remote_connection() {
        check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $app_password = isset($_POST['app_password']) ? $_POST['app_password'] : '';
        
        if (empty($site_url) || empty($username) || empty($app_password)) {
            wp_send_json_error('Missing required fields');
        }
        
        // Test the connection
        $result = AI_Comment_Moderator_Remote_Site_Manager::test_connection(
            $site_url,
            $username,
            $app_password
        );
        
        if ($result['success']) {
            wp_send_json_success(array(
                'user' => $result['user'],
                'roles' => $result['roles']
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
}

// Initialize the AJAX handler when WordPress is ready
add_action('init', function() {
    new AI_Comment_Moderator_Ajax_Handler();
});
