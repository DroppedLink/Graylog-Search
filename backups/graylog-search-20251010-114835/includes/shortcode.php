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
    
    // Enqueue scripts and styles for this shortcode
    wp_enqueue_style('graylog-search-style');
    wp_enqueue_script('graylog-search-script');
    
    // Generate unique ID for this instance
    $instance_id = 'graylog-search-' . uniqid();
    
    // Build the search interface
    ob_start();
    ?>
    <div class="graylog-search-shortcode" id="<?php echo esc_attr($instance_id); ?>">
        <div class="graylog-search-compact-form">
            <form class="graylog-search-form">
                <div class="graylog-form-row">
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_fqdn">FQDN/Hostname:</label>
                        <input type="text" 
                               id="<?php echo esc_attr($instance_id); ?>_fqdn" 
                               class="graylog-input search-fqdn"
                               placeholder="e.g., server01.example.com">
                    </div>
                    
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_terms">Search Terms:</label>
                        <input type="text" 
                               id="<?php echo esc_attr($instance_id); ?>_terms" 
                               class="graylog-input search-terms"
                               placeholder="e.g., error, warning">
                    </div>
                    
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_filter">Filter Out:</label>
                        <input type="text" 
                               id="<?php echo esc_attr($instance_id); ?>_filter" 
                               class="graylog-input filter-out"
                               placeholder="e.g., debug, info">
                    </div>
                </div>
                
                <div class="graylog-form-row">
                    <div class="graylog-form-col">
                        <label for="<?php echo esc_attr($instance_id); ?>_timerange">Time Range:</label>
                        <select id="<?php echo esc_attr($instance_id); ?>_timerange" class="graylog-select time-range">
                            <option value="3600">Last Hour</option>
                            <option value="86400" selected>Last Day</option>
                            <option value="604800">Last Week</option>
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
            </form>
        </div>
        
        <div class="graylog-loading" style="display: none;">
            <div class="graylog-spinner"></div>
            <p>Searching logs...</p>
        </div>
        
        <div class="graylog-error-message" style="display: none;"></div>
        
        <div class="graylog-results-container" style="display: none; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="graylog-results-header">
                <h3>Search Results <span class="result-count"></span></h3>
            </div>
            <div class="graylog-results-scroll">
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
    wp_register_style(
        'graylog-search-style',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/css/style.css',
        array(),
        GRAYLOG_SEARCH_VERSION
    );
    
    wp_register_script(
        'graylog-search-script',
        GRAYLOG_SEARCH_PLUGIN_URL . 'assets/js/search.js',
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

