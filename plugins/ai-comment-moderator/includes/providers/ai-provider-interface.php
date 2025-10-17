<?php
/**
 * AI Provider Interface
 * 
 * Defines the contract that all AI providers must implement
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

interface AI_Provider_Interface {
    
    /**
     * Test connection to the AI provider
     * 
     * @return array {
     *     @type bool   $success Whether connection was successful
     *     @type string $message Success or error message
     *     @type array  $data    Optional additional data (e.g., model count)
     * }
     */
    public function test_connection();
    
    /**
     * Get available models from the provider
     * 
     * @return array {
     *     @type bool  $success Whether the request was successful
     *     @type array $models  Array of model names/IDs
     *     @type string $error  Error message if failed
     * }
     */
    public function get_models();
    
    /**
     * Process a comment through the AI model
     * 
     * @param array  $comment_data Comment data including content, author, etc.
     * @param string $prompt       The prompt/instructions for the AI
     * @param string $model        The specific model to use
     * 
     * @return array {
     *     @type bool   $success    Whether processing succeeded
     *     @type string $decision   The AI's decision (spam, ham, toxic, etc.)
     *     @type int    $confidence Confidence percentage (0-100)
     *     @type string $reasoning  AI's explanation for the decision
     *     @type int    $tokens     Tokens used (if applicable)
     *     @type float  $cost       Cost in USD (if applicable)
     *     @type string $error      Error message if failed
     * }
     */
    public function process_comment($comment_data, $prompt, $model);
    
    /**
     * Get the internal provider name (lowercase, no spaces)
     * 
     * @return string Provider identifier (e.g., 'ollama', 'openai', 'claude')
     */
    public function get_provider_name();
    
    /**
     * Get the display name for the provider
     * 
     * @return string User-friendly provider name (e.g., 'Ollama', 'OpenAI', 'Claude')
     */
    public function get_provider_display_name();
    
    /**
     * Check if provider supports streaming responses
     * 
     * @return bool True if streaming is supported
     */
    public function supports_streaming();
    
    /**
     * Get provider-specific configuration fields
     * 
     * @return array Configuration fields for settings page
     */
    public function get_config_fields();
    
    /**
     * Validate provider configuration
     * 
     * @param array $config Configuration array
     * 
     * @return array {
     *     @type bool   $valid  Whether configuration is valid
     *     @type string $error  Error message if invalid
     * }
     */
    public function validate_config($config);
    
    /**
     * Get cost estimation for processing
     * 
     * @param int $token_count Estimated tokens
     * 
     * @return float Estimated cost in USD
     */
    public function estimate_cost($token_count);
}

