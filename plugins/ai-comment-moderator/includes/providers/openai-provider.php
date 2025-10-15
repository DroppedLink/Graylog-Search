<?php
/**
 * OpenAI Provider
 * 
 * Implementation of AI_Provider_Interface for OpenAI (GPT models)
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

require_once dirname(__FILE__) . '/ai-provider-interface.php';

class AI_OpenAI_Provider implements AI_Provider_Interface {
    
    private $api_url = 'https://api.openai.com/v1';
    private $api_key;
    private $rate_limiter;
    
    // Pricing per 1K tokens (as of 2024)
    private $pricing = array(
        'gpt-3.5-turbo' => array('input' => 0.0005, 'output' => 0.0015),
        'gpt-4' => array('input' => 0.03, 'output' => 0.06),
        'gpt-4-turbo' => array('input' => 0.01, 'output' => 0.03),
        'gpt-4o' => array('input' => 0.005, 'output' => 0.015)
    );
    
    public function __construct() {
        $encrypted_key = get_option('ai_comment_moderator_openai_api_key', '');
        $this->api_key = $this->decrypt_api_key($encrypted_key);
        $this->rate_limiter = new AI_Comment_Moderator_Rate_Limiter();
    }
    
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key not configured'
            );
        }
        
        try {
            $response = wp_remote_get($this->api_url . '/models', array(
                'timeout' => 10,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
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
            if ($http_code === 401) {
                return array(
                    'success' => false,
                    'message' => 'Invalid API key'
                );
            }
            
            if ($http_code !== 200) {
                return array(
                    'success' => false,
                    'message' => 'HTTP error: ' . $http_code
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['data'])) {
                return array(
                    'success' => false,
                    'message' => 'Invalid response format'
                );
            }
            
            // Count GPT models
            $gpt_models = array_filter($data['data'], function($model) {
                return strpos($model['id'], 'gpt') !== false;
            });
            
            return array(
                'success' => true,
                'message' => 'Successfully connected to OpenAI. Found ' . count($gpt_models) . ' GPT model(s).',
                'data' => array(
                    'models_count' => count($gpt_models)
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    public function get_models() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'models' => array(),
                'error' => 'API key not configured'
            );
        }
        
        try {
            $response = wp_remote_get($this->api_url . '/models', array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
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
            
            if (!isset($data['data'])) {
                return array(
                    'success' => false,
                    'models' => array(),
                    'error' => 'Invalid response from OpenAI API'
                );
            }
            
            // Filter for chat models only
            $models = array();
            $preferred_models = array('gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo');
            
            foreach ($preferred_models as $model_id) {
                foreach ($data['data'] as $model) {
                    if (strpos($model['id'], $model_id) === 0 && !in_array($model['id'], $models)) {
                        $models[] = $model['id'];
                        break; // Only add first match for each preferred model
                    }
                }
            }
            
            // If no preferred models found, get all GPT chat models
            if (empty($models)) {
                foreach ($data['data'] as $model) {
                    if (strpos($model['id'], 'gpt') === 0) {
                        $models[] = $model['id'];
                    }
                }
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
    
    public function process_comment($comment_data, $prompt, $model) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'API key not configured'
            );
        }
        
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
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a comment moderation assistant. Analyze comments and respond with: decision (spam/ham/toxic/approve), confidence (0-100%), and brief reasoning.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.3,
                'max_tokens' => 200
            );
            
            $response = wp_remote_post($this->api_url . '/chat/completions', array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
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
            
            if ($http_code === 401) {
                return array(
                    'success' => false,
                    'error' => 'Invalid API key'
                );
            }
            
            if ($http_code === 429) {
                return array(
                    'success' => false,
                    'error' => 'Rate limit exceeded on OpenAI side. Please try again later.'
                );
            }
            
            if ($http_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                $error_data = json_decode($body, true);
                $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'HTTP ' . $http_code;
                
                return array(
                    'success' => false,
                    'error' => 'API error: ' . $error_message
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['choices'][0]['message']['content'])) {
                return array(
                    'success' => false,
                    'error' => 'Invalid response format from OpenAI'
                );
            }
            
            $ai_response = trim($data['choices'][0]['message']['content']);
            $parsed = $this->parse_ai_response($ai_response);
            
            // Calculate costs
            $tokens_used = isset($data['usage']['total_tokens']) ? $data['usage']['total_tokens'] : 0;
            $prompt_tokens = isset($data['usage']['prompt_tokens']) ? $data['usage']['prompt_tokens'] : 0;
            $completion_tokens = isset($data['usage']['completion_tokens']) ? $data['usage']['completion_tokens'] : 0;
            
            $cost = $this->calculate_cost($model, $prompt_tokens, $completion_tokens);
            
            // Log usage
            $this->log_usage($model, $tokens_used, $cost);
            
            $processing_time = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'decision' => $parsed['decision'],
                'confidence' => $parsed['confidence'],
                'reasoning' => $parsed['reasoning'],
                'tokens' => $tokens_used,
                'cost' => $cost,
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
    
    private function parse_ai_response($response) {
        $response_lower = strtolower($response);
        
        $decision = 'hold';
        $confidence = 50;
        $reasoning = $response;
        
        // Extract confidence
        if (preg_match('/(\d+)%/', $response, $matches)) {
            $confidence = min(100, max(0, intval($matches[1])));
        }
        
        // Determine decision
        if (strpos($response_lower, 'spam') !== false) {
            $decision = 'spam';
            if ($confidence < 60) $confidence = 80;
        } elseif (strpos($response_lower, 'ham') !== false || strpos($response_lower, 'legitimate') !== false) {
            $decision = 'ham';
            if ($confidence < 60) $confidence = 85;
        } elseif (strpos($response_lower, 'toxic') !== false || strpos($response_lower, 'offensive') !== false) {
            $decision = 'toxic';
            if ($confidence < 60) $confidence = 75;
        } elseif (strpos($response_lower, 'approve') !== false) {
            $decision = 'approve';
            if ($confidence < 60) $confidence = 80;
        } elseif (strpos($response_lower, 'reject') !== false) {
            $decision = 'reject';
            if ($confidence < 60) $confidence = 75;
        }
        
        return array(
            'decision' => $decision,
            'confidence' => $confidence,
            'reasoning' => $reasoning
        );
    }
    
    private function calculate_cost($model, $prompt_tokens, $completion_tokens) {
        if (!isset($this->pricing[$model])) {
            // Use gpt-4 pricing as default for unknown models
            $pricing = $this->pricing['gpt-4'];
        } else {
            $pricing = $this->pricing[$model];
        }
        
        $input_cost = ($prompt_tokens / 1000) * $pricing['input'];
        $output_cost = ($completion_tokens / 1000) * $pricing['output'];
        
        return $input_cost + $output_cost;
    }
    
    private function log_usage($model, $tokens, $cost) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_provider_usage';
        
        $wpdb->insert($table, array(
            'provider' => 'openai',
            'model' => $model,
            'tokens_used' => $tokens,
            'cost_usd' => $cost,
            'comments_processed' => 1,
            'date' => current_time('Y-m-d'),
            'created_at' => current_time('mysql')
        ));
    }
    
    private function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }
        
        if (defined('AUTH_KEY') && AUTH_KEY) {
            return base64_encode($api_key . '::' . md5(AUTH_KEY));
        }
        return base64_encode($api_key);
    }
    
    private function decrypt_api_key($encrypted) {
        if (empty($encrypted)) {
            return '';
        }
        
        $decoded = base64_decode($encrypted);
        if (strpos($decoded, '::') !== false) {
            list($api_key, $hash) = explode('::', $decoded, 2);
            return $api_key;
        }
        return $decoded;
    }
    
    public function get_provider_name() {
        return 'openai';
    }
    
    public function get_provider_display_name() {
        return 'OpenAI';
    }
    
    public function supports_streaming() {
        return true;
    }
    
    public function get_config_fields() {
        return array(
            array(
                'id' => 'openai_api_key',
                'label' => 'API Key',
                'type' => 'password',
                'default' => '',
                'description' => 'Your OpenAI API key (starts with sk-)',
                'required' => true
            ),
            array(
                'id' => 'openai_model',
                'label' => 'Model',
                'type' => 'model_select',
                'default' => 'gpt-3.5-turbo',
                'description' => 'Select a GPT model',
                'required' => true
            ),
            array(
                'id' => 'openai_budget_alert',
                'label' => 'Budget Alert (USD/month)',
                'type' => 'number',
                'default' => '10',
                'description' => 'Receive alert when monthly spending exceeds this amount',
                'required' => false
            )
        );
    }
    
    public function validate_config($config) {
        if (empty($config['openai_api_key'])) {
            return array(
                'valid' => false,
                'error' => 'API key is required'
            );
        }
        
        if (!preg_match('/^sk-[a-zA-Z0-9\-_]+$/', $config['openai_api_key'])) {
            return array(
                'valid' => false,
                'error' => 'API key format is invalid (should start with sk-)'
            );
        }
        
        if (empty($config['openai_model'])) {
            return array(
                'valid' => false,
                'error' => 'Model selection is required'
            );
        }
        
        return array('valid' => true);
    }
    
    public function estimate_cost($token_count) {
        $model = get_option('ai_comment_moderator_openai_model', 'gpt-3.5-turbo');
        
        if (!isset($this->pricing[$model])) {
            return 0;
        }
        
        $pricing = $this->pricing[$model];
        // Estimate 75% input, 25% output
        $input_tokens = $token_count * 0.75;
        $output_tokens = $token_count * 0.25;
        
        return ($input_tokens / 1000) * $pricing['input'] + ($output_tokens / 1000) * $pricing['output'];
    }
}

