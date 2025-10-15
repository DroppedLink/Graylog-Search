<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Register shortcode
add_shortcode('graylog_search', 'graylog_search_shortcode');

function graylog_search_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'height' => '600px',
        'capability' => 'read', // Who can use this: 'read', 'edit_posts', 'manage_options'
    ), $atts);
    
    // Check if user has permission
    if (!current_user_can($atts['capability'])) {
        return '<p>You do not have permission to access this feature.</p>';
    }
    
    // Check if settings are configured
    $api_url = get_option('graylog_api_url', '');
    $api_token = get_option('graylog_api_token', '');
    
    if (empty($api_url) || empty($api_token)) {
        return '<div class="graylog-error">Graylog API is not configured. Please contact your administrator.</div>';
    }
    
    // Enqueue ALL scripts and styles for this shortcode
    wp_enqueue_style('graylog-search-style');
    wp_enqueue_style('graylog-search-query-builder');
    wp_enqueue_style('graylog-search-history');
    
    wp_enqueue_script('graylog-search-script');
    wp_enqueue_script('graylog-search-keyboard');
    wp_enqueue_script('graylog-search-regex');
    wp_enqueue_script('graylog-search-query-builder');
    wp_enqueue_script('graylog-search-history');
    
    // Generate unique ID for this instance
    $instance_id = 'graylog-search-' . uniqid();
    
    // Build the search interface
    ob_start();
    ?>
    <div class="graylog-search-shortcode" id="<?php echo esc_attr($instance_id); ?>">
        <!-- Header with tools -->
        <div class="graylog-search-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0; font-size: 20px;">Graylog Search</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span id="connection-status" class="connection-status" title="Connection status">
                    <span class="status-dot status-unknown"></span>
                </span>
                <button type="button" class="button button-large graylog-dark-mode-toggle" title="Toggle dark mode">
                    <span class="dashicons dashicons-admin-appearance"></span>
                </button>
            </div>
        </div>
        
        <div class="graylog-search-compact-form">
            <form class="graylog-search-form">
                <div class="graylog-form-row">
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_fqdn">Hostname:</label>
                        <textarea id="<?php echo esc_attr($instance_id); ?>_fqdn" 
                                  class="graylog-input search-fqdn"
                                  rows="3"
                                  placeholder="e.g., server01 or server01.example.com&#10;Multiple: one per line or comma-separated"></textarea>
                    </div>
                    
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_terms">Search Terms:</label>
                        <textarea id="<?php echo esc_attr($instance_id); ?>_terms" 
                                  class="graylog-input search-terms"
                                  rows="3"
                                  placeholder="e.g., error, warning&#10;Multiple: one per line or comma-separated"></textarea>
                    </div>
                    
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_filter">Filter Out:</label>
                        <textarea id="<?php echo esc_attr($instance_id); ?>_filter" 
                                  class="graylog-input filter-out"
                                  rows="3"
                                  placeholder="e.g., debug, info&#10;Multiple: one per line or comma-separated"></textarea>
                    </div>
                </div>
                
                <div class="graylog-form-row">
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_timerange">Time Range:</label>
                        <select id="<?php echo esc_attr($instance_id); ?>_timerange" class="graylog-select time-range">
                            <option value="60">Last Hour</option>
                            <option value="240">Last 4 Hours</option>
                            <option value="480">Last 8 Hours</option>
                            <option value="720">Last 12 Hours</option>
                            <option value="1440" selected>Last Day</option>
                            <option value="4320">Last 3 Days</option>
                            <option value="10080">Last Week</option>
                        </select>
                    </div>
                    
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_limit">Result Limit:</label>
                        <select id="<?php echo esc_attr($instance_id); ?>_limit" class="graylog-select result-limit">
                            <option value="50">50 results</option>
                            <option value="100" selected>100 results</option>
                            <option value="250">250 results</option>
                            <option value="500">500 results</option>
                        </select>
                    </div>
                    
                    <div class="graylog-form-col graylog-form-buttons">
                        <button type="submit" class="graylog-btn graylog-btn-primary">Search Logs</button>
                        <button type="button" class="graylog-btn graylog-btn-secondary clear-search">Clear</button>
                    </div>
                </div>
                
                <!-- Advanced Search Tools Row -->
                <div class="graylog-form-row" style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <!-- Regex Mode -->
                    <div class="regex-mode-controls">
                        <label title="Enable regex search mode">
                            <input type="checkbox" id="regex-mode-toggle"> Regex Mode
                        </label>
                    </div>
                    
                    <!-- Regex Helper Buttons -->
                    <div class="regex-helper-buttons" style="display: none;">
                        <button type="button" class="button button-small" id="regex-pattern-library-btn">
                            <span class="dashicons dashicons-book"></span> Pattern Library
                        </button>
                        <button type="button" class="button button-small" id="regex-tester-btn">
                            <span class="dashicons dashicons-admin-tools"></span> Test Regex
                        </button>
                        <button type="button" class="button button-small" id="regex-syntax-btn">
                            <span class="dashicons dashicons-editor-help"></span> Syntax Help
                        </button>
                    </div>
                    
                    <!-- Visual Query Builder Button -->
                    <button type="button" class="button button-large" id="open-query-builder">
                        <span class="dashicons dashicons-editor-code"></span> Visual Query Builder
                    </button>
                    
                    <!-- Search History Button -->
                    <button type="button" class="button button-large" id="view-search-history">
                        <span class="dashicons dashicons-backup"></span> Search History
                    </button>
                    
                    <!-- Auto-Refresh -->
                    <div class="auto-refresh-controls">
                        <label>
                            <input type="checkbox" id="auto-refresh-toggle">
                            Auto-refresh
                        </label>
                        <select id="auto-refresh-interval">
                            <option value="15">15s</option>
                            <option value="30" selected>30s</option>
                            <option value="60">60s</option>
                            <option value="300">5min</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="graylog-loading" style="display: none;">
            <div class="graylog-spinner"></div>
            <p>Searching logs...</p>
        </div>
        
        <div class="graylog-error-message" style="display: none;"></div>
        
        <div class="graylog-results-container" style="display: none; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="graylog-results-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0;">Search Results <span class="result-count"></span></h3>
                    <div class="results-toolbar-actions">
                        <div class="parse-controls" style="font-size: 12px;">
                            <label title="Enable parsing of structured log formats">
                                <input type="checkbox" id="parse-toggle"> Parse
                            </label>
                            <div class="parse-format-options" style="display: none; font-size: 11px;">
                                <label><input type="checkbox" class="parse-format" value="json" checked> JSON</label>
                                <label><input type="checkbox" class="parse-format" value="kv" checked> kv</label>
                                <label><input type="checkbox" class="parse-format" value="cef" checked> CEF</label>
                                <label><input type="checkbox" class="parse-format" value="leef" checked> LEEF</label>
                            </div>
                        </div>
                        <div class="export-controls" style="font-size: 12px;">
                            <button class="export-btn" title="Export visible results" style="padding: 4px 8px;">
                                <span class="dashicons dashicons-download"></span> Export
                            </button>
                            <div class="export-menu" style="display: none;">
                                <button class="export-pdf">📄 PDF Report</button>
                                <button class="export-csv">CSV</button>
                                <button class="export-json">JSON</button>
                                <button class="export-txt">Text</button>
                                <button class="export-copy">📋 Copy</button>
                            </div>
                        </div>
                        <div class="timezone-controls">
                            <select id="timezone-selector" class="timezone-selector" title="Select timezone for timestamps" style="font-size: 12px; padding: 4px 8px;">
                                <option value="UTC">🌐 UTC/GMT</option>
                                <?php
                                $timezones = graylog_get_available_timezones();
                                foreach ($timezones as $group => $zones) {
                                    if ($group !== 'UTC/GMT') {
                                        echo '<optgroup label="' . esc_attr($group) . '">';
                                        foreach ($zones as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                                        }
                                        echo '</optgroup>';
                                    }
                                }
                                ?>
                            </select>
                            <button id="timezone-toggle-btn" class="timezone-toggle-btn" title="Toggle between original and converted times" style="font-size: 12px; padding: 4px 8px;">
                                Show Original
                            </button>
                        </div>
                        <button class="resolve-all-ips-btn" title="Resolve all IP addresses to hostnames" style="font-size: 12px; padding: 6px 12px;">
                            <span class="dashicons dashicons-admin-site"></span>
                            Resolve All IPs
                            <span class="ip-count">0</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="graylog-results-scroll">
                <div class="active-filters-container"></div>
                <div class="graylog-results-content"></div>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

