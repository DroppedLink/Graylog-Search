<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Add moderation queue to admin menu
add_action('admin_menu', 'ai_moderator_add_queue_menu', 11);
function ai_moderator_add_queue_menu() {
    add_submenu_page(
        'ai-comment-moderator',
        'Moderation Queue',
        'Review Queue',
        'manage_options',
        'ai-comment-moderator-queue',
        'ai_moderator_queue_page'
    );
}

function ai_moderator_queue_page() {
    global $wpdb;
    
    // Get comments requiring manual review
    $flagged_comments = $wpdb->get_results("
        SELECT c.*, r.ai_decision, r.confidence_score, r.flagged_reason, p.post_title
        FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id
        LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE r.requires_manual_review = 1 
        AND r.manual_review_status = 'pending'
        ORDER BY c.comment_date DESC
        LIMIT 50
    ");
    
    ?>
    <div class="wrap">
        <h1>Moderation Queue</h1>
        <p>Comments flagged for manual review by the AI system</p>
        
        <?php if (empty($flagged_comments)): ?>
            <div class="notice notice-info">
                <p>✓ No comments currently require manual review. All caught up!</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Author</th>
                        <th>Comment</th>
                        <th>Post</th>
                        <th>AI Decision</th>
                        <th>Confidence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flagged_comments as $comment): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($comment->comment_date)); ?></td>
                        <td>
                            <strong><?php echo esc_html($comment->comment_author); ?></strong><br>
                            <small><?php echo esc_html($comment->comment_author_email); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html(wp_trim_words($comment->comment_content, 20)); ?>
                            <?php if ($comment->flagged_reason): ?>
                                <br><small class="description">Reason: <?php echo esc_html($comment->flagged_reason); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($comment->post_title); ?></td>
                        <td>
                            <span class="action-badge action-<?php echo esc_attr($comment->ai_decision); ?>">
                                <?php echo esc_html(ucfirst($comment->ai_decision)); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($comment->confidence_score * 100, 1); ?>%</td>
                        <td>
                            <a href="<?php echo admin_url('comment.php?action=approve&c=' . $comment->comment_ID); ?>" class="button button-small">Approve</a>
                            <a href="<?php echo admin_url('comment.php?action=spam&c=' . $comment->comment_ID); ?>" class="button button-small">Spam</a>
                            <a href="<?php echo admin_url('comment.php?action=trash&c=' . $comment->comment_ID); ?>" class="button button-small">Trash</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

