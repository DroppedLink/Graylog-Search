<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Add admin menu
add_action('admin_menu', 'graylog_search_add_admin_menu');
function graylog_search_add_admin_menu() {
    // Main menu item - Search page
    add_menu_page(
        'Graylog Search',
        'Graylog Search',
        'manage_options',
        'graylog-search',
        'graylog_search_page',
        'dashicons-search',
        30
    );
    
    // Submenu - Settings
    add_submenu_page(
        'graylog-search',
        'Graylog Settings',
        'Settings',
        'manage_options',
        'graylog-search-settings',
        'graylog_search_settings_page'
    );
}

// Settings page content
function graylog_search_settings_page() {
    // Save settings if form submitted
    if (isset($_POST['graylog_settings_submit'])) {
        check_admin_referer('graylog_settings_nonce');
        
        update_option('graylog_api_url', sanitize_text_field($_POST['graylog_api_url']));
        update_option('graylog_api_token', sanitize_text_field($_POST['graylog_api_token']));
        update_option('graylog_search_disable_ssl_verify', isset($_POST['disable_ssl_verify']) ? '1' : '0');
        update_option('graylog_search_github_token', sanitize_text_field($_POST['github_token']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    $disable_ssl = get_option('graylog_search_disable_ssl_verify', '0');
    $github_token = get_option('graylog_search_github_token', '');
    
    // Get update status
    $update_status = Graylog_Search_GitHub_Updater::get_update_status();
    ?>
    <div class="wrap">
        <h1>Graylog Search Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('graylog_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="graylog_api_url">Graylog API URL</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="graylog_api_url" 
                               name="graylog_api_url" 
                               value="<?php echo esc_attr($api_url); ?>" 
                               class="regular-text"
                               placeholder="https://graylog.example.com:9000">
                        <p class="description">Enter your Graylog server URL (e.g., https://graylog.example.com:9000 or http://logs:9000)<br>
                        The /api path will be added automatically if not present.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="graylog_api_token">API Token</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="graylog_api_token" 
                               name="graylog_api_token" 
                               value="<?php echo esc_attr($api_token); ?>" 
                               class="regular-text"
                               placeholder="Your Graylog API token">
                        <p class="description">Enter your Graylog API token. You can generate one in Graylog under System → Users → API Tokens</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="disable_ssl_verify">SSL Verification</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="disable_ssl_verify" 
                                   name="disable_ssl_verify" 
                                   value="1" 
                                   <?php checked($disable_ssl, '1'); ?>>
                            Disable SSL Certificate Verification
                        </label>
                        <p class="description" style="color: #d63638;">
                            <strong>⚠️ Security Warning:</strong> Only enable this if you're using self-signed certificates in a trusted environment. 
                            This will disable SSL verification for Graylog API calls and GitHub update checks.<br>
                            <strong>Error this fixes:</strong> "cURL error 60: SSL certificate problem: self-signed certificate in certificate chain"
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2>Advanced Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="github_token">GitHub Token (Optional)</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="github_token" 
                               name="github_token" 
                               value="<?php echo esc_attr($github_token); ?>" 
                               class="regular-text"
                               placeholder="ghp_xxxxxxxxxxxx">
                        <p class="description">Optional: Increases GitHub API rate limits for update checks. Not required for public repositories.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="graylog_settings_submit" 
                       id="submit" 
                       class="button button-primary" 
                       value="Save Settings">
            </p>
        </form>
        
        <hr>
        
        <h2>Plugin Updates</h2>
        <div class="update-checker" style="background: #f0f0f1; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <p><strong>Current Version:</strong> <?php echo esc_html($update_status['current_version']); ?></p>
            
            <?php if ($update_status['update_available']): ?>
                <div style="background: #d63638; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <strong>🎉 Update Available!</strong><br>
                    <p style="margin: 10px 0;">New Version: <strong><?php echo esc_html($update_status['new_version']); ?></strong></p>
                    <p style="margin: 10px 0;">
                        <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">
                            Go to Plugins Page to Update
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div style="background: #00a32a; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <strong>✓ You're running the latest version!</strong>
                </div>
            <?php endif; ?>
            
            <p>
                <button type="button" id="check-for-updates" class="button">
                    <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                    Check for Updates Now
                </button>
                <span id="update-check-status" style="margin-left: 10px;"></span>
            </p>
            
            <p class="description">
                This plugin updates from GitHub: 
                <a href="https://github.com/DroppedLink/Graylog-Search" target="_blank">DroppedLink/Graylog-Search</a>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#check-for-updates').on('click', function() {
                var $button = $(this);
                var $status = $('#update-check-status');
                
                $button.prop('disabled', true);
                $status.html('<span style="color: #2271b1;">Checking...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'graylog_check_updates',
                        nonce: '<?php echo wp_create_nonce('graylog-search-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #00a32a;">✓ Check complete</span>');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            $status.html('<span style="color: #d63638;">✗ Check failed</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: #d63638;">✗ Error checking for updates</span>');
                    },
                    complete: function() {
                        setTimeout(function() {
                            $button.prop('disabled', false);
                            $status.html('');
                        }, 3000);
                    }
                });
            });
        });
        </script>
        
        <hr>
        
        <h2>Quick Start Guide</h2>
        <ol>
            <li>Enter your Graylog API URL above</li>
            <li>Generate an API token in Graylog (System → Users → Your User → Edit Tokens)</li>
            <li>Paste the token above and save</li>
            <li>Go to "Graylog Search" to start searching logs</li>
        </ol>
    </div>
    <?php
}

