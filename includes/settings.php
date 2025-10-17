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
        update_option('graylog_search_delete_on_uninstall', isset($_POST['delete_on_uninstall']) ? '1' : '0');
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    $disable_ssl = get_option('graylog_search_disable_ssl_verify', '0');
    $github_token = get_option('graylog_search_github_token', '');
    $delete_on_uninstall = get_option('graylog_search_delete_on_uninstall', '0');
    
    // Get update status
    $update_status = Graylog_Search_GitHub_Updater::get_update_status();
    
    // Clear cache if showing false update notification (version is same or older)
    if ($update_status['update_available'] && isset($update_status['new_version'])) {
        if (version_compare(GRAYLOG_SEARCH_VERSION, $update_status['new_version'], '>=')) {
            // Current version is same or newer than "new version" - cache is stale
            delete_transient('graylog_search_github_release');
            delete_site_transient('update_plugins');
            // Re-fetch update status after clearing cache
            $update_status = Graylog_Search_GitHub_Updater::get_update_status();
        }
    }
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
                <tr>
                    <th scope="row">Connection Test</th>
                    <td>
                        <button type="button" id="test-graylog-connection" class="button button-secondary">
                            <span class="dashicons dashicons-admin-plugins" style="vertical-align: middle;"></span>
                            Test Connection
                        </button>
                        <span id="connection-test-spinner" class="spinner" style="float: none; margin: 0 10px; display: none;"></span>
                        
                        <div id="connection-test-result" style="margin-top: 15px; display: none;"></div>
                        
                        <p class="description">
                            Click to test your Graylog API connection. This will verify:
                            <br>• API URL is reachable
                            <br>• API Token is valid
                            <br>• Search endpoint is working
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
                <tr>
                    <th scope="row">
                        <label for="delete_on_uninstall">Uninstall Options</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="delete_on_uninstall" 
                                   name="delete_on_uninstall" 
                                   value="1" 
                                   <?php checked($delete_on_uninstall, '1'); ?>>
                            Delete all plugin data when uninstalling
                        </label>
                        <p class="description" style="color: #d63638;">
                            <strong>⚠️ Warning:</strong> When enabled, uninstalling the plugin will permanently delete:
                            <br>• Search history database table (<?php global $wpdb; echo $wpdb->prefix; ?>graylog_search_history)
                            <br>• All plugin settings and API credentials
                            <br>• Saved searches and user preferences
                            <br>• All cached data and transients
                            <br><br><strong>This action cannot be undone!</strong> Keep this disabled if you plan to reinstall the plugin later.
                        </p>
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
        
        <h2>Shortcode Usage</h2>
        <div class="shortcode-info" style="background: #f0f0f1; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <p><strong>Add the search interface to any page or post:</strong></p>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2271b1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="font-size: 16px; font-weight: bold;">[graylog_search]</code>
                    <button type="button" class="button button-secondary" onclick="copyShortcode('[graylog_search]')">
                        <span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span> Copy
                    </button>
                </div>
            </div>
            
            <h3 style="margin-top: 20px;">Optional Attributes:</h3>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <strong>Custom Height:</strong>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                    <code>[graylog_search height="800px"]</code>
                    <button type="button" class="button button-secondary" onclick="copyShortcode('[graylog_search height=&quot;800px&quot;]')">
                        <span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span> Copy
                    </button>
                </div>
                <p class="description" style="margin-top: 10px;">Set custom height for the results container</p>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <strong>Restrict Access:</strong>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                    <code>[graylog_search capability="edit_posts"]</code>
                    <button type="button" class="button button-secondary" onclick="copyShortcode('[graylog_search capability=&quot;edit_posts&quot;]')">
                        <span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span> Copy
                    </button>
                </div>
                <p class="description" style="margin-top: 10px;">Limit access by WordPress capability</p>
            </div>
            
            <p style="margin-top: 15px;">
                <strong>💡 Tip:</strong> Copy any shortcode above and paste it into a page or post editor.
            </p>
        </div>
        
        <script>
        function copyShortcode(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                var msg = document.createElement('div');
                msg.className = 'notice notice-success is-dismissible';
                msg.style.position = 'fixed';
                msg.style.top = '32px';
                msg.style.right = '20px';
                msg.style.zIndex = '999999';
                msg.innerHTML = '<p><strong>Copied!</strong> Shortcode copied to clipboard.</p>';
                document.body.appendChild(msg);
                setTimeout(function() {
                    msg.remove();
                }, 3000);
            }, function(err) {
                alert('Failed to copy: ' + err);
            });
        }
        </script>
        
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
            // Test Graylog Connection
            $('#test-graylog-connection').on('click', function() {
                var $button = $(this);
                var $spinner = $('#connection-test-spinner');
                var $result = $('#connection-test-result');
                
                // Get current form values (not saved yet)
                var apiUrl = $('#graylog_api_url').val();
                var apiToken = $('#graylog_api_token').val();
                var disableSSL = $('#disable_ssl_verify').is(':checked');
                
                if (!apiUrl || !apiToken) {
                    $result.html('<div class="notice notice-error inline"><p><strong>Error:</strong> Please enter both API URL and Token before testing.</p></div>').show();
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $result.html('').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'graylog_test_connection',
                        nonce: '<?php echo wp_create_nonce('graylog-test-connection'); ?>',
                        api_url: apiUrl,
                        api_token: apiToken,
                        disable_ssl: disableSSL ? '1' : '0'
                    },
                    timeout: 30000,
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="notice notice-success inline" style="padding: 15px;"><p><strong>✅ Connection Successful!</strong></p>';
                            html += '<ul style="margin: 10px 0 0 20px;">';
                            
                            if (response.data.graylog_version) {
                                html += '<li><strong>Graylog Version:</strong> ' + response.data.graylog_version + '</li>';
                            }
                            if (response.data.hostname) {
                                html += '<li><strong>Server Hostname:</strong> ' + response.data.hostname + '</li>';
                            }
                            if (response.data.message_count !== undefined) {
                                html += '<li><strong>Test Search:</strong> Found ' + response.data.message_count + ' messages</li>';
                            }
                            if (response.data.response_time) {
                                html += '<li><strong>Response Time:</strong> ' + response.data.response_time + 'ms</li>';
                            }
                            
                            html += '</ul></div>';
                            $result.html(html).show();
                        } else {
                            var html = '<div class="notice notice-error inline" style="padding: 15px;"><p><strong>❌ Connection Failed</strong></p>';
                            html += '<p><strong>Error:</strong> ' + (response.data.message || 'Unknown error') + '</p>';
                            
                            if (response.data.details) {
                                html += '<p style="margin-top: 10px;"><strong>Details:</strong></p>';
                                html += '<pre style="background: #f0f0f1; padding: 10px; border-radius: 3px; font-size: 12px; overflow-x: auto;">' + response.data.details + '</pre>';
                            }
                            
                            if (response.data.suggestions) {
                                html += '<p style="margin-top: 10px;"><strong>Suggestions:</strong></p><ul style="margin-left: 20px;">';
                                response.data.suggestions.forEach(function(suggestion) {
                                    html += '<li>' + suggestion + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            html += '</div>';
                            $result.html(html).show();
                        }
                    },
                    error: function(jqXHR, textStatus) {
                        var html = '<div class="notice notice-error inline"><p><strong>❌ Request Failed</strong></p>';
                        html += '<p>Status: ' + textStatus + '</p>';
                        if (textStatus === 'timeout') {
                            html += '<p>The request timed out after 30 seconds. Check if your Graylog server is accessible.</p>';
                        }
                        html += '</div>';
                        $result.html(html).show();
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.css('visibility', 'hidden');
                    }
                });
            });
            
            // Check for Updates
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

