<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Background_Processor {
    
    /**
     * Create a background job
     */
    public static function create_job($job_type, $data, $total_items = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_background_jobs';
        
        $wpdb->insert($table, array(
            'job_type' => $job_type,
            'status' => 'pending',
            'total_items' => $total_items,
            'processed_items' => 0,
            'data' => json_encode($data),
            'created_at' => current_time('mysql')
        ));
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get job status
     */
    public static function get_job($job_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_background_jobs';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $job_id
        ));
    }
    
    /**
     * Update job progress
     */
    public static function update_job_progress($job_id, $processed_items) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_background_jobs';
        
        $wpdb->update(
            $table,
            array(
                'processed_items' => $processed_items,
                'status' => 'processing'
            ),
            array('id' => $job_id)
        );
    }
    
    /**
     * Mark job as completed
     */
    public static function complete_job($job_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_background_jobs';
        
        $wpdb->update(
            $table,
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ),
            array('id' => $job_id)
        );
    }
    
    /**
     * Mark job as failed
     */
    public static function fail_job($job_id, $error_message) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_background_jobs';
        
        $wpdb->update(
            $table,
            array(
                'status' => 'failed',
                'error_message' => $error_message,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $job_id)
        );
    }
}

