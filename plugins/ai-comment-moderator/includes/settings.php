<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Add admin menu
add_action('admin_menu', 'ai_comment_moderator_add_admin_menu');
function ai_comment_moderator_add_admin_menu() {
    // Main menu item - Dashboard
    add_menu_page(
        'AI Comment Moderator',
        'AI Moderator',
        'manage_options',
        'ai-comment-moderator',
        'ai_comment_moderator_dashboard_page',
        'dashicons-shield-alt',
        30
    );
    
    // Submenu - Dashboard (rename first submenu to match)
    add_submenu_page(
        'ai-comment-moderator',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'ai-comment-moderator',
        'ai_comment_moderator_dashboard_page'
    );
    
    // Submenu - Batch Processing
    add_submenu_page(
        'ai-comment-moderator',
        'Batch Processing',
        'Batch Process',
        'manage_options',
        'ai-comment-moderator-batch',
        'ai_comment_moderator_batch_page'
    );
    
    // Submenu - Prompts
    add_submenu_page(
        'ai-comment-moderator',
        'Manage Prompts',
        'Prompts',
        'manage_options',
        'ai-comment-moderator-prompts',
        'ai_comment_moderator_prompts_page'
    );
    
    // Submenu - Settings
    add_submenu_page(
        'ai-comment-moderator',
        'AI Moderator Settings',
        'Settings',
        'manage_options',
        'ai-comment-moderator-settings',
        'ai_comment_moderator_settings_page'
    );
}

