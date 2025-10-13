<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Add analytics to admin menu
add_action('admin_menu', 'ai_moderator_add_analytics_menu', 12);
function ai_moderator_add_analytics_menu() {
    add_submenu_page(
        'ai-comment-moderator',
        'Analytics',
        'Analytics',
        'manage_options',
        'ai-comment-moderator-analytics',
        'ai_moderator_analytics_page'
    );
}

function ai_moderator_analytics_page() {
    global $wpdb;
    
    // Get statistics
    $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total_processed,
            SUM(CASE WHEN ai_decision = 'approve' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN ai_decision = 'spam' THEN 1 ELSE 0 END) as spam,
            SUM(CASE WHEN ai_decision = 'toxic' THEN 1 ELSE 0 END) as toxic,
            AVG(confidence_score) as avg_confidence
        FROM {$wpdb->prefix}ai_comment_reviews
        WHERE ai_reviewed = 1
    ");
    
    ?>
    <div class="wrap">
        <h1>AI Moderation Analytics</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Processed</h3>
                <span class="stat-number"><?php echo number_format($stats->total_processed); ?></span>
            </div>
            <div class="stat-card">
                <h3>Approved</h3>
                <span class="stat-number"><?php echo number_format($stats->approved); ?></span>
            </div>
            <div class="stat-card">
                <h3>Marked as Spam</h3>
                <span class="stat-number"><?php echo number_format($stats->spam); ?></span>
            </div>
            <div class="stat-card">
                <h3>Toxic/Flagged</h3>
                <span class="stat-number"><?php echo number_format($stats->toxic); ?></span>
            </div>
        </div>
        
        <div class="ai-moderator-widget">
            <h2>Average Confidence</h2>
            <p><strong><?php echo number_format($stats->avg_confidence * 100, 1); ?>%</strong></p>
            <p class="description">Average confidence score across all AI decisions</p>
        </div>
        
        <div class="ai-moderator-widget">
            <h2>Export Data</h2>
            <p>Download complete moderation logs for analysis</p>
            <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-analytics&export=csv'); ?>" class="button button-primary">Export to CSV</a>
        </div>
    </div>
    <?php
    
    // Handle export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        AI_Comment_Moderator_Export_Handler::export_to_csv();
    }
}