// AJAX handler for testing Graylog connection
add_action('wp_ajax_graylog_test_connection', 'graylog_test_connection_handler');
function graylog_test_connection_handler() {
    check_ajax_referer('graylog-test-connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $start_time = microtime(true);
    
    // Get test parameters from AJAX request (not from database)
    $api_url = sanitize_text_field($_POST['api_url']);
    $api_token = sanitize_text_field($_POST['api_token']);
    $disable_ssl = isset($_POST['disable_ssl']) && $_POST['disable_ssl'] === '1';
    
    // Ensure /api is present
    if (strpos($api_url, '/api') === false) {
        $api_url .= '/api';
    }
    
    $suggestions = array();
    
    // Test 1: System endpoint
    $response = wp_remote_get($api_url . '/system', array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_token . ':token'),
            'Accept' => 'application/json',
            'X-Requested-By' => 'Graylog-Search-Plugin'
        ),
        'timeout' => 15,
        'sslverify' => !$disable_ssl
    ));
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        
        // Provide helpful suggestions based on error
        if (strpos($error_message, 'cURL error 60') !== false || strpos($error_message, 'SSL') !== false) {
            $suggestions[] = 'SSL certificate error detected. Try enabling "Disable SSL Verification" checkbox above.';
        }
        if (strpos($error_message, 'Could not resolve host') !== false) {
            $suggestions[] = 'DNS resolution failed. Check if the hostname is correct.';
        }
        if (strpos($error_message, 'Connection timed out') !== false || strpos($error_message, 'Operation timed out') !== false) {
            $suggestions[] = 'Connection timeout. Check if the server is running and accessible.';
        }
        if (strpos($error_message, 'Connection refused') !== false) {
            $suggestions[] = 'Connection refused. Check if Graylog is running on the specified port.';
        }
        
        wp_send_json_error(array(
            'message' => $error_message,
            'details' => 'Failed to connect to: ' . $api_url . '/system',
            'suggestions' => $suggestions
        ));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 401) {
        wp_send_json_error(array(
            'message' => 'Authentication failed (HTTP 401)',
            'details' => 'The API token is invalid or expired.',
            'suggestions' => array(
                'Generate a new API token in Graylog: System → Users → [Your User] → Edit Tokens',
                'Make sure you copied the entire token',
                'Check that the token has proper permissions'
            )
        ));
        return;
    }
    
    if ($status_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_error(array(
            'message' => 'Graylog returned HTTP ' . $status_code,
            'details' => substr($body, 0, 500),
            'suggestions' => array(
                'Check if the API URL is correct',
                'Verify Graylog is running properly'
            )
        ));
        return;
    }
    
    // Parse system info
    $body = wp_remote_retrieve_body($response);
    $system_data = json_decode($body, true);
    
    $graylog_version = isset($system_data['version']) ? $system_data['version'] : 'Unknown';
    $hostname = isset($system_data['hostname']) ? $system_data['hostname'] : 'Unknown';
    
    // Test 2: Try a simple search
    $search_url = add_query_arg(array(
        'query' => '*',
        'range' => 300,
        'limit' => 10
    ), $api_url . '/search/universal/relative');
    
    $search_response = wp_remote_get($search_url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_token . ':token'),
            'Accept' => 'application/json',
            'X-Requested-By' => 'Graylog-Search-Plugin'
        ),
        'timeout' => 15,
        'sslverify' => !$disable_ssl
    ));
    
    $message_count = 0;
    if (!is_wp_error($search_response) && wp_remote_retrieve_response_code($search_response) === 200) {
        $search_body = wp_remote_retrieve_body($search_response);
        $search_data = json_decode($search_body, true);
        if (isset($search_data['messages'])) {
            $message_count = count($search_data['messages']);
        }
    }
    
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    
    wp_send_json_success(array(
        'graylog_version' => $graylog_version,
        'hostname' => $hostname,
        'message_count' => $message_count,
        'response_time' => $response_time
    ));
}

