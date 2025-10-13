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
        
        update_option('ai_comment_moderator_ollama_url', sanitize_url($_POST['ollama_url']));
        update_option('ai_comment_moderator_ollama_model', sanitize_text_field($_POST['ollama_model']));
        update_option('ai_comment_moderator_batch_size', intval($_POST['batch_size']));
        update_option('ai_comment_moderator_auto_process', isset($_POST['auto_process']) ? '1' : '0');
        update_option('ai_comment_moderator_rate_limit', intval($_POST['rate_limit']));
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
    
    $ollama_url = get_option('ai_comment_moderator_ollama_url', 'http://localhost:11434');
    $ollama_model = get_option('ai_comment_moderator_ollama_model', '');
    $batch_size = get_option('ai_comment_moderator_batch_size', '10');
    $auto_process = get_option('ai_comment_moderator_auto_process', '0');
    $rate_limit = get_option('ai_comment_moderator_rate_limit', '5');
    $keep_data = get_option('ai_comment_moderator_keep_data_on_uninstall', '1');
    
    ?>
    <div class="wrap">
        <h1>AI Comment Moderator Settings</h1>
        
        <?php echo $connection_status; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('ai_moderator_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Ollama URL</th>
                    <td>
                        <input type="url" name="ollama_url" id="ollama_url" value="<?php echo esc_attr($ollama_url); ?>" class="regular-text" required />
                        <button type="button" id="test-ollama-connection" class="button">Test Connection & Load Models</button>
                        <span id="connection-status"></span>
                        <p class="description">The URL where your Ollama instance is running (e.g., http://localhost:11434)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ollama Model</th>
                    <td>
                        <select name="ollama_model" id="ollama_model" class="regular-text">
                            <option value="">Select a model...</option>
                            <?php 
                            // Show currently saved model if available
                            if ($ollama_model) {
                                echo '<option value="' . esc_attr($ollama_model) . '" selected>' . esc_html($ollama_model) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Select the AI model to use for comment moderation</p>
                    </td>
                </tr>
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
                        <p class="description">Maximum API requests per minute to prevent overloading Ollama</p>
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
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var currentModel = '<?php echo esc_js($ollama_model); ?>';
        
        // Test connection button handler
        $('#test-ollama-connection').on('click', function() {
            var $button = $(this);
            var $status = $('#connection-status');
            var $modelSelect = $('#ollama_model');
            var ollamaUrl = $('#ollama_url').val();
            
            if (!ollamaUrl) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">⚠ Please enter an Ollama URL first</span>');
                return;
            }
            
            // Update button state
            $button.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #666; margin-left: 10px;">⏳ Connecting...</span>');
            
            // Make AJAX request
            $.post(ajaxurl, {
                action: 'ai_moderator_get_models',
                nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                ollama_url: ollamaUrl
            }, function(response) {
                if (response.success && response.data.models) {
                    var models = response.data.models;
                    
                    // Clear and populate dropdown
                    $modelSelect.empty().append('<option value="">Select a model...</option>');
                    
                    $.each(models, function(index, model) {
                        var selected = (model === currentModel) ? ' selected' : '';
                        $modelSelect.append('<option value="' + model + '"' + selected + '>' + model + '</option>');
                    });
                    
                    // Update status
                    $status.html('<span style="color: #46b450; margin-left: 10px;">✓ Found ' + models.length + ' models!</span>');
                    
                    // Fade out success message after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut(400, function() {
                            $(this).html('').show();
                        });
                    }, 5000);
                } else {
                    var errorMsg = response.data || 'Failed to load models';
                    $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ ' + errorMsg + '</span>');
                }
            }).fail(function(xhr, status, error) {
                $status.html('<span style="color: #dc3232; margin-left: 10px;">✗ Network error: ' + error + '</span>');
            }).always(function() {
                $button.prop('disabled', false).text('Test Connection & Load Models');
            });
        });
        
        // Auto-test on page load if URL exists and no models loaded
        if (currentModel && $('#ollama_model option').length <= 1) {
            // Give it a moment for the page to load
            setTimeout(function() {
                $('#test-ollama-connection').trigger('click');
            }, 500);
        }
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