// Enqueue scripts for shortcode on frontend
add_action('wp_enqueue_scripts', 'graylog_search_enqueue_frontend_assets');
function graylog_search_enqueue_frontend_assets() {
    // Register (but don't enqueue yet - only when shortcode is used)
    
    // Styles
    wp_register_style(
        'graylog-search-style',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/style.css',
        array(),
        GRAYLOG_SEARCH_VERSION
    );
    
    wp_register_style(
        'graylog-search-query-builder',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/query-builder.css',
        array('graylog-search-style'),
        GRAYLOG_SEARCH_VERSION
    );
    
    wp_register_style(
        'graylog-search-history',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/search-history.css',
        array('graylog-search-style'),
        GRAYLOG_SEARCH_VERSION
    );
    
    // Scripts
    wp_register_script(
        'graylog-search-script',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    wp_register_script(
        'graylog-search-keyboard',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/keyboard-shortcuts.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    wp_register_script(
        'graylog-search-regex',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/regex-helper.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    wp_register_script(
        'graylog-search-query-builder',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/query-builder.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    wp_register_script(
        'graylog-search-history',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search-history.js',
        array('jquery'),
        GRAYLOG_SEARCH_VERSION,
        true
    );
    
    // Pass AJAX URL to JavaScript
    wp_localize_script('graylog-search-script', 'graylogSearch', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('graylog_search_nonce')
    ));
}

// Add AJAX handler for non-admin users (if they have permission)
add_action('wp_ajax_nopriv_graylog_search_logs', 'graylog_search_logs_handler_public');
function graylog_search_logs_handler_public() {
    // Same as admin handler, but check for 'read' capability instead
    error_log('Graylog Search: Public AJAX handler called');
    
    // Verify nonce
    check_ajax_referer('graylog_search_nonce', 'nonce');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }
    
    // Call the main handler
    graylog_search_logs_handler();
}

