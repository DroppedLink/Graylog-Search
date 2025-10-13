<?php
/**
 * Plugin Name: AI Comment Moderator
 * Description: AI-powered comment moderation using Ollama with configurable prompts, batch processing, and multi-site management
 * Version: 1.0.3
 * Author: CSE
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('AI_COMMENT_MODERATOR_VERSION', '1.0.3');
define('AI_COMMENT_MODERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_COMMENT_MODERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files (order matters - dependencies first)
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/ollama-client.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/reputation-manager.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/webhook-handler.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/remote-site-manager.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/prompt-manager.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/multi-model-processor.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/comment-processor.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/batch-processor.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/background-processor.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/export-handler.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/moderation-queue.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/analytics.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/github-updater.php';
require_once AI_COMMENT_MODERATOR_PLUGIN_DIR . 'includes/settings.php';

// Activation hook
register_activation_hook(__FILE__, 'ai_comment_moderator_activate');
function ai_comment_moderator_activate() {
    // Create database tables
    ai_comment_moderator_create_tables();
    
    // Set default options (only if they don't exist)
    // add_option will not overwrite existing values
    add_option('ai_comment_moderator_ollama_url', 'http://localhost:11434');
    add_option('ai_comment_moderator_ollama_model', '');
    add_option('ai_comment_moderator_batch_size', '10');
    add_option('ai_comment_moderator_auto_process', '0');
    add_option('ai_comment_moderator_rate_limit', '5');
    add_option('ai_comment_moderator_confidence_approve', '90');
    add_option('ai_comment_moderator_confidence_reject', '80');
    add_option('ai_comment_moderator_reputation_threshold', '80');
    add_option('ai_comment_moderator_multi_model_enabled', '0');
    add_option('ai_comment_moderator_webhook_url', '');
    add_option('ai_comment_moderator_webhook_events', 'toxic,spam_high');
    add_option('ai_comment_moderator_keep_data_on_uninstall', '1'); // Default to keeping data
    add_option('ai_comment_moderator_github_token', ''); // For GitHub updates
    
    // Create default prompts (only if none exist)
    ai_comment_moderator_create_default_prompts();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ai_comment_moderator_deactivate');
function ai_comment_moderator_deactivate() {
    // Cleanup if needed
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'ai_comment_moderator_uninstall');
function ai_comment_moderator_uninstall() {
    global $wpdb;
    
    // Check if user wants to keep data
    $keep_data = get_option('ai_comment_moderator_keep_data_on_uninstall', '0');
    
    if ($keep_data === '1') {
        // User wants to keep data - only remove the keep_data flag itself
        // All other data (tables, settings, prompts, remote sites) will be preserved
        delete_option('ai_comment_moderator_keep_data_on_uninstall');
        return; // Exit early, don't delete anything
    }
    
    // User wants to delete everything
    
    // Drop custom tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_comment_reviews");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_comment_prompts");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_comment_logs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_comment_reputation");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_background_jobs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_webhook_log");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_remote_sites");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_remote_comments");
    
    // Delete all options
    delete_option('ai_comment_moderator_ollama_url');
    delete_option('ai_comment_moderator_ollama_model');
    delete_option('ai_comment_moderator_batch_size');
    delete_option('ai_comment_moderator_auto_process');
    delete_option('ai_comment_moderator_rate_limit');
    delete_option('ai_comment_moderator_confidence_approve');
    delete_option('ai_comment_moderator_confidence_reject');
    delete_option('ai_comment_moderator_reputation_threshold');
    delete_option('ai_comment_moderator_multi_model_enabled');
    delete_option('ai_comment_moderator_webhook_url');
    delete_option('ai_comment_moderator_webhook_events');
    delete_option('ai_comment_moderator_keep_data_on_uninstall');
}

// Create database tables
function ai_comment_moderator_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for tracking comment review status
    $table_reviews = $wpdb->prefix . 'ai_comment_reviews';
    $sql_reviews = "CREATE TABLE $table_reviews (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        comment_id bigint(20) NOT NULL,
        ai_reviewed tinyint(1) DEFAULT 0,
        ai_decision varchar(20) DEFAULT '',
        ai_confidence float DEFAULT 0,
        confidence_score float DEFAULT 0,
        prompt_id mediumint(9) DEFAULT NULL,
        requires_manual_review tinyint(1) DEFAULT 0,
        manual_review_status varchar(20) DEFAULT 'pending',
        flagged_reason text,
        processed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY comment_id (comment_id),
        KEY ai_reviewed (ai_reviewed),
        KEY ai_decision (ai_decision),
        KEY requires_manual_review (requires_manual_review),
        KEY confidence_score (confidence_score)
    ) $charset_collate;";
    
    // Table for storing prompts
    $table_prompts = $wpdb->prefix . 'ai_comment_prompts';
    $sql_prompts = "CREATE TABLE $table_prompts (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        prompt_text text NOT NULL,
        action_approve varchar(20) DEFAULT 'approve',
        action_spam varchar(20) DEFAULT 'spam',
        action_trash varchar(20) DEFAULT 'trash',
        category varchar(50) DEFAULT 'general',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY is_active (is_active),
        KEY category (category)
    ) $charset_collate;";
    
    // Table for processing logs
    $table_logs = $wpdb->prefix . 'ai_comment_logs';
    $sql_logs = "CREATE TABLE $table_logs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        comment_id bigint(20) NOT NULL,
        prompt_id mediumint(9) NOT NULL,
        ai_response text,
        action_taken varchar(20),
        processing_time float DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY comment_id (comment_id),
        KEY prompt_id (prompt_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Table for user reputation
    $table_reputation = $wpdb->prefix . 'ai_comment_reputation';
    $sql_reputation = "CREATE TABLE $table_reputation (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(100) NOT NULL,
        username varchar(100) DEFAULT '',
        reputation_score int DEFAULT 50,
        approved_count int DEFAULT 0,
        spam_count int DEFAULT 0,
        last_comment_date datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY reputation_score (reputation_score)
    ) $charset_collate;";
    
    // Table for background jobs
    $table_jobs = $wpdb->prefix . 'ai_background_jobs';
    $sql_jobs = "CREATE TABLE $table_jobs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        job_type varchar(50) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        total_items int DEFAULT 0,
        processed_items int DEFAULT 0,
        data longtext,
        started_at datetime DEFAULT NULL,
        completed_at datetime DEFAULT NULL,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status),
        KEY job_type (job_type),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Table for webhook logs
    $table_webhooks = $wpdb->prefix . 'ai_webhook_log';
    $sql_webhooks = "CREATE TABLE $table_webhooks (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        webhook_url varchar(255) NOT NULL,
        payload longtext,
        response_code int DEFAULT NULL,
        response_body text,
        success tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY event_type (event_type),
        KEY success (success),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Table for remote sites
    $table_remote_sites = $wpdb->prefix . 'ai_remote_sites';
    $sql_remote_sites = "CREATE TABLE $table_remote_sites (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        site_name varchar(255) NOT NULL,
        site_url varchar(255) NOT NULL,
        username varchar(100) NOT NULL,
        app_password text NOT NULL,
        is_active tinyint(1) DEFAULT 1,
        last_sync datetime DEFAULT NULL,
        total_comments int DEFAULT 0,
        pending_moderation int DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY site_url (site_url),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    // Table for remote comments cache
    $table_remote_comments = $wpdb->prefix . 'ai_remote_comments';
    $sql_remote_comments = "CREATE TABLE $table_remote_comments (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        site_id mediumint(9) NOT NULL,
        remote_comment_id bigint(20) NOT NULL,
        comment_author varchar(255),
        comment_author_email varchar(100),
        comment_content text,
        comment_date datetime,
        post_id bigint(20),
        post_title varchar(255),
        comment_status varchar(20),
        moderation_status varchar(20) DEFAULT 'pending',
        ai_decision varchar(20),
        synced_back tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY site_comment (site_id, remote_comment_id),
        KEY site_id (site_id),
        KEY moderation_status (moderation_status),
        KEY synced_back (synced_back)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_reviews);
    dbDelta($sql_prompts);
    dbDelta($sql_logs);
    dbDelta($sql_reputation);
    dbDelta($sql_jobs);
    dbDelta($sql_webhooks);
    dbDelta($sql_remote_sites);
    dbDelta($sql_remote_comments);
}

// Create default prompts
function ai_comment_moderator_create_default_prompts() {
    global $wpdb;
    
    $table_prompts = $wpdb->prefix . 'ai_comment_prompts';
    
    // Check if default prompts already exist
    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_prompts");
    
    if ($existing_count > 0) {
        // Prompts already exist, don't create duplicates
        return;
    }
    
    $default_prompts = array(
        array(
            'name' => 'Spam Detection',
            'prompt_text' => 'Analyze this comment for spam characteristics. Comment: "{comment_content}" by {author_name} ({author_email}) on post "{post_title}". Respond with: SPAM if it\'s spam, APPROVE if it\'s legitimate. Consider promotional content, irrelevant links, repetitive text, and suspicious patterns.',
            'action_approve' => 'approve',
            'action_spam' => 'spam',
            'action_trash' => 'spam',
            'category' => 'spam'
        ),
        array(
            'name' => 'Toxicity Detection',
            'prompt_text' => 'Evaluate this comment for toxic, rude, or inappropriate content. Comment: "{comment_content}" by {author_name} on post "{post_title}". Respond with: TOXIC if inappropriate, APPROVE if acceptable. Look for harassment, hate speech, personal attacks, or offensive language.',
            'action_approve' => 'approve',
            'action_spam' => 'trash',
            'action_trash' => 'trash',
            'category' => 'toxicity'
        ),
        array(
            'name' => 'General Moderation',
            'prompt_text' => 'Review this comment for overall quality and appropriateness. Comment: "{comment_content}" by {author_name} ({author_email}) on post "{post_title}". Respond with: APPROVE for good comments, SPAM for promotional/irrelevant content, TOXIC for inappropriate content. Provide brief reasoning.',
            'action_approve' => 'approve',
            'action_spam' => 'spam',
            'action_trash' => 'trash',
            'category' => 'general'
        )
    );
    
    foreach ($default_prompts as $prompt) {
        $wpdb->insert($table_prompts, $prompt);
    }
}

// Initialize the plugin
add_action('plugins_loaded', 'ai_comment_moderator_init');
function ai_comment_moderator_init() {
    // Plugin initialization code
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'ai_comment_moderator_enqueue_assets');
function ai_comment_moderator_enqueue_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'ai-comment-moderator') === false) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'ai-comment-moderator-style',
        AI_COMMENT_MODERATOR_PLUGIN_URL . 'assets/css/style.css',
        array(),
        AI_COMMENT_MODERATOR_VERSION
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'ai-comment-moderator-script',
        AI_COMMENT_MODERATOR_PLUGIN_URL . 'assets/js/moderator.js',
        array('jquery'),
        AI_COMMENT_MODERATOR_VERSION,
        true
    );
    
    // Pass AJAX URL to JavaScript
    wp_localize_script('ai-comment-moderator-script', 'aiCommentModerator', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_comment_moderator_nonce')
    ));
}
