<?php
/**
 * Claude (Anthropic) Provider
 * 
 * Implementation of AI_Provider_Interface for Anthropic Claude models
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

require_once dirname(__FILE__) . '/ai-provider-interface.php';

class AI_Claude_Provider implements AI_Provider_Interface {
    
    private $api_url = 'https://api.anthropic.com/v1';
    private $api_key;
    private $rate_limiter;
    
    // Pricing per 1M tokens (as of 2024)
    private $pricing = array(
        'claude-3-opus-20240229' => array('input' => 15.00, 'output' => 75.00),
        'claude-3-sonnet-20240229' => array('input' => 3.00, 'output' => 15.00),
        'claude-3-haiku-20240307' => array('input' => 0.25, 'output' => 1.25),
        'claude-3-5-sonnet-20240620' => array('input' => 3.00, 'output' => 15.00)
    );
    
    // User-friendly model names
    private $model_display_names = array(
        'claude-3-opus-20240229' => 'Claude 3 Opus',
        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307' => 'Claude 3 Haiku',
        'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet'
    );
    
    public function __construct() {
        $encrypted_key = get_option('ai_comment_moderator_claude_api_key', '');
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
            // Claude doesn't have a models endpoint, so we test with a minimal message
            $test_payload = array(
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 10,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hi'
                    )
                )
            );
            
            $response = wp_remote_post($this->api_url . '/messages', array(
                'timeout' => 10,
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($test_payload)
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
                $body = wp_remote_retrieve_body($response);
                $error_data = json_decode($body, true);
                $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'HTTP ' . $http_code;
                
                return array(
                    'success' => false,
                    'message' => 'Error: ' . $error_message
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Successfully connected to Claude. ' . count($this->model_display_names) . ' model(s) available.',
                'data' => array(
                    'models_count' => count($this->model_display_names)
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
        // Claude doesn't have a public models API, so we return our known models
        return array(
            'success' => true,
            'models' => array_keys($this->model_display_names)
        );
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
                'max_tokens' => 300,
                'temperature' => 0.3,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'system' => 'You are a comment moderation assistant. Analyze comments and respond with: decision (spam/ham/toxic/approve), confidence (0-100%), and brief reasoning.'
            );
            
            $response = wp_remote_post($this->api_url . '/messages', array(
                'timeout' => 60,
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => '2023-06-01',
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
                    'error' => 'Rate limit exceeded on Anthropic side. Please try again later.'
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
            
            if (!isset($data['content'][0]['text'])) {
                return array(
                    'success' => false,
                    'error' => 'Invalid response format from Claude'
                );
            }
            
            $ai_response = trim($data['content'][0]['text']);
            $parsed = $this->parse_ai_response($ai_response);
            
            // Calculate costs
            $input_tokens = isset($data['usage']['input_tokens']) ? $data['usage']['input_tokens'] : 0;
            $output_tokens = isset($data['usage']['output_tokens']) ? $data['usage']['output_tokens'] : 0;
            $tokens_used = $input_tokens + $output_tokens;
            
            $cost = $this->calculate_cost($model, $input_tokens, $output_tokens);
            
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
    
    private function calculate_cost($model, $input_tokens, $output_tokens) {
        if (!isset($this->pricing[$model])) {
            // Use Sonnet pricing as default
            $pricing = $this->pricing['claude-3-sonnet-20240229'];
        } else {
            $pricing = $this->pricing[$model];
        }
        
        $input_cost = ($input_tokens / 1000000) * $pricing['input'];
        $output_cost = ($output_tokens / 1000000) * $pricing['output'];
        
        return $input_cost + $output_cost;
    }
    
    private function log_usage($model, $tokens, $cost) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_provider_usage';
        
        $wpdb->insert($table, array(
            'provider' => 'claude',
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
        return 'claude';
    }
    
    public function get_provider_display_name() {
        return 'Claude (Anthropic)';
    }
    
    public function supports_streaming() {
        return true;
    }
    
    public function get_config_fields() {
        return array(
            array(
                'id' => 'claude_api_key',
                'label' => 'API Key',
                'type' => 'password',
                'default' => '',
                'description' => 'Your Anthropic API key (starts with sk-ant-)',
                'required' => true
            ),
            array(
                'id' => 'claude_model',
                'label' => 'Model',
                'type' => 'select',
                'options' => $this->model_display_names,
                'default' => 'claude-3-haiku-20240307',
                'description' => 'Select a Claude model (Haiku is fastest and cheapest)',
                'required' => true
            ),
            array(
                'id' => 'claude_budget_alert',
                'label' => 'Budget Alert (USD/month)',
                'type' => 'number',
                'default' => '10',
                'description' => 'Receive alert when monthly spending exceeds this amount',
                'required' => false
            )
        );
    }
    
    public function validate_config($config) {
        if (empty($config['claude_api_key'])) {
            return array(
                'valid' => false,
                'error' => 'API key is required'
            );
        }
        
        if (!preg_match('/^sk-ant-[a-zA-Z0-9\-_]+$/', $config['claude_api_key'])) {
            return array(
                'valid' => false,
                'error' => 'API key format is invalid (should start with sk-ant-)'
            );
        }
        
        if (empty($config['claude_model'])) {
            return array(
                'valid' => false,
                'error' => 'Model selection is required'
            );
        }
        
        return array('valid' => true);
    }
    
    public function estimate_cost($token_count) {
        $model = get_option('ai_comment_moderator_claude_model', 'claude-3-haiku-20240307');
        
        if (!isset($this->pricing[$model])) {
            return 0;
        }
        
        $pricing = $this->pricing[$model];
        // Estimate 75% input, 25% output
        $input_tokens = $token_count * 0.75;
        $output_tokens = $token_count * 0.25;
        
        return ($input_tokens / 1000000) * $pricing['input'] + ($output_tokens / 1000000) * $pricing['output'];
    }
}

