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
    
    // Get reason code statistics (v2.2.0+)
    $reason_stats = $wpdb->get_results("
        SELECT reason_code, COUNT(*) as count 
        FROM {$wpdb->prefix}ai_comment_reviews 
        WHERE reason_code IS NOT NULL
        GROUP BY reason_code 
        ORDER BY reason_code ASC
    ");
    
    $total_with_codes = array_sum(array_column($reason_stats, 'count'));
    
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
        
        <?php if (!empty($reason_stats)): ?>
        <div class="ai-moderator-widget">
            <h2>Top Moderation Reasons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">Code</th>
                        <th>Reason</th>
                        <th style="width: 100px;">Count</th>
                        <th style="width: 100px;">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reason_stats as $stat): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php echo AI_Comment_Moderator_Reason_Codes::format_code_display($stat->reason_code, false); ?>
                            </td>
                            <td><?php echo esc_html(AI_Comment_Moderator_Reason_Codes::get_code_label($stat->reason_code)); ?></td>
                            <td style="text-align: center;"><?php echo number_format($stat->count); ?></td>
                            <td style="text-align: center;">
                                <?php echo $total_with_codes > 0 ? round($stat->count / $total_with_codes * 100, 1) : 0; ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="ai-moderator-widget">
            <h2>Export Data</h2>
            <p>Download complete moderation logs for analysis</p>
            <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-analytics&export=csv'); ?>" class="button button-primary">Export to CSV</a>
        </div>
    </div>
    
    <style>
        .reason-code {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .reason-critical {
            background-color: #d63638;
            color: white;
        }
        .reason-warning {
            background-color: #dba617;
            color: white;
        }
        .reason-approved {
            background-color: #46b450;
            color: white;
        }
        .reason-unknown {
            background-color: #8c8f94;
            color: white;
        }
    </style>
    <?php
    
    // Handle export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        AI_Comment_Moderator_Export_Handler::export_to_csv();
    }
}

