<?php
/**
 * Ollama AI Provider
 * 
 * Implementation of AI_Provider_Interface for Ollama
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

require_once dirname(__FILE__) . '/ai-provider-interface.php';

class AI_Ollama_Provider implements AI_Provider_Interface {
    
    private $api_url;
    private $rate_limiter;
    
    public function __construct() {
        $this->api_url = rtrim(get_option('ai_comment_moderator_ollama_url', 'http://localhost:11434'), '/');
        $this->rate_limiter = new AI_Comment_Moderator_Rate_Limiter();
    }
    
    /**
     * Test connection to Ollama
     */
    public function test_connection() {
        try {
            $response = wp_remote_get($this->api_url . '/api/tags', array(
                'timeout' => 10,
                'headers' => array(
                    'Content-Type' => 'application/json'
                )
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message()
                );
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                return array(
                    'success' => false,
                    'message' => 'HTTP error: ' . $http_code
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['models'])) {
                return array(
                    'success' => false,
                    'message' => 'Invalid response format from Ollama'
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Successfully connected to Ollama. Found ' . count($data['models']) . ' model(s).',
                'data' => array(
                    'models_count' => count($data['models'])
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get available models from Ollama
     */
    public function get_models() {
        try {
            $response = wp_remote_get($this->api_url . '/api/tags', array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json'
                )
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'models' => array(),
                    'error' => $response->get_error_message()
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['models'])) {
                return array(
                    'success' => false,
                    'models' => array(),
                    'error' => 'Invalid response from Ollama API'
                );
            }
            
            $models = array();
            foreach ($data['models'] as $model) {
                $models[] = $model['name'];
            }
            
            return array(
                'success' => true,
                'models' => $models
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'models' => array(),
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process comment through Ollama
     */
    public function process_comment($comment_data, $prompt, $model) {
        // Check rate limit
        if (!$this->rate_limiter->can_make_request()) {
            return array(
                'success' => false,
                'error' => 'Rate limit exceeded. Please wait ' . $this->rate_limiter->get_wait_time() . ' seconds.'
            );
        }
        
        $start_time = microtime(true);
        
        try {
            $payload = array(
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => array(
                    'temperature' => 0.1, // Low temperature for consistent responses
                    'top_p' => 0.9,
                    'num_predict' => 200 // Limit response length
                )
            );
            
            $response = wp_remote_post($this->api_url . '/api/generate', array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($payload)
            ));
            
            $this->rate_limiter->record_request();
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => 'API request failed: ' . $response->get_error_message()
                );
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                return array(
                    'success' => false,
                    'error' => 'API returned HTTP ' . $http_code
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['response'])) {
                return array(
                    'success' => false,
                    'error' => 'Invalid response format from Ollama'
                );
            }
            
            // Parse AI response
            $ai_response = trim($data['response']);
            $parsed = $this->parse_ai_response($ai_response);
            
            $processing_time = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'decision' => $parsed['decision'],
                'confidence' => $parsed['confidence'],
                'reasoning' => $parsed['reasoning'],
                'tokens' => 0, // Ollama doesn't provide token count
                'cost' => 0, // Ollama is free
                'processing_time' => $processing_time,
                'model_used' => $model,
                'raw_response' => $ai_response
            );
            
        } catch (Exception $e) {
            $this->rate_limiter->record_request();
            return array(
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Parse AI response to extract decision and confidence
     */
    private function parse_ai_response($response) {
        $response_lower = strtolower($response);
        
        // Default values
        $decision = 'hold';
        $confidence = 50;
        $reasoning = $response;
        
        // Extract confidence if present
        if (preg_match('/(\d+)%/', $response, $matches)) {
            $confidence = min(100, max(0, intval($matches[1])));
        }
        
        // Determine decision based on keywords
        if (strpos($response_lower, 'spam') !== false) {
            $decision = 'spam';
            if ($confidence < 60) $confidence = 75; // Default confidence for spam
        } elseif (strpos($response_lower, 'ham') !== false || strpos($response_lower, 'legitimate') !== false) {
            $decision = 'ham';
            if ($confidence < 60) $confidence = 85; // Default confidence for ham
        } elseif (strpos($response_lower, 'toxic') !== false || strpos($response_lower, 'offensive') !== false) {
            $decision = 'toxic';
            if ($confidence < 60) $confidence = 70;
        } elseif (strpos($response_lower, 'approve') !== false) {
            $decision = 'approve';
            if ($confidence < 60) $confidence = 80;
        } elseif (strpos($response_lower, 'reject') !== false) {
            $decision = 'reject';
            if ($confidence < 60) $confidence = 70;
        }
        
        return array(
            'decision' => $decision,
            'confidence' => $confidence,
            'reasoning' => $reasoning
        );
    }
    
    public function get_provider_name() {
        return 'ollama';
    }
    
    public function get_provider_display_name() {
        return 'Ollama';
    }
    
    public function supports_streaming() {
        return true;
    }
    
    public function get_config_fields() {
        return array(
            array(
                'id' => 'ollama_url',
                'label' => 'Ollama URL',
                'type' => 'url',
                'default' => 'http://localhost:11434',
                'description' => 'URL of your Ollama server (e.g., http://localhost:11434)',
                'required' => true
            ),
            array(
                'id' => 'ollama_model',
                'label' => 'Model',
                'type' => 'model_select',
                'default' => '',
                'description' => 'Select a model from your Ollama installation',
                'required' => true
            )
        );
    }
    
    public function validate_config($config) {
        if (empty($config['ollama_url'])) {
            return array(
                'valid' => false,
                'error' => 'Ollama URL is required'
            );
        }
        
        if (empty($config['ollama_model'])) {
            return array(
                'valid' => false,
                'error' => 'Model selection is required'
            );
        }
        
        return array('valid' => true);
    }
    
    public function estimate_cost($token_count) {
        return 0; // Ollama is free (self-hosted)
    }
}

