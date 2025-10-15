<?php
/**
 * OpenRouter Provider
 * 
 * Implementation of AI_Provider_Interface for OpenRouter (unified API for 100+ models)
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

require_once dirname(__FILE__) . '/ai-provider-interface.php';

class AI_OpenRouter_Provider implements AI_Provider_Interface {
    
    private $api_url = 'https://openrouter.ai/api/v1';
    private $api_key;
    private $rate_limiter;
    private $fallback_models = array();
    
    public function __construct() {
        $encrypted_key = get_option('ai_comment_moderator_openrouter_api_key', '');
        $this->api_key = $this->decrypt_api_key($encrypted_key);
        $this->rate_limiter = new AI_Comment_Moderator_Rate_Limiter();
        
        // Get configured fallback models
        $fallbacks = get_option('ai_comment_moderator_openrouter_fallbacks', '');
        if (!empty($fallbacks)) {
            $this->fallback_models = array_map('trim', explode(',', $fallbacks));
        }
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
            
            return array(
                'success' => true,
                'message' => 'Successfully connected to OpenRouter. Found ' . count($data['data']) . ' model(s).',
                'data' => array(
                    'models_count' => count($data['data'])
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
        
        // Try to get from cache first
        $cached_models = get_transient('ai_moderator_openrouter_models');
        if ($cached_models !== false) {
            return array(
                'success' => true,
                'models' => $cached_models
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
                    'error' => 'Invalid response from OpenRouter API'
                );
            }
            
            // Extract model IDs and sort by popularity/recommended
            $models = array();
            foreach ($data['data'] as $model) {
                $models[] = array(
                    'id' => $model['id'],
                    'name' => isset($model['name']) ? $model['name'] : $model['id'],
                    'pricing' => isset($model['pricing']) ? $model['pricing'] : array(),
                    'context_length' => isset($model['context_length']) ? $model['context_length'] : 0
                );
            }
            
            // Sort by name
            usort($models, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            // Cache for 1 hour
            set_transient('ai_moderator_openrouter_models', $models, HOUR_IN_SECONDS);
            
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
        
        // Try primary model first, then fallbacks
        $models_to_try = array_merge(array($model), $this->fallback_models);
        $last_error = '';
        
        foreach ($models_to_try as $current_model) {
            $result = $this->try_process_with_model($comment_data, $prompt, $current_model);
            
            if ($result['success']) {
                // Add note if we used a fallback
                if ($current_model !== $model) {
                    $result['fallback_used'] = true;
                    $result['fallback_model'] = $current_model;
                    $result['original_model'] = $model;
                }
                return $result;
            }
            
            $last_error = $result['error'];
        }
        
        // All attempts failed
        return array(
            'success' => false,
            'error' => 'All models failed. Last error: ' . $last_error
        );
    }
    
    private function try_process_with_model($comment_data, $prompt, $model) {
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
                    'HTTP-Referer' => home_url(),
                    'X-Title' => get_bloginfo('name'),
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
                    'error' => 'Rate limit exceeded'
                );
            }
            
            if ($http_code === 402) {
                return array(
                    'success' => false,
                    'error' => 'Insufficient credits'
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
                    'error' => 'Invalid response format from OpenRouter'
                );
            }
            
            $ai_response = trim($data['choices'][0]['message']['content']);
            $parsed = $this->parse_ai_response($ai_response);
            
            // Get usage data from headers or response
            $tokens_used = 0;
            $cost = 0;
            
            if (isset($data['usage']['total_tokens'])) {
                $tokens_used = $data['usage']['total_tokens'];
            }
            
            // OpenRouter provides cost in response headers
            $headers = wp_remote_retrieve_headers($response);
            if (isset($headers['x-ratelimit-cost'])) {
                $cost = floatval($headers['x-ratelimit-cost']);
            }
            
            // Log usage
            if ($tokens_used > 0 || $cost > 0) {
                $this->log_usage($model, $tokens_used, $cost);
            }
            
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
    
    private function log_usage($model, $tokens, $cost) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_provider_usage';
        
        $wpdb->insert($table, array(
            'provider' => 'openrouter',
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
        return 'openrouter';
    }
    
    public function get_provider_display_name() {
        return 'OpenRouter';
    }
    
    public function supports_streaming() {
        return true;
    }
    
    public function get_config_fields() {
        return array(
            array(
                'id' => 'openrouter_api_key',
                'label' => 'API Key',
                'type' => 'password',
                'default' => '',
                'description' => 'Your OpenRouter API key (get one at <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a>)',
                'required' => true
            ),
            array(
                'id' => 'openrouter_model',
                'label' => 'Primary Model',
                'type' => 'model_select',
                'default' => 'openai/gpt-3.5-turbo',
                'description' => 'Select a model from 100+ options',
                'required' => true
            ),
            array(
                'id' => 'openrouter_fallbacks',
                'label' => 'Fallback Models',
                'type' => 'text',
                'default' => '',
                'description' => 'Comma-separated list of model IDs to try if primary fails (e.g., "anthropic/claude-3-haiku,meta-llama/llama-3-8b")',
                'required' => false
            ),
            array(
                'id' => 'openrouter_budget_alert',
                'label' => 'Budget Alert (USD/month)',
                'type' => 'number',
                'default' => '10',
                'description' => 'Receive alert when monthly spending exceeds this amount',
                'required' => false
            )
        );
    }
    
    public function validate_config($config) {
        if (empty($config['openrouter_api_key'])) {
            return array(
                'valid' => false,
                'error' => 'API key is required'
            );
        }
        
        if (empty($config['openrouter_model'])) {
            return array(
                'valid' => false,
                'error' => 'Model selection is required'
            );
        }
        
        return array('valid' => true);
    }
    
    public function estimate_cost($token_count) {
        // OpenRouter pricing varies by model, return a generic estimate
        // Average cost across popular models is roughly $0.001 per 1K tokens
        return ($token_count / 1000) * 0.001;
    }
}