// Dashboard page
function ai_comment_moderator_dashboard_page() {
    global $wpdb;
    
    // Get statistics
    $total_comments = wp_count_comments();
    $reviewed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_comment_reviews WHERE ai_reviewed = 1");
    
    // Get unreviewed counts by status
    $processor = new AI_Comment_Moderator_Comment_Processor();
    $pending_approved = $processor->get_unreviewed_count('approved');
    $pending_moderation = $processor->get_unreviewed_count('pending');
    $pending_total = $processor->get_unreviewed_count('all');
    
    // Get recent activity
    $recent_logs = $wpdb->get_results("
        SELECT l.*, c.comment_author, c.comment_content, p.name as prompt_name 
        FROM {$wpdb->prefix}ai_comment_logs l
        LEFT JOIN {$wpdb->comments} c ON l.comment_id = c.comment_ID
        LEFT JOIN {$wpdb->prefix}ai_comment_prompts p ON l.prompt_id = p.id
        ORDER BY l.created_at DESC 
        LIMIT 10
    ");
    
    ?>
    <div class="wrap">
        <h1>AI Comment Moderator Dashboard</h1>
        
        <div class="ai-moderator-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Comments</h3>
                    <span class="stat-number"><?php echo number_format($total_comments->total_comments); ?></span>
                </div>
                <div class="stat-card">
                    <h3>AI Reviewed</h3>
                    <span class="stat-number"><?php echo number_format($reviewed_count); ?></span>
                </div>
                <div class="stat-card">
                    <h3>Pending AI Review</h3>
                    <span class="stat-number"><?php echo number_format($pending_total); ?></span>
                    <p style="font-size: 12px; margin-top: 5px; color: #666;">
                        <?php echo number_format($pending_approved); ?> approved, 
                        <?php echo number_format($pending_moderation); ?> pending
                    </p>
                </div>
                <div class="stat-card">
                    <h3>Review Progress</h3>
                    <span class="stat-number"><?php echo $total_comments->total_comments > 0 ? round(($reviewed_count / $total_comments->total_comments) * 100, 1) : 0; ?>%</span>
                </div>
            </div>
        </div>
        
        <div class="ai-moderator-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-batch'); ?>" class="button button-primary">
                    Process Pending Comments
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-settings'); ?>" class="button">
                    Configure Settings
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-prompts'); ?>" class="button">
                    Manage Prompts
                </a>
            </div>
        </div>
        
        <?php if (!empty($recent_logs)): ?>
        <div class="ai-moderator-recent">
            <h2>Recent Activity</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Author</th>
                        <th>Comment</th>
                        <th>Prompt</th>
                        <th>Action</th>
                        <th>Processing Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($log->created_at)); ?></td>
                        <td><?php echo esc_html($log->comment_author); ?></td>
                        <td><?php echo esc_html(wp_trim_words($log->comment_content, 10)); ?></td>
                        <td><?php echo esc_html($log->prompt_name); ?></td>
                        <td>
                            <span class="action-badge action-<?php echo esc_attr($log->action_taken); ?>">
                                <?php echo esc_html(ucfirst($log->action_taken)); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($log->processing_time, 2); ?>s</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Settings page
function ai_comment_moderator_settings_page() {
    // Save settings if form submitted
    if (isset($_POST['ai_moderator_settings_submit'])) {
        check_admin_referer('ai_moderator_settings_nonce');
        
        // Save active provider
        $active_provider = sanitize_text_field($_POST['active_provider']);
        update_option('ai_comment_moderator_active_provider', $active_provider);
        
        // Save provider-specific settings based on which provider is active
        if ($active_provider === 'ollama') {
            update_option('ai_comment_moderator_ollama_url', sanitize_url($_POST['ollama_url']));
            update_option('ai_comment_moderator_ollama_model', sanitize_text_field($_POST['ollama_model']));
        } elseif ($active_provider === 'openai') {
            if (!empty($_POST['openai_api_key'])) {
                // Simple encryption using WordPress AUTH_KEY
                $api_key = sanitize_text_field($_POST['openai_api_key']);
                if (defined('AUTH_KEY') && AUTH_KEY) {
                    $encrypted_key = base64_encode($api_key . '::' . md5(AUTH_KEY));
                } else {
                    $encrypted_key = base64_encode($api_key);
                }
                update_option('ai_comment_moderator_openai_api_key', $encrypted_key);
            }
            update_option('ai_comment_moderator_openai_model', sanitize_text_field($_POST['openai_model']));
            update_option('ai_comment_moderator_openai_budget_alert', floatval($_POST['openai_budget_alert']));
        } elseif ($active_provider === 'claude') {
            if (!empty($_POST['claude_api_key'])) {
                $api_key = sanitize_text_field($_POST['claude_api_key']);
                if (defined('AUTH_KEY') && AUTH_KEY) {
                    $encrypted_key = base64_encode($api_key . '::' . md5(AUTH_KEY));
                } else {
                    $encrypted_key = base64_encode($api_key);
                }
                update_option('ai_comment_moderator_claude_api_key', $encrypted_key);
            }
            update_option('ai_comment_moderator_claude_model', sanitize_text_field($_POST['claude_model']));
            update_option('ai_comment_moderator_claude_budget_alert', floatval($_POST['claude_budget_alert']));
        } elseif ($active_provider === 'openrouter') {
            if (!empty($_POST['openrouter_api_key'])) {
                $api_key = sanitize_text_field($_POST['openrouter_api_key']);
                if (defined('AUTH_KEY') && AUTH_KEY) {
                    $encrypted_key = base64_encode($api_key . '::' . md5(AUTH_KEY));
                } else {
                    $encrypted_key = base64_encode($api_key);
                }
                update_option('ai_comment_moderator_openrouter_api_key', $encrypted_key);
            }
            update_option('ai_comment_moderator_openrouter_model', sanitize_text_field($_POST['openrouter_model']));
            update_option('ai_comment_moderator_openrouter_fallbacks', sanitize_text_field($_POST['openrouter_fallbacks']));
            update_option('ai_comment_moderator_openrouter_budget_alert', floatval($_POST['openrouter_budget_alert']));
        }
        
        // Save general settings
        update_option('ai_comment_moderator_batch_size', intval($_POST['batch_size']));
        update_option('ai_comment_moderator_auto_process', isset($_POST['auto_process']) ? '1' : '0');
        update_option('ai_comment_moderator_rate_limit', intval($_POST['rate_limit']));
        update_option('ai_comment_moderator_sync_pages_per_batch', intval($_POST['sync_pages_per_batch']));
        update_option('ai_comment_moderator_keep_data_on_uninstall', isset($_POST['keep_data_on_uninstall']) ? '1' : '0');
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    // Test Ollama connection and fetch models
    $connection_status = '';
    $available_models = array();
    if (isset($_POST['test_connection'])) {
        check_admin_referer('ai_moderator_settings_nonce');
        $client = new AI_Comment_Moderator_Ollama_Client();
        $models = $client->get_available_models();
        if ($models !== false) {
            $available_models = $models;
            $connection_status = '<div class="notice notice-success"><p>✓ Connection successful! Found ' . count($models) . ' models.</p></div>';
        } else {
            $connection_status = '<div class="notice notice-error"><p>✗ Connection failed. Please check your Ollama URL and ensure Ollama is running.</p></div>';
        }
    }
    
    // Get all current settings
    $active_provider = get_option('ai_comment_moderator_active_provider', 'ollama');
    $ollama_url = get_option('ai_comment_moderator_ollama_url', 'http://localhost:11434');
    $ollama_model = get_option('ai_comment_moderator_ollama_model', '');
    $openai_model = get_option('ai_comment_moderator_openai_model', 'gpt-3.5-turbo');
    $openai_budget = get_option('ai_comment_moderator_openai_budget_alert', '10');
    $claude_model = get_option('ai_comment_moderator_claude_model', 'claude-3-haiku-20240307');
    $claude_budget = get_option('ai_comment_moderator_claude_budget_alert', '10');
    $openrouter_model = get_option('ai_comment_moderator_openrouter_model', 'openai/gpt-3.5-turbo');
    $openrouter_fallbacks = get_option('ai_comment_moderator_openrouter_fallbacks', '');
    $openrouter_budget = get_option('ai_comment_moderator_openrouter_budget_alert', '10');
    $batch_size = get_option('ai_comment_moderator_batch_size', '10');
    $auto_process = get_option('ai_comment_moderator_auto_process', '0');
    $rate_limit = get_option('ai_comment_moderator_rate_limit', '5');
    $keep_data = get_option('ai_comment_moderator_keep_data_on_uninstall', '1');
    
    // Get available providers
    $available_providers = AI_Provider_Factory::get_available_providers();
    
    ?>
    <div class="wrap">
        <h1>AI Comment Moderator Settings</h1>
        
        <?php echo $connection_status; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('ai_moderator_settings_nonce'); ?>
            
            <h2>🤖 AI Provider</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Active Provider</th>
                    <td>
                        <select name="active_provider" id="active_provider" class="regular-text">
                            <?php foreach ($available_providers as $key => $provider): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($active_provider, $key); ?>>
                                    <?php echo esc_html($provider['display_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Choose which AI provider to use for comment moderation</p>
                    </td>
                </tr>
            </table>
            
            <!-- Ollama Settings -->
            <div id="provider-settings-ollama" class="provider-settings" style="display: <?php echo $active_provider === 'ollama' ? 'block' : 'none'; ?>;">
                <h2>Ollama Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Ollama URL</th>
                        <td>
                            <input type="url" name="ollama_url" id="ollama_url" value="<?php echo esc_attr($ollama_url); ?>" class="regular-text" />
                            <button type="button" id="test-ollama-connection" class="button">Test Connection & Load Models</button>
                            <span id="ollama-connection-status"></span>
                            <p class="description">URL where your Ollama instance is running (e.g., http://localhost:11434)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Ollama Model</th>
                        <td>
                            <select name="ollama_model" id="ollama_model" class="regular-text">
                                <option value="">Select a model...</option>
                                <?php if ($ollama_model): ?>
                                    <option value="<?php echo esc_attr($ollama_model); ?>" selected><?php echo esc_html($ollama_model); ?></option>
                                <?php endif; ?>
                            </select>
                            <p class="description">Select the AI model to use. Cost: $0 (self-hosted)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- OpenAI Settings -->
            <div id="provider-settings-openai" class="provider-settings" style="display: <?php echo $active_provider === 'openai' ? 'block' : 'none'; ?>;">
                <h2>OpenAI Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="openai_api_key" id="openai_api_key" class="regular-text" placeholder="sk-..." />
                            <button type="button" id="test-openai-connection" class="button">Test Connection & Load Models</button>
                            <span id="openai-connection-status"></span>
                            <p class="description">Your OpenAI API key (starts with sk-). Get one at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></p>
                            <p class="description"><strong>Note:</strong> Leave blank to keep existing key. API keys are encrypted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="openai_model" id="openai_model" class="regular-text">
                                <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (~$0.001/comment)</option>
                                <option value="gpt-4" <?php selected($openai_model, 'gpt-4'); ?>>GPT-4 (~$0.05/comment)</option>
                                <option value="gpt-4-turbo" <?php selected($openai_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo (~$0.02/comment)</option>
                                <option value="gpt-4o" <?php selected($openai_model, 'gpt-4o'); ?>>GPT-4o (~$0.01/comment)</option>
                            </select>
                            <p class="description">Select GPT model. Prices are estimates.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Monthly Budget Alert (USD)</th>
                        <td>
                            <input type="number" name="openai_budget_alert" value="<?php echo esc_attr($openai_budget); ?>" min="1" step="0.01" class="small-text" />
                            <p class="description">Receive alert when monthly spending exceeds this amount</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Claude Settings -->
            <div id="provider-settings-claude" class="provider-settings" style="display: <?php echo $active_provider === 'claude' ? 'block' : 'none'; ?>;">
                <h2>Claude (Anthropic) Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="claude_api_key" id="claude_api_key" class="regular-text" placeholder="sk-ant-..." />
                            <button type="button" id="test-claude-connection" class="button">Test Connection</button>
                            <span id="claude-connection-status"></span>
                            <p class="description">Your Anthropic API key (starts with sk-ant-). Get one at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></p>
                            <p class="description"><strong>Note:</strong> Leave blank to keep existing key. API keys are encrypted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="claude_model" id="claude_model" class="regular-text">
                                <option value="claude-3-haiku-20240307" <?php selected($claude_model, 'claude-3-haiku-20240307'); ?>>Claude 3 Haiku (~$0.0005/comment - fastest)</option>
                                <option value="claude-3-sonnet-20240229" <?php selected($claude_model, 'claude-3-sonnet-20240229'); ?>>Claude 3 Sonnet (~$0.005/comment)</option>
                                <option value="claude-3-5-sonnet-20240620" <?php selected($claude_model, 'claude-3-5-sonnet-20240620'); ?>>Claude 3.5 Sonnet (~$0.005/comment)</option>
                                <option value="claude-3-opus-20240229" <?php selected($claude_model, 'claude-3-opus-20240229'); ?>>Claude 3 Opus (~$0.02/comment - most capable)</option>
                            </select>
                            <p class="description">Select Claude model. Haiku is fastest and cheapest.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Monthly Budget Alert (USD)</th>
                        <td>
                            <input type="number" name="claude_budget_alert" value="<?php echo esc_attr($claude_budget); ?>" min="1" step="0.01" class="small-text" />
                            <p class="description">Receive alert when monthly spending exceeds this amount</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- OpenRouter Settings -->
            <div id="provider-settings-openrouter" class="provider-settings" style="display: <?php echo $active_provider === 'openrouter' ? 'block' : 'none'; ?>;">
                <h2>OpenRouter Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="openrouter_api_key" id="openrouter_api_key" class="regular-text" />
                            <button type="button" id="test-openrouter-connection" class="button">Test Connection & Load Models</button>
                            <span id="openrouter-connection-status"></span>
                            <p class="description">Your OpenRouter API key. Get one at <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a></p>
                            <p class="description"><strong>Note:</strong> Leave blank to keep existing key. API keys are encrypted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Primary Model</th>
                        <td>
                            <select name="openrouter_model" id="openrouter_model" class="regular-text">
                                <option value="<?php echo esc_attr($openrouter_model); ?>" selected><?php echo esc_html($openrouter_model); ?></option>
                            </select>
                            <button type="button" id="refresh-openrouter-models" class="button">Refresh Models</button>
                            <p class="description">Select from 100+ models. Click Test Connection to load models.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fallback Models</th>
                        <td>
                            <input type="text" name="openrouter_fallbacks" value="<?php echo esc_attr($openrouter_fallbacks); ?>" class="regular-text" placeholder="anthropic/claude-3-haiku,meta-llama/llama-3-8b" />
                            <p class="description">Comma-separated list of model IDs to try if primary fails (automatic fallback)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Monthly Budget Alert (USD)</th>
                        <td>
                            <input type="number" name="openrouter_budget_alert" value="<?php echo esc_attr($openrouter_budget); ?>" min="1" step="0.01" class="small-text" />
                            <p class="description">Receive alert when monthly spending exceeds this amount</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Batch Size</th>
                    <td>
                        <input type="number" name="batch_size" value="<?php echo esc_attr($batch_size); ?>" min="1" max="100" class="small-text" />
                        <p class="description">Number of comments to process in each batch (1-100)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto-Process New Comments</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_process" value="1" <?php checked($auto_process, '1'); ?> />
                            Automatically process new comments as they are submitted
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Rate Limit</th>
                    <td>
                        <input type="number" name="rate_limit" value="<?php echo esc_attr($rate_limit); ?>" min="1" max="60" class="small-text" />
                        <p class="description">Maximum API requests per minute</p>
                    </td>
                </tr>
            </table>
            
            <h2>Remote Site Sync Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Comments Per Sync</th>
                    <td>
                        <?php
                        $sync_pages = get_option('ai_comment_moderator_sync_pages_per_batch', 10);
                        ?>
                        <select name="sync_pages_per_batch">
                            <option value="5" <?php selected($sync_pages, 5); ?>>500 comments (5 pages × 100)</option>
                            <option value="10" <?php selected($sync_pages, 10); ?>>1,000 comments (10 pages × 100)</option>
                            <option value="20" <?php selected($sync_pages, 20); ?>>2,000 comments (20 pages × 100)</option>
                            <option value="50" <?php selected($sync_pages, 50); ?>>5,000 comments (50 pages × 100)</option>
                            <option value="100" <?php selected($sync_pages, 100); ?>>10,000 comments (100 pages × 100)</option>
                        </select>
                        <p class="description">
                            Number of comments to fetch from remote sites per sync operation.<br>
                            <strong>Higher values = fewer clicks needed, but may cause timeouts on slow servers.</strong><br>
                            Default: 1,000 (good balance for most sites)
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2>Data Management</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Keep Data on Uninstall</th>
                    <td>
                        <label>
                            <input type="checkbox" name="keep_data_on_uninstall" value="1" <?php checked($keep_data, '1'); ?> />
                            Preserve all data when plugin is deleted
                        </label>
                        <p class="description">
                            <strong>Recommended: Keep this checked during development.</strong><br>
                            When enabled, your settings, prompts, remote sites, and processing history will be preserved if you delete the plugin.<br>
                            This allows you to reinstall without losing your configuration.<br>
                            <br>
                            <strong>What gets preserved:</strong>
                        </p>
                        <ul style="list-style: disc; margin-left: 25px; margin-top: 5px;">
                            <li>Ollama URL and model settings</li>
                            <li>All custom prompts</li>
                            <li>Remote site configurations and credentials</li>
                            <li>Comment review history and logs</li>
                            <li>User reputation scores</li>
                            <li>Batch processing jobs</li>
                            <li>Webhook configurations</li>
                        </ul>
                        <p class="description" style="margin-top: 10px;">
                            <strong style="color: #d63638;">⚠️ Important:</strong> Only uncheck this if you want to completely remove all plugin data from your database.
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'ai_moderator_settings_submit'); ?>
        </form>
        
        <!-- Danger Zone Section (v2.2.0+) -->
        <div class="ai-moderator-danger-zone" style="margin-top: 40px; padding: 20px; border: 2px solid #dc3232; border-radius: 4px; background: #fff;">
            <h2 style="color: #dc3232; margin-top: 0;">⚠️ Danger Zone</h2>
            
            <div class="reset-data-section" style="margin-bottom: 20px;">
                <h3>Reset Processing Data</h3>
                <p>Clear all AI moderation history, analytics, and processed comments while keeping your configuration settings (AI providers, remote sites, prompts, and plugin settings).</p>
                
                <div class="current-data-stats" style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 3px;">
                    <strong>Current Data:</strong><br>
                    <span id="reviews-count">Loading...</span> AI reviews<br>
                    <span id="corrections-count">Loading...</span> corrections tracked<br>
                    <span id="remote-comments-count">Loading...</span> remote comments cached
                </div>
                
                <p style="color: #d63638;"><strong>Warning:</strong> This action cannot be undone. Your settings and configuration will be preserved.</p>
                
                <button type="button" id="reset-data-btn" class="button button-secondary">
                    🗑️ Reset Processing Data
                </button>
                <span class="reset-status" style="margin-left: 10px;"></span>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Load data stats on page load (v2.2.0+)
        loadDataStats();
        
        // Provider switching
        $('#active_provider').on('change', function() {
            var provider = $(this).val();
            $('.provider-settings').hide();
            $('#provider-settings-' + provider).show();
        });
        
        // Ollama connection test
        $('#test-ollama-connection').on('click', function() {
            var $button = $(this);
            var $status = $('#ollama-connection-status');
            var $modelSelect = $('#ollama_model');
            var ollamaUrl = $('#ollama_url').val();
            var currentModel = '<?php echo esc_js($ollama_model); ?>';
            
            if (!ollamaUrl) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">⚠ Please enter a URL first</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #666; margin-left: 10px;">⏳ Connecting...</span>');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_test_provider',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                provider: 'ollama',
                ollama_url: ollamaUrl
            }, function(response) {
                if (response.success && response.data.models) {
                    var models = response.data.models;
                    $modelSelect.empty().append('<option value="">Select a model...</option>');
                    
                    $.each(models, function(index, model) {
                        var selected = (model === currentModel) ? ' selected' : '';
                        $modelSelect.append('<option value="' + model + '"' + selected + '>' + model + '</option>');
                    });
                    
                    $status.html('<span style="color: #46b450; margin-left: 10px;">✓ Found ' + models.length + ' models!</span>');
                    setTimeout(function() { $status.fadeOut(400, function() { $(this).html('').show(); }); }, 5000);
                } else {
                    $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ ' + (response.data || 'Connection failed') + '</span>');
                }
            }).fail(function() {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ Network error</span>');
            }).always(function() {
                $button.prop('disabled', false).text('Test Connection & Load Models');
            });
        });
        
        // OpenAI connection test
        $('#test-openai-connection').on('click', function() {
            var $button = $(this);
            var $status = $('#openai-connection-status');
            var apiKey = $('#openai_api_key').val();
            
            if (!apiKey) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">⚠ Please enter an API key first</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #666; margin-left: 10px;">⏳ Connecting...</span>');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_test_provider',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                provider: 'openai',
                api_key: apiKey
            }, function(response) {
                if (response.success) {
                    $status.html('<span style="color: #46b450; margin-left: 10px;">✓ Connection successful!</span>');
                    setTimeout(function() { $status.fadeOut(400, function() { $(this).html('').show(); }); }, 5000);
                } else {
                    $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ ' + (response.data || 'Connection failed') + '</span>');
                }
            }).fail(function() {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ Network error</span>');
            }).always(function() {
                $button.prop('disabled', false).text('Test Connection & Load Models');
            });
        });
        
        // Claude connection test
        $('#test-claude-connection').on('click', function() {
            var $button = $(this);
            var $status = $('#claude-connection-status');
            var apiKey = $('#claude_api_key').val();
            
            if (!apiKey) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">⚠ Please enter an API key first</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #666; margin-left: 10px;">⏳ Connecting...</span>');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_test_provider',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                provider: 'claude',
                api_key: apiKey
            }, function(response) {
                if (response.success) {
                    $status.html('<span style="color: #46b450; margin-left: 10px;">✓ Connection successful!</span>');
                    setTimeout(function() { $status.fadeOut(400, function() { $(this).html('').show(); }); }, 5000);
                } else {
                    $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ ' + (response.data || 'Connection failed') + '</span>');
                }
            }).fail(function() {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ Network error</span>');
            }).always(function() {
                $button.prop('disabled', false).text('Test Connection');
            });
        });
        
        // OpenRouter connection test
        $('#test-openrouter-connection').on('click', function() {
            var $button = $(this);
            var $status = $('#openrouter-connection-status');
            var $modelSelect = $('#openrouter_model');
            var apiKey = $('#openrouter_api_key').val();
            var currentModel = '<?php echo esc_js($openrouter_model); ?>';
            
            if (!apiKey) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">⚠ Please enter an API key first</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #666; margin-left: 10px;">⏳ Connecting & loading models...</span>');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_test_provider',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                provider: 'openrouter',
                api_key: apiKey
            }, function(response) {
                if (response.success && response.data.models) {
                    var models = response.data.models;
                    $modelSelect.empty();
                    
                    $.each(models, function(index, model) {
                        var selected = (model.id === currentModel) ? ' selected' : '';
                        var displayName = model.name || model.id;
                        $modelSelect.append('<option value="' + model.id + '"' + selected + '>' + displayName + '</option>');
                    });
                    
                    $status.html('<span style="color: #46b450; margin-left: 10px;">✓ Found ' + models.length + ' models!</span>');
                    setTimeout(function() { $status.fadeOut(400, function() { $(this).html('').show(); }); }, 5000);
                } else {
                    $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ ' + (response.data || 'Connection failed') + '</span>');
                }
            }).fail(function() {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ Network error</span>');
            }).always(function() {
                $button.prop('disabled', false).text('Test Connection & Load Models');
            });
        });
        
        // Data stats loading function (v2.2.0+)
        function loadDataStats() {
            $.post(ajaxurl, {
                action: 'ai_moderator_get_data_stats',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#reviews-count').text(response.data.reviews.toLocaleString());
                    $('#corrections-count').text(response.data.corrections.toLocaleString());
                    $('#remote-comments-count').text(response.data.remote_comments.toLocaleString());
                }
            }).fail(function() {
                $('#reviews-count').text('Error loading');
                $('#corrections-count').text('Error loading');
                $('#remote-comments-count').text('Error loading');
            });
        }
        
        // Reset data handler (v2.2.0+)
        $('#reset-data-btn').on('click', function() {
            if (!confirm('Are you sure you want to delete all AI moderation history and analytics?\n\nThis will clear:\n• All processed comments\n• All analytics data\n• All correction tracking\n• All remote comments cache\n\nYour settings, prompts, and remote sites will be preserved.\n\nThis cannot be undone!')) {
                return;
            }
            
            var $btn = $(this);
            var $status = $('.reset-status');
            
            $btn.prop('disabled', true).text('Resetting...');
            $status.html('<span style="color: #666;">⏳ Clearing data...</span>');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_reset_data',
                nonce: '<?php echo wp_create_nonce('ai_moderator_reset_data'); ?>'
            }, function(response) {
                if (response.success) {
                    $status.html('<span style="color: #46b450;">✓ Data cleared successfully!</span>');
                    loadDataStats(); // Refresh counts
                    setTimeout(function() {
                        $status.html('');
                        $btn.prop('disabled', false).text('🗑️ Reset Processing Data');
                    }, 3000);
                } else {
                    $status.html('<span style="color: #dc3232;">✗ Error: ' + (response.data || 'Unknown error') + '</span>');
                    $btn.prop('disabled', false).text('🗑️ Reset Processing Data');
                }
            }).fail(function() {
                $status.html('<span style="color: #dc3232;">✗ Network error</span>');
                $btn.prop('disabled', false).text('🗑️ Reset Processing Data');
            });
        });
    });
    </script>
    <?php
}

