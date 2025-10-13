<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Export_Handler {
    
    /**
     * Export moderation logs to CSV
     */
    public static function export_to_csv($start_date = null, $end_date = null) {
        global $wpdb;
        
        $where = "1=1";
        if ($start_date) {
            $where .= $wpdb->prepare(" AND l.created_at >= %s", $start_date);
        }
        if ($end_date) {
            $where .= $wpdb->prepare(" AND l.created_at <= %s", $end_date);
        }
        
        $logs = $wpdb->get_results("
            SELECT 
                l.id,
                l.comment_id,
                c.comment_author,
                c.comment_author_email,
                c.comment_content,
                p.name as prompt_name,
                l.ai_response,
                l.action_taken,
                l.processing_time,
                l.created_at
            FROM {$wpdb->prefix}ai_comment_logs l
            LEFT JOIN {$wpdb->comments} c ON l.comment_id = c.comment_ID
            LEFT JOIN {$wpdb->prefix}ai_comment_prompts p ON l.prompt_id = p.id
            WHERE {$where}
            ORDER BY l.created_at DESC
        ");
        
        // Generate CSV
        $filename = 'ai-moderation-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'ID', 'Comment ID', 'Author', 'Email', 'Comment', 'Prompt', 
            'AI Response', 'Action', 'Processing Time', 'Date'
        ));
        
        // Data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->comment_id,
                $log->comment_author,
                $log->comment_author_email,
                substr($log->comment_content, 0, 100),
                $log->prompt_name,
                substr($log->ai_response, 0, 100),
                $log->action_taken,
                $log->processing_time,
                $log->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
}

