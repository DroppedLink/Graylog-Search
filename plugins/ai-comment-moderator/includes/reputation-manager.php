<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Reputation_Manager {
    
    /**
     * Get or create reputation record for user
     */
    public static function get_reputation($email, $username = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_reputation';
        $reputation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s",
            $email
        ));
        
        if (!$reputation) {
            // Create new reputation record with default score of 50
            $wpdb->insert($table, array(
                'email' => $email,
                'username' => $username,
                'reputation_score' => 50,
                'approved_count' => 0,
                'spam_count' => 0,
                'created_at' => current_time('mysql')
            ));
            
            return self::get_reputation($email, $username);
        }
        
        return $reputation;
    }
    
    /**
     * Update reputation based on comment outcome
     */
    public static function update_reputation($email, $outcome, $username = '') {
        global $wpdb;
        
        $reputation = self::get_reputation($email, $username);
        $table = $wpdb->prefix . 'ai_comment_reputation';
        
        $new_score = $reputation->reputation_score;
        $approved_count = $reputation->approved_count;
        $spam_count = $reputation->spam_count;
        
        switch ($outcome) {
            case 'approved':
                $approved_count++;
                // Increase score, max 100
                $new_score = min(100, $new_score + 2);
                break;
                
            case 'spam':
            case 'trash':
            case 'toxic':
                $spam_count++;
                // Decrease score significantly, min 0
                $new_score = max(0, $new_score - 10);
                break;
                
            case 'hold':
                // Slight decrease for held comments
                $new_score = max(0, $new_score - 1);
                break;
        }
        
        $wpdb->update(
            $table,
            array(
                'reputation_score' => $new_score,
                'approved_count' => $approved_count,
                'spam_count' => $spam_count,
                'last_comment_date' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('email' => $email)
        );
        
        return $new_score;
    }
    
    /**
     * Check if user should skip AI check based on reputation
     */
    public static function should_skip_ai_check($email) {
        $threshold = intval(get_option('ai_comment_moderator_reputation_threshold', 80));
        
        // If threshold is 0 or 100+, never skip
        if ($threshold <= 0 || $threshold > 100) {
            return false;
        }
        
        $reputation = self::get_reputation($email);
        return $reputation->reputation_score >= $threshold;
    }
    
    /**
     * Get all users sorted by reputation
     */
    public static function get_all_reputations($limit = 100, $offset = 0, $order = 'DESC') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_reputation';
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY reputation_score $order LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Get reputation statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_reputation';
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_users,
                AVG(reputation_score) as avg_reputation,
                SUM(approved_count) as total_approved,
                SUM(spam_count) as total_spam,
                COUNT(CASE WHEN reputation_score >= 80 THEN 1 END) as trusted_users,
                COUNT(CASE WHEN reputation_score < 20 THEN 1 END) as flagged_users
            FROM $table
        ");
    }
    
    /**
     * Manually adjust reputation score
     */
    public static function set_reputation($email, $score) {
        global $wpdb;
        
        $score = max(0, min(100, intval($score)));
        $table = $wpdb->prefix . 'ai_comment_reputation';
        
        return $wpdb->update(
            $table,
            array(
                'reputation_score' => $score,
                'updated_at' => current_time('mysql')
            ),
            array('email' => $email)
        );
    }
    
    /**
     * Get user comment history
     */
    public static function get_user_history($email, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, r.ai_decision, r.ai_confidence, l.action_taken
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->prefix}ai_comment_reviews r ON c.comment_ID = r.comment_id
            LEFT JOIN {$wpdb->prefix}ai_comment_logs l ON c.comment_ID = l.comment_id
            WHERE c.comment_author_email = %s
            ORDER BY c.comment_date DESC
            LIMIT %d
        ", $email, $limit));
    }
    
    /**
     * Reset reputation for a user
     */
    public static function reset_reputation($email) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_reputation';
        
        return $wpdb->update(
            $table,
            array(
                'reputation_score' => 50,
                'approved_count' => 0,
                'spam_count' => 0,
                'updated_at' => current_time('mysql')
            ),
            array('email' => $email)
        );
    }
    
    /**
     * Delete reputation record
     */
    public static function delete_reputation($email) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_comment_reputation';
        return $wpdb->delete($table, array('email' => $email));
    }
    
    /**
     * Get reputation badge/label
     */
    public static function get_reputation_label($score) {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Trusted';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Average';
        if ($score >= 20) return 'Poor';
        return 'Flagged';
    }
    
    /**
     * Get reputation color for UI
     */
    public static function get_reputation_color($score) {
        if ($score >= 80) return '#46b450'; // Green
        if ($score >= 60) return '#00a0d2'; // Blue
        if ($score >= 40) return '#ffb900'; // Yellow
        if ($score >= 20) return '#f56e28'; // Orange
        return '#dc3232'; // Red
    }
}

// Initialize reputation manager when WordPress is ready
add_action('init', function() {
    // Hook into comment status changes to update reputation
    add_action('wp_set_comment_status', 'ai_moderator_update_reputation_on_status_change', 10, 2);
});

function ai_moderator_update_reputation_on_status_change($comment_id, $status) {
    $comment = get_comment($comment_id);
    if (!$comment) return;
    
    $email = $comment->comment_author_email;
    if (!$email) return;
    
    // Map comment status to outcome
    $outcome_map = array(
        'approve' => 'approved',
        'spam' => 'spam',
        'trash' => 'trash',
        'hold' => 'hold'
    );
    
    if (isset($outcome_map[$status])) {
        AI_Comment_Moderator_Reputation_Manager::update_reputation(
            $email,
            $outcome_map[$status],
            $comment->comment_author
        );
    }
}