// Batch processing page
function ai_comment_moderator_batch_page() {
    global $wpdb;
    
    // Get unreviewed comments counts for different statuses
    $processor = new AI_Comment_Moderator_Comment_Processor();
    $approved_count = $processor->get_unreviewed_count('approved');
    $pending_count = $processor->get_unreviewed_count('pending');
    $all_count = $processor->get_unreviewed_count('all');
    
    // Get remote sites and their pending counts
    $remote_sites = AI_Comment_Moderator_Remote_Site_Manager::get_sites(true);
    $remote_total = 0;
    if ($remote_sites) {
        foreach ($remote_sites as $site) {
            $remote_total += $site->pending_moderation;
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Batch Comment Processing</h1>
        
        <div class="batch-info">
            <p>
                <strong>Local Unreviewed Comments:</strong><br>
                Approved: <?php echo number_format($approved_count); ?> | 
                Pending Approval: <?php echo number_format($pending_count); ?> | 
                All: <?php echo number_format($all_count); ?>
            </p>
            <?php if ($remote_total > 0): ?>
            <p>
                <strong>Remote Sites:</strong> <?php echo number_format($remote_total); ?> pending moderation across <?php echo count($remote_sites); ?> site(s)
            </p>
            <?php endif; ?>
        </div>
        
        <div class="batch-controls">
            <h2>Processing Options</h2>
            <form id="batch-process-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">Comment Source</th>
                        <td>
                            <select name="comment_source" id="comment_source" class="regular-text">
                                <option value="local" selected>Local Site Comments</option>
                                <?php if ($remote_sites && count($remote_sites) > 0): ?>
                                <option value="remote_all">All Remote Sites</option>
                                <?php foreach ($remote_sites as $site): ?>
                                    <option value="remote_<?php echo $site->id; ?>">
                                        <?php echo esc_html($site->site_name); ?> 
                                        (<?php echo number_format($site->pending_moderation); ?> pending)
                                    </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="description">Choose to process local or remote site comments</p>
                        </td>
                    </tr>
                    <tr id="local-status-filter">
                        <th scope="row">Comment Status Filter</th>
                        <td>
                            <select name="comment_status" id="comment_status" class="regular-text">
                                <option value="all" selected>All Comments (Approved + Pending)</option>
                                <option value="approved">Approved Comments Only</option>
                                <option value="pending">Pending Comments Only</option>
                            </select>
                            <p class="description">Select which comments to process (applies to local comments only)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Prompt to Use</th>
                        <td>
                            <select name="prompt_id" id="prompt_id" required>
                                <option value="">Select a prompt...</option>
                                <?php
                                $prompts = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ai_comment_prompts WHERE is_active = 1");
                                foreach ($prompts as $prompt) {
                                    echo '<option value="' . esc_attr($prompt->id) . '">' . esc_html($prompt->name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Number of Comments</th>
                        <td>
                            <input type="number" name="batch_count" id="batch_count" value="<?php echo get_option('ai_comment_moderator_batch_size', '10'); ?>" min="1" max="1000" class="small-text" />
                            <p class="description">Process up to this many comments in this batch</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Re-processing Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="include_reviewed" id="include_reviewed" value="1" />
                                Re-process already reviewed comments
                            </label>
                            <p class="description">Check this to process comments that have already been reviewed by AI. Useful for testing different prompts or re-evaluating comments.</p>
                        </td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary" id="start-batch">Start Processing</button>
            </form>
        </div>
        
        <div id="batch-progress" style="display: none;">
            <h2>Processing Progress</h2>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
            </div>
            <p id="progress-text">Preparing...</p>
            <div id="progress-log"></div>
        </div>
        
        <div id="batch-results" style="display: none;">
            <h2>Processing Results</h2>
            <div id="results-summary"></div>
            <div id="results-details"></div>
        </div>
    </div>
    <?php
}
