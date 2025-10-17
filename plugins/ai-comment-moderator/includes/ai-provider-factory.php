<?php
/**
 * AI Provider Factory
 * 
 * Factory class for creating and managing AI provider instances
 * 
 * @package AI_Comment_Moderator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Provider_Factory {
    
    /**
     * Registry of available providers
     * 
     * @var array
     */
    private static $providers = array();
    
    /**
     * Cached provider instances
     * 
     * @var array
     */
    private static $instances = array();
    
    /**
     * Register a provider
     * 
     * @param string $name     Provider name (e.g., 'ollama')
     * @param string $class    Provider class name
     * @param string $file     Provider file path
     */
    public static function register_provider($name, $class, $file) {
        self::$providers[$name] = array(
            'class' => $class,
            'file' => $file
        );
    }
    
    /**
     * Get a provider instance
     * 
     * @param string $name Provider name
     * 
     * @return AI_Provider_Interface|null Provider instance or null if not found
     */
    public static function get_provider($name) {
        // Return cached instance if available
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        
        // Check if provider is registered
        if (!isset(self::$providers[$name])) {
            error_log("AI Moderator: Provider '{$name}' not registered");
            return null;
        }
        
        $provider_info = self::$providers[$name];
        
        // Load provider file
        if (!class_exists($provider_info['class'])) {
            if (file_exists($provider_info['file'])) {
                require_once $provider_info['file'];
            } else {
                error_log("AI Moderator: Provider file not found: {$provider_info['file']}");
                return null;
            }
        }
        
        // Create instance
        $class = $provider_info['class'];
        if (class_exists($class)) {
            self::$instances[$name] = new $class();
            return self::$instances[$name];
        }
        
        error_log("AI Moderator: Provider class not found: {$class}");
        return null;
    }
    
    /**
     * Get the active provider based on settings
     * 
     * @return AI_Provider_Interface|null Active provider instance
     */
    public static function get_active_provider() {
        $active_provider = get_option('ai_comment_moderator_active_provider', 'ollama');
        return self::get_provider($active_provider);
    }
    
    /**
     * Get all available providers
     * 
     * @return array Array of provider info arrays
     */
    public static function get_available_providers() {
        $available = array();
        
        foreach (self::$providers as $name => $info) {
            $provider = self::get_provider($name);
            if ($provider) {
                $available[$name] = array(
                    'name' => $name,
                    'display_name' => $provider->get_provider_display_name(),
                    'supports_streaming' => $provider->supports_streaming()
                );
            }
        }
        
        return $available;
    }
    
    /**
     * Check if a provider is available
     * 
     * @param string $name Provider name
     * 
     * @return bool True if provider is available
     */
    public static function is_provider_available($name) {
        return isset(self::$providers[$name]);
    }
    
    /**
     * Initialize and register all providers
     */
    public static function init() {
        $providers_dir = dirname(__FILE__) . '/providers/';
        
        // Register Ollama Provider
        self::register_provider(
            'ollama',
            'AI_Ollama_Provider',
            $providers_dir . 'ollama-provider.php'
        );
        
        // Register OpenAI Provider
        self::register_provider(
            'openai',
            'AI_OpenAI_Provider',
            $providers_dir . 'openai-provider.php'
        );
        
        // Register Claude Provider
        self::register_provider(
            'claude',
            'AI_Claude_Provider',
            $providers_dir . 'claude-provider.php'
        );
        
        // Register OpenRouter Provider
        self::register_provider(
            'openrouter',
            'AI_OpenRouter_Provider',
            $providers_dir . 'openrouter-provider.php'
        );
        
        // Allow plugins to register custom providers
        do_action('ai_moderator_register_providers', self::class);
    }
    
    /**
     * Clear cached instances
     */
    public static function clear_cache() {
        self::$instances = array();
    }
}

// Initialize providers on plugin load
add_action('plugins_loaded', array('AI_Provider_Factory', 'init'));

