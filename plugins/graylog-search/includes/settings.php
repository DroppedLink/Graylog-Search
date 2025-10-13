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
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
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

