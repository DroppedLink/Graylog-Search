<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Ollama_Client {
    
    private $api_url;
    private $rate_limiter;
    
    public function __construct() {
        $this->api_url = rtrim(get_option('ai_comment_moderator_ollama_url', 'http://localhost:11434'), '/');
        $this->rate_limiter = new AI_Comment_Moderator_Rate_Limiter();
    }
    
    /**
     * Get available models from Ollama
     */
    public function get_available_models() {
        try {
            $response = wp_remote_get($this->api_url . '/api/tags', array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json'
                )
            ));
            
            if (is_wp_error($response)) {
                error_log('AI Comment Moderator: Ollama connection error - ' . $response->get_error_message());
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['models'])) {
                error_log('AI Comment Moderator: Invalid response from Ollama API');
                return false;
            }
            
            $models = array();
            foreach ($data['models'] as $model) {
                $models[] = $model['name'];
            }
            
            return $models;
            
        } catch (Exception $e) {
            error_log('AI Comment Moderator: Exception getting models - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send a prompt to Ollama and get response
     */
    public function generate_response($prompt, $model = null) {
        if (!$this->rate_limiter->can_make_request()) {
            return array(
                'success' => false,
                'error' => 'Rate limit exceeded. Please wait before making more requests.'
            );
        }
        
        if (!$model) {
            $model = get_option('ai_comment_moderator_ollama_model', '');
            if (empty($model)) {
                return array(
                    'success' => false,
                    'error' => 'No model selected. Please configure a model in settings.'
                );
            }
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
                    'num_predict' => 100 // Limit response length
                )
            );
            
            $response = wp_remote_post($this->api_url . '/api/generate', array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($payload)
            ));
            
            if (is_wp_error($response)) {
                $this->rate_limiter->record_request();
                return array(
                    'success' => false,
                    'error' => 'API request failed: ' . $response->get_error_message(),
                    'processing_time' => microtime(true) - $start_time
                );
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                $this->rate_limiter->record_request();
                return array(
                    'success' => false,
                    'error' => 'API returned HTTP ' . $http_code,
                    'processing_time' => microtime(true) - $start_time
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['response'])) {
                $this->rate_limiter->record_request();
                return array(
                    'success' => false,
                    'error' => 'Invalid response format from Ollama',
                    'processing_time' => microtime(true) - $start_time
                );
            }
            
            $this->rate_limiter->record_request();
            
            return array(
                'success' => true,
                'response' => trim($data['response']),
                'processing_time' => microtime(true) - $start_time,
                'model_used' => $model
            );
            
        } catch (Exception $e) {
            $this->rate_limiter->record_request();
            error_log('AI Comment Moderator: Exception during API call - ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'processing_time' => microtime(true) - $start_time
            );
        }
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
                    'error' => $response->get_error_message()
                );
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                return array(
                    'success' => false,
                    'error' => 'HTTP ' . $http_code
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['models'])) {
                return array(
                    'success' => false,
                    'error' => 'Invalid response format'
                );
            }
            
            return array(
                'success' => true,
                'models_count' => count($data['models'])
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
}

/**
 * Simple rate limiter to prevent API overload
 */
class AI_Comment_Moderator_Rate_Limiter {
    
    private $option_name = 'ai_comment_moderator_rate_limiter';
    
    public function can_make_request() {
        $rate_limit = intval(get_option('ai_comment_moderator_rate_limit', 5));
        $requests = $this->get_recent_requests();
        
        // Clean old requests (older than 1 minute)
        $cutoff_time = time() - 60;
        $requests = array_filter($requests, function($timestamp) use ($cutoff_time) {
            return $timestamp > $cutoff_time;
        });
        
        return count($requests) < $rate_limit;
    }
    
    public function record_request() {
        $requests = $this->get_recent_requests();
        $requests[] = time();
        
        // Keep only last 100 requests to prevent option from growing too large
        if (count($requests) > 100) {
            $requests = array_slice($requests, -100);
        }
        
        update_option($this->option_name, $requests);
    }
    
    private function get_recent_requests() {
        $requests = get_option($this->option_name, array());
        return is_array($requests) ? $requests : array();
    }
    
    public function get_wait_time() {
        $requests = $this->get_recent_requests();
        if (empty($requests)) {
            return 0;
        }
        
        // Find the oldest request in the current minute
        $cutoff_time = time() - 60;
        $recent_requests = array_filter($requests, function($timestamp) use ($cutoff_time) {
            return $timestamp > $cutoff_time;
        });
        
        if (empty($recent_requests)) {
            return 0;
        }
        
        $oldest_request = min($recent_requests);
        $wait_time = 60 - (time() - $oldest_request);
        
        return max(0, $wait_time);
    }
}
