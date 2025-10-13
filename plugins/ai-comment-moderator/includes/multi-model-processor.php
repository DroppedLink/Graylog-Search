<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Multi_Model_Processor {
    
    private $ollama_client;
    
    public function __construct() {
        $this->ollama_client = new AI_Comment_Moderator_Ollama_Client();
    }
    
    /**
     * Process comment through multiple models and get consensus
     */
    public function process_with_consensus($prompt, $models = array()) {
        if (empty($models)) {
            // Get models from settings
            $models = $this->get_configured_models();
        }
        
        if (count($models) < 2) {
            // Fall back to single model processing
            return $this->ollama_client->generate_response($prompt);
        }
        
        $responses = array();
        $start_time = microtime(true);
        
        // Process with each model
        foreach ($models as $model) {
            $response = $this->ollama_client->generate_response($prompt, $model);
            if ($response['success']) {
                $responses[] = array(
                    'model' => $model,
                    'response' => $response['response'],
                    'processing_time' => $response['processing_time']
                );
            }
        }
        
        if (empty($responses)) {
            return array(
                'success' => false,
                'error' => 'All models failed to respond',
                'processing_time' => microtime(true) - $start_time
            );
        }
        
        // Analyze consensus
        $consensus = $this->analyze_consensus($responses);
        
        return array(
            'success' => true,
            'response' => $consensus['decision'],
            'consensus' => $consensus,
            'individual_responses' => $responses,
            'processing_time' => microtime(true) - $start_time,
            'models_used' => array_column($responses, 'model')
        );
    }
    
    /**
     * Analyze responses and determine consensus
     */
    private function analyze_consensus($responses) {
        $decisions = array();
        $vote_counts = array(
            'APPROVE' => 0,
            'SPAM' => 0,
            'TOXIC' => 0,
            'UNCLEAR' => 0
        );
        
        // Parse each response
        foreach ($responses as $response) {
            $text = strtoupper($response['response']);
            
            $decision = 'UNCLEAR';
            if (strpos($text, 'SPAM') !== false) {
                $decision = 'SPAM';
            } elseif (strpos($text, 'TOXIC') !== false || strpos($text, 'INAPPROPRIATE') !== false) {
                $decision = 'TOXIC';
            } elseif (strpos($text, 'APPROVE') !== false) {
                $decision = 'APPROVE';
            }
            
            $decisions[] = array(
                'model' => $response['model'],
                'decision' => $decision,
                'raw_response' => $response['response']
            );
            
            $vote_counts[$decision]++;
        }
        
        // Determine winner (majority vote)
        arsort($vote_counts);
        $winner = array_key_first($vote_counts);
        $winner_count = $vote_counts[$winner];
        $total_votes = count($responses);
        
        // Calculate confidence based on agreement
        $confidence = $winner_count / $total_votes;
        
        // Check for tie
        $top_two = array_slice($vote_counts, 0, 2, true);
        $has_tie = count($top_two) >= 2 && $top_two[array_key_first($top_two)] === $top_two[array_keys($top_two)[1]];
        
        return array(
            'decision' => $winner,
            'confidence' => $confidence,
            'vote_counts' => $vote_counts,
            'individual_decisions' => $decisions,
            'has_consensus' => $confidence >= 0.67, // 2/3 or better
            'has_tie' => $has_tie,
            'requires_manual_review' => $has_tie || $confidence < 0.67
        );
    }
    
    /**
     * Get configured models for consensus
     */
    private function get_configured_models() {
        $primary_model = get_option('ai_comment_moderator_ollama_model', '');
        $secondary_models = get_option('ai_comment_moderator_secondary_models', '');
        
        $models = array($primary_model);
        
        if (!empty($secondary_models)) {
            $secondary = array_map('trim', explode(',', $secondary_models));
            $models = array_merge($models, $secondary);
        }
        
        return array_filter($models);
    }
    
    /**
     * Check if multi-model processing is enabled
     */
    public static function is_enabled() {
        return get_option('ai_comment_moderator_multi_model_enabled', '0') === '1';
    }
}

