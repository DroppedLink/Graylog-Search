# WordPress Plugin Programmer's Guide

> **Reference Implementation:** Based on the Graylog Search Plugin v1.6.4
> 
> This guide establishes the standard structure, patterns, and best practices for all WordPress plugins in this project.

## Table of Contents

1. [Plugin Structure](#plugin-structure)
2. [Core Architecture](#core-architecture)
3. [File Organization](#file-organization)
4. [Security Best Practices](#security-best-practices)
5. [AJAX Implementation](#ajax-implementation)
6. [Frontend Development](#frontend-development)
7. [Settings & Configuration](#settings--configuration)
8. [Features Implementation](#features-implementation)
9. [Documentation Standards](#documentation-standards)
10. [Version Control & Updates](#version-control--updates)
11. [Database Management](#database-management)
12. [Error Handling & Logging](#error-handling--logging)
13. [Third-Party Integrations](#third-party-integrations)
14. [Advanced WordPress Features](#advanced-wordpress-features)
15. [Testing & Quality Assurance](#testing--quality-assurance)
16. [Performance Optimization](#performance-optimization)
17. [Multi-site Support](#multi-site-support)

---

## 1. Plugin Structure

### Required Root Files

Every plugin MUST include these files in the root directory:

```
plugin-name/
├── plugin-name.php          # Main plugin file
├── README.md                # User-facing documentation
├── CHANGELOG.md             # Version history
├── includes/                # PHP functionality files
├── assets/                  # Frontend resources
│   ├── css/
│   └── js/
```

### Main Plugin File Template

```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Description: Brief description of what the plugin does
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Define constants
define('PLUGIN_NAME_VERSION', '1.0.0');
define('PLUGIN_NAME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLUGIN_NAME_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PLUGIN_NAME_PLUGIN_DIR . 'includes/settings.php';
require_once PLUGIN_NAME_PLUGIN_DIR . 'includes/ajax-handler.php';
// ... other includes

// Activation hook
register_activation_hook(__FILE__, 'plugin_name_activate');
function plugin_name_activate() {
    // Set default options
    add_option('plugin_name_setting', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'plugin_name_deactivate');
function plugin_name_deactivate() {
    // Cleanup if needed
}

// Initialize plugin
add_action('plugins_loaded', 'plugin_name_init');
function plugin_name_init() {
    // Plugin initialization code
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'plugin_name_enqueue_assets');
function plugin_name_enqueue_assets($hook) {
    // Only load on plugin pages
    if ($hook !== 'toplevel_page_plugin-name') {
        return;
    }
    
    wp_enqueue_style(
        'plugin-name-style',
        PLUGIN_NAME_PLUGIN_URL . 'assets/css/style.css',
        array(),
        PLUGIN_NAME_VERSION
    );
    
    wp_enqueue_script(
        'plugin-name-script',
        PLUGIN_NAME_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        PLUGIN_NAME_VERSION,
        true
    );
    
    // Pass data to JavaScript
    wp_localize_script('plugin-name-script', 'pluginName', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('plugin_name_nonce')
    ));
}
```

---

## 2. Core Architecture

### Separation of Concerns

**RULE:** Each file should have a single, well-defined purpose. Keep files under 700 lines of code.

#### File Responsibilities:

1. **Main Plugin File** (`plugin-name.php`)
   - Plugin metadata
   - Constants definition
   - File includes
   - Activation/deactivation hooks
   - Asset enqueuing

2. **Settings File** (`includes/settings.php`)
   - Admin menu registration
   - Settings page rendering
   - Option handling
   - Settings validation

3. **AJAX Handler** (`includes/ajax-handler.php`)
   - AJAX action hooks
   - Request processing
   - Response formatting
   - API integrations

4. **Page Handlers** (`includes/*-page.php`)
   - UI rendering
   - Form handling
   - Display logic

5. **Feature Modules** (`includes/feature-name.php`)
   - Self-contained feature logic
   - Helper functions
   - Specialized handlers

---

## 3. File Organization

### Directory Structure

```
plugin-name/
├── plugin-name.php                    # Main plugin file
├── README.md                          # Installation & usage guide
├── CHANGELOG.md                       # Version history
├── includes/                          # PHP backend files
│   ├── settings.php                   # Settings page & admin menu
│   ├── ajax-handler.php               # AJAX handlers
│   ├── search-page.php                # Main functionality page
│   ├── shortcode.php                  # Shortcode implementation
│   ├── dns-lookup.php                 # Feature: DNS lookup
│   ├── timezone-handler.php           # Feature: Timezone handling
│   └── github-updater.php             # Feature: Auto-updates
├── assets/
│   ├── css/
│   │   └── style.css                  # All plugin styles
│   └── js/
│       └── search.js                  # All plugin JavaScript
├── backups/                           # Development backups (optional)
└── dist/                              # Distribution builds (optional)
```

### Naming Conventions

- **Files:** `lowercase-with-dashes.php`
- **Functions:** `plugin_prefix_function_name()`
- **Classes:** `Plugin_Prefix_Class_Name`
- **Constants:** `PLUGIN_PREFIX_CONSTANT_NAME`
- **CSS Classes:** `.plugin-prefix-class-name`
- **JavaScript Objects:** `pluginPrefixObjectName`

---

## 4. Security Best Practices

### Input Sanitization

**ALWAYS** sanitize user input:

```php
// Text fields
$value = sanitize_text_field($_POST['field_name']);

// Email
$email = sanitize_email($_POST['email']);

// URLs
$url = esc_url_raw($_POST['url']);

// Integers
$number = intval($_POST['number']);

// Arrays
$array = array_map('sanitize_text_field', $_POST['array_field']);
```

### Output Escaping

**ALWAYS** escape output:

```php
// HTML content
echo esc_html($text);

// Attributes
echo '<div class="' . esc_attr($class) . '">';

// URLs
echo '<a href="' . esc_url($url) . '">';

// JavaScript
echo '<script>var data = ' . wp_json_encode($data) . ';</script>';
```

### Nonce Verification

**ALWAYS** use nonces for forms and AJAX:

```php
// Generate nonce
wp_nonce_field('plugin_name_action');

// Verify nonce in handler
check_admin_referer('plugin_name_action');

// AJAX nonce
check_ajax_referer('plugin_name_nonce', 'nonce');
```

### Capability Checks

**ALWAYS** verify user permissions:

```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Insufficient permissions'));
    return;
}
```

### Prevent Direct Access

**ALWAYS** add to top of PHP files:

```php
if (!defined('WPINC')) {
    die;
}
```

---

## 5. AJAX Implementation

### Backend AJAX Handler Pattern

```php
// Register AJAX action
add_action('wp_ajax_plugin_action_name', 'plugin_action_handler');

function plugin_action_handler() {
    // 1. Verify nonce
    check_ajax_referer('plugin_name_nonce', 'nonce');
    
    // 2. Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // 3. Sanitize input
    $param = sanitize_text_field($_POST['param']);
    
    // 4. Process request
    $result = process_data($param);
    
    // 5. Handle errors
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }
    
    // 6. Return success
    wp_send_json_success($result);
}
```

### Frontend AJAX Pattern (jQuery)

```javascript
jQuery(document).ready(function($) {
    $('#submit-button').on('click', function(e) {
        e.preventDefault();
        
        // Show loading state
        $(this).prop('disabled', true);
        
        // Prepare data
        var data = {
            action: 'plugin_action_name',
            nonce: pluginName.nonce,
            param: $('#input-field').val()
        };
        
        // Make AJAX request
        $.ajax({
            url: pluginName.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Handle success
                    console.log(response.data);
                } else {
                    // Handle error
                    alert(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                // Reset loading state
                $('#submit-button').prop('disabled', false);
            }
        });
    });
});
```

---

## 6. Frontend Development

### CSS Organization

Structure your styles logically:

```css
/* 1. Container/Layout Styles */
.plugin-wrap { }
.plugin-container { }

/* 2. Form Styles */
.plugin-form { }
.plugin-input { }
.plugin-button { }

/* 3. Results/Display Styles */
.plugin-results { }
.plugin-table { }

/* 4. Component Styles */
.plugin-notification { }
.plugin-modal { }

/* 5. State Styles */
.plugin-loading { }
.plugin-error { }
.plugin-success { }

/* 6. Responsive Styles */
@media screen and (max-width: 782px) { }
```

### JavaScript Organization

Structure your JavaScript functionally:

```javascript
jQuery(document).ready(function($) {
    // === STATE VARIABLES ===
    var activeFilters = [];
    var cache = {};
    
    // === EVENT HANDLERS ===
    $('#form').on('submit', function(e) { });
    $(document).on('click', '.dynamic-element', function() { });
    
    // === CORE FUNCTIONS ===
    function performAction() { }
    function processData(data) { }
    
    // === HELPER FUNCTIONS ===
    function escapeHtml(text) { }
    function showNotification(message, type) { }
    
    // === INITIALIZATION ===
    loadInitialData();
});
```

### UI/UX Standards

1. **Loading States**: Always show loading indicators for async operations
2. **Error Handling**: Display user-friendly error messages
3. **Notifications**: Use toast/notification system for feedback
4. **Responsive Design**: Ensure mobile compatibility
5. **Accessibility**: Use proper ARIA labels and semantic HTML

---

## 7. Settings & Configuration

### Settings Page Pattern

```php
function plugin_name_settings_page() {
    // Handle form submission
    if (isset($_POST['plugin_settings_submit'])) {
        check_admin_referer('plugin_settings_nonce');
        
        update_option('plugin_setting_1', sanitize_text_field($_POST['setting_1']));
        update_option('plugin_setting_2', sanitize_text_field($_POST['setting_2']));
        
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    
    $setting_1 = get_option('plugin_setting_1', '');
    $setting_2 = get_option('plugin_setting_2', '');
    ?>
    <div class="wrap">
        <h1>Plugin Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('plugin_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="setting_1">Setting 1</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="setting_1" 
                               name="setting_1" 
                               value="<?php echo esc_attr($setting_1); ?>" 
                               class="regular-text">
                        <p class="description">Description of setting 1</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="plugin_settings_submit" 
                       class="button button-primary" 
                       value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}
```

### Menu Registration

```php
add_action('admin_menu', 'plugin_name_add_admin_menu');
function plugin_name_add_admin_menu() {
    // Main menu item
    add_menu_page(
        'Plugin Name',              // Page title
        'Plugin Name',              // Menu title
        'manage_options',           // Capability
        'plugin-name',              // Menu slug
        'plugin_name_page',         // Callback function
        'dashicons-admin-generic',  // Icon
        30                          // Position
    );
    
    // Submenu - Settings
    add_submenu_page(
        'plugin-name',              // Parent slug
        'Settings',                 // Page title
        'Settings',                 // Menu title
        'manage_options',           // Capability
        'plugin-name-settings',     // Menu slug
        'plugin_name_settings_page' // Callback
    );
}
```

---

## 8. Features Implementation

### Modular Features

Each major feature should be in its own file:

```php
// includes/dns-lookup.php

// AJAX handler for DNS lookup
add_action('wp_ajax_plugin_dns_lookup', 'plugin_dns_lookup_handler');
function plugin_dns_lookup_handler() {
    check_ajax_referer('plugin_nonce', 'nonce');
    
    $ip = sanitize_text_field($_POST['ip']);
    
    // Perform DNS lookup
    $hostname = gethostbyaddr($ip);
    
    if ($hostname === $ip) {
        wp_send_json_error(array('message' => 'DNS lookup failed'));
        return;
    }
    
    wp_send_json_success(array('hostname' => $hostname));
}
```

### Common Feature Patterns

#### 1. **Interactive Filtering**
- Client-side filtering for instant results
- Active filters display with removal options
- Filter persistence across actions

#### 2. **Data Export**
- Multiple format support (CSV, JSON, TXT)
- Copy to clipboard functionality
- Export only visible/filtered data

#### 3. **Auto-refresh**
- Configurable intervals
- Pause/resume capability
- Visual indicators

#### 4. **Timezone Handling**
- User preference storage
- Automatic conversion
- Toggle between original/converted

#### 5. **GitHub Auto-updates**
- Version checking
- One-click updates
- Changelog display

---

## 9. Documentation Standards

### Required Documentation Files

1. **README.md** - User-facing documentation
   - Features overview
   - Installation instructions
   - Usage guide
   - Requirements
   - Support information

2. **CHANGELOG.md** - Version history
   - All changes in reverse chronological order
   - Semantic versioning
   - Categories: Added, Changed, Fixed, Security

3. **Feature-specific Guides** (as needed)
   - `SHORTCODE_GUIDE.md`
   - `USAGE_GUIDE.md`
   - `TROUBLESHOOTING.md`
   - etc.

### CHANGELOG Format

```markdown
# Changelog

## [1.2.0] - 2025-10-13

### Added
- New feature X with capability Y
- Support for Z functionality

### Changed
- Improved performance of feature A
- Updated UI for better UX

### Fixed
- Bug in feature B that caused issue C
- Compatibility issue with D

### Security
- Fixed XSS vulnerability in E
```

### README Template

```markdown
# Plugin Name

Brief description of what the plugin does.

## Features

- Feature 1
- Feature 2
- Feature 3

## Installation

1. Step 1
2. Step 2
3. Step 3

## Usage

### Basic Usage
Instructions...

### Advanced Features
Instructions...

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Any other requirements

## File Structure

```
plugin-name/
├── plugin-name.php
├── includes/
└── assets/
```

## Support

Contact information or support details.
```

---

## 10. Version Control & Updates

### Version Numbering

Use Semantic Versioning (SEMVER):

- **MAJOR.MINOR.PATCH** (e.g., 1.2.3)
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### GitHub Updater Integration

```php
// includes/github-updater.php

class Plugin_Name_GitHub_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $github_repo;
    
    public function __construct($file) {
        $this->file = $file;
        $this->plugin = plugin_basename($file);
        $this->basename = dirname($this->plugin);
        $this->github_repo = 'username/repo-name';
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
    }
    
    public function check_update($transient) {
        // Implementation
    }
    
    public function plugin_info($false, $action, $response) {
        // Implementation
    }
}

new Plugin_Name_GitHub_Updater(__FILE__);
```

### Release Checklist

Before releasing a new version:

- [ ] Update version number in main plugin file
- [ ] Update CHANGELOG.md with all changes
- [ ] Update README.md if needed
- [ ] Test all functionality
- [ ] Check for security issues
- [ ] Verify compatibility with latest WordPress
- [ ] Create Git tag for version
- [ ] Create GitHub release with ZIP file
- [ ] Update any external documentation

---

## Best Practices Summary

### Code Quality
1. Keep files under 700 lines
2. One responsibility per file
3. Use meaningful names
4. Comment complex logic
5. Follow WordPress coding standards

### Security
1. Sanitize ALL input
2. Escape ALL output
3. Use nonces for forms/AJAX
4. Check user capabilities
5. Prevent direct file access

### Performance
1. Enqueue assets only where needed
2. Use transients for caching
3. Minimize database queries
4. Lazy-load heavy features
5. Optimize asset delivery

### User Experience
1. Provide loading indicators
2. Show clear error messages
3. Implement responsive design
4. Add helpful tooltips
5. Include inline documentation

### Maintenance
1. Version all releases
2. Document all changes
3. Maintain backward compatibility
4. Test updates thoroughly
5. Provide migration paths

---

## Quick Reference: Common Code Snippets

### Add Admin Notice

```php
function plugin_name_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>Your message here</p>
    </div>
    <?php
}
add_action('admin_notices', 'plugin_name_admin_notice');
```

### Register Shortcode

```php
add_shortcode('plugin_shortcode', 'plugin_shortcode_handler');
function plugin_shortcode_handler($atts) {
    $atts = shortcode_atts(array(
        'param1' => 'default',
        'param2' => 'default'
    ), $atts);
    
    ob_start();
    // Output HTML
    return ob_get_clean();
}
```

### Add Custom Post Type

```php
function plugin_register_post_type() {
    register_post_type('custom_type', array(
        'labels' => array(
            'name' => 'Custom Types',
            'singular_name' => 'Custom Type'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor')
    ));
}
add_action('init', 'plugin_register_post_type');
```

### Enqueue Assets Conditionally

```php
function plugin_conditional_assets() {
    if (is_page('special-page')) {
        wp_enqueue_style('plugin-special-style', 
            plugins_url('assets/css/special.css', __FILE__));
        wp_enqueue_script('plugin-special-script', 
            plugins_url('assets/js/special.js', __FILE__), 
            array('jquery'));
    }
}
add_action('wp_enqueue_scripts', 'plugin_conditional_assets');
```

---

## 11. Database Management

### Use WordPress Options First

**RULE:** Start with WordPress options. Only create custom tables when you have clear performance needs.

#### When to Use Options

Use `add_option()`, `get_option()`, `update_option()` for:
- Plugin settings (always)
- Small datasets (< 100 rows)
- Simple key-value storage
- User preferences

```php
// Store settings
update_option('plugin_name_api_key', $api_key);
update_option('plugin_name_cache', $data);

// Retrieve settings
$api_key = get_option('plugin_name_api_key', 'default_value');
```

#### When to Use Custom Tables

Create custom tables only when:
- Storing thousands of records
- Complex queries needed
- Relationships between data
- Performance becomes an issue

```php
// Activation: Create table
function plugin_name_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'plugin_table';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        data text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Store version for future migrations
    add_option('plugin_name_db_version', '1.0');
}
```

#### Database Versioning

Track database schema versions:

```php
function plugin_name_check_db_version() {
    $current_version = get_option('plugin_name_db_version', '0');
    
    if (version_compare($current_version, '1.1', '<')) {
        plugin_name_upgrade_db_to_1_1();
        update_option('plugin_name_db_version', '1.1');
    }
}

function plugin_name_upgrade_db_to_1_1() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'plugin_table';
    
    // Add new column
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN new_field VARCHAR(255)");
}
```

#### Query Best Practices

```php
// Use $wpdb->prepare for all queries with variables
global $wpdb;
$user_id = 123;

$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}plugin_table WHERE user_id = %d",
    $user_id
));

// Avoid queries in loops - fetch once, process many times
$all_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}plugin_table");
foreach ($all_data as $row) {
    process_row($row);
}
```

#### Cleanup on Uninstall

```php
// uninstall.php (not in main plugin file)
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete options
delete_option('plugin_name_api_key');
delete_option('plugin_name_settings');
delete_option('plugin_name_db_version');

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}plugin_table");
```

---

## 12. Error Handling & Logging

### Error Logging Strategy

**RULE:** Log errors during development. Minimize logging in production.

#### Development Logging

```php
// Enable WordPress debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Log messages
error_log('Plugin Name: Starting process');
error_log('Plugin Name: API returned: ' . print_r($data, true));
error_log('Plugin Name: Error occurred: ' . $error_message);
```

#### Production Logging

Only log critical errors in production:

```php
function plugin_name_log_error($message, $data = null) {
    // Only log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = 'Plugin Name Error: ' . $message;
        if ($data) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

// Usage
plugin_name_log_error('API request failed', array('url' => $url, 'code' => $status_code));
```

#### Using WP_Error

WordPress has a built-in error handling class:

```php
function plugin_name_process_data($input) {
    if (empty($input)) {
        return new WP_Error('empty_input', 'Input cannot be empty');
    }
    
    $result = external_api_call($input);
    
    if (!$result) {
        return new WP_Error('api_failed', 'External API call failed');
    }
    
    return $result;
}

// Check for errors
$result = plugin_name_process_data($data);
if (is_wp_error($result)) {
    error_log('Error: ' . $result->get_error_message());
    wp_send_json_error(array('message' => $result->get_error_message()));
    return;
}
```

#### Admin Notices for Users

```php
function plugin_name_show_error_notice() {
    $error = get_transient('plugin_name_error');
    if ($error) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Plugin Name Error:</strong> <?php echo esc_html($error); ?></p>
        </div>
        <?php
        delete_transient('plugin_name_error');
    }
}
add_action('admin_notices', 'plugin_name_show_error_notice');

// Set error from anywhere
function plugin_name_set_error($message) {
    set_transient('plugin_name_error', $message, 60);
}
```

#### Try-Catch for External Operations

```php
function plugin_name_risky_operation() {
    try {
        // Risky code here
        $result = json_decode($json_string, true, 512, JSON_THROW_ON_ERROR);
        return $result;
    } catch (Exception $e) {
        error_log('Plugin Name: JSON decode error: ' . $e->getMessage());
        return new WP_Error('json_error', 'Failed to parse JSON data');
    }
}
```

---

## 13. Third-Party Integrations

### API Communication Pattern

Follow this pattern for all external API calls:

```php
function plugin_name_api_request($endpoint, $method = 'GET', $body = null) {
    $api_url = get_option('plugin_name_api_url');
    $api_token = get_option('plugin_name_api_token');
    
    // Build URL
    $url = rtrim($api_url, '/') . '/' . ltrim($endpoint, '/');
    
    // Prepare request
    $args = array(
        'method' => $method,
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        )
    );
    
    // Add body for POST/PUT
    if ($body && in_array($method, array('POST', 'PUT', 'PATCH'))) {
        $args['body'] = json_encode($body);
    }
    
    // Optional: Disable SSL verification (only for development)
    if (get_option('plugin_name_disable_ssl', false)) {
        $args['sslverify'] = false;
    }
    
    // Make request
    $response = wp_remote_request($url, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        error_log('Plugin Name: API Error: ' . $response->get_error_message());
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Handle non-200 responses
    if ($status_code < 200 || $status_code >= 300) {
        error_log('Plugin Name: API returned status ' . $status_code . ': ' . substr($body, 0, 200));
        return new WP_Error('api_error', 'API returned status code ' . $status_code);
    }
    
    // Parse JSON response
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Plugin Name: JSON parse error: ' . json_last_error_msg());
        return new WP_Error('json_error', 'Failed to parse API response');
    }
    
    return $data;
}
```

### Rate Limiting

Implement simple rate limiting with transients:

```php
function plugin_name_check_rate_limit($action = 'default', $limit = 60) {
    $key = 'plugin_rate_limit_' . $action;
    $count = get_transient($key);
    
    if ($count === false) {
        // First request in window
        set_transient($key, 1, 60);
        return true;
    }
    
    if ($count >= $limit) {
        return false; // Rate limit exceeded
    }
    
    // Increment counter
    set_transient($key, $count + 1, 60);
    return true;
}

// Usage
if (!plugin_name_check_rate_limit('api_call', 100)) {
    wp_send_json_error(array('message' => 'Rate limit exceeded. Try again later.'));
    return;
}
```

### Retry Logic

Simple retry mechanism for failed requests:

```php
function plugin_name_api_request_with_retry($endpoint, $max_retries = 3) {
    $attempt = 0;
    
    while ($attempt < $max_retries) {
        $result = plugin_name_api_request($endpoint);
        
        if (!is_wp_error($result)) {
            return $result;
        }
        
        $attempt++;
        
        if ($attempt < $max_retries) {
            // Wait before retrying (exponential backoff)
            sleep(pow(2, $attempt)); // 2s, 4s, 8s
        }
    }
    
    return new WP_Error('max_retries', 'Failed after ' . $max_retries . ' attempts');
}
```

### Webhook Handling

```php
// Register custom endpoint for webhooks
add_action('rest_api_init', 'plugin_name_register_webhook');
function plugin_name_register_webhook() {
    register_rest_route('plugin-name/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'plugin_name_handle_webhook',
        'permission_callback' => 'plugin_name_verify_webhook'
    ));
}

function plugin_name_verify_webhook($request) {
    // Verify webhook signature
    $signature = $request->get_header('X-Signature');
    $body = $request->get_body();
    $secret = get_option('plugin_name_webhook_secret');
    
    $expected = hash_hmac('sha256', $body, $secret);
    
    return hash_equals($expected, $signature);
}

function plugin_name_handle_webhook($request) {
    $data = $request->get_json_params();
    
    // Process webhook data
    do_action('plugin_name_webhook_received', $data);
    
    return array('success' => true);
}
```

---

## 14. Advanced WordPress Features

### WordPress Cron (Scheduled Tasks)

```php
// Schedule task on activation
register_activation_hook(__FILE__, 'plugin_name_schedule_tasks');
function plugin_name_schedule_tasks() {
    if (!wp_next_scheduled('plugin_name_daily_task')) {
        wp_schedule_event(time(), 'daily', 'plugin_name_daily_task');
    }
}

// Remove scheduled task on deactivation
register_deactivation_hook(__FILE__, 'plugin_name_unschedule_tasks');
function plugin_name_unschedule_tasks() {
    $timestamp = wp_next_scheduled('plugin_name_daily_task');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'plugin_name_daily_task');
    }
}

// Define what the task does
add_action('plugin_name_daily_task', 'plugin_name_do_daily_task');
function plugin_name_do_daily_task() {
    // Cleanup old data
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}plugin_table WHERE created_at < %s",
        date('Y-m-d H:i:s', strtotime('-30 days'))
    ));
}
```

### Custom REST API Endpoints

```php
add_action('rest_api_init', 'plugin_name_register_routes');
function plugin_name_register_routes() {
    register_rest_route('plugin-name/v1', '/data/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'plugin_name_get_data',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            )
        )
    ));
}

function plugin_name_get_data($request) {
    $id = $request->get_param('id');
    
    // Fetch data
    $data = get_data_by_id($id);
    
    if (!$data) {
        return new WP_Error('not_found', 'Data not found', array('status' => 404));
    }
    
    return rest_ensure_response($data);
}
```

### Transients for Caching

```php
function plugin_name_get_cached_data() {
    // Try to get cached data
    $cache_key = 'plugin_name_data';
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Fetch fresh data
    $data = plugin_name_fetch_expensive_data();
    
    // Cache for 1 hour
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    
    return $data;
}

// Clear cache when data changes
function plugin_name_clear_cache() {
    delete_transient('plugin_name_data');
}
```

### Custom Hooks for Extensibility

Allow other plugins/themes to extend your plugin:

```php
// In your plugin code, add action hooks
function plugin_name_process_item($item) {
    // Before processing
    do_action('plugin_name_before_process', $item);
    
    // Process item
    $result = process($item);
    
    // After processing
    do_action('plugin_name_after_process', $item, $result);
    
    return $result;
}

// Add filter hooks for modifying data
function plugin_name_get_settings() {
    $settings = get_option('plugin_name_settings', array());
    
    // Allow filtering
    return apply_filters('plugin_name_settings', $settings);
}
```

### File Upload Handling

```php
function plugin_name_handle_upload() {
    check_admin_referer('plugin_name_upload');
    
    if (!current_user_can('upload_files')) {
        wp_die('Insufficient permissions');
    }
    
    if (empty($_FILES['file'])) {
        wp_die('No file uploaded');
    }
    
    // Handle the upload
    $uploaded = wp_handle_upload($_FILES['file'], array(
        'test_form' => false,
        'mimes' => array(
            'csv' => 'text/csv',
            'json' => 'application/json'
        )
    ));
    
    if (isset($uploaded['error'])) {
        wp_die('Upload failed: ' . $uploaded['error']);
    }
    
    // Process the uploaded file
    $file_path = $uploaded['file'];
    plugin_name_process_file($file_path);
    
    // Clean up
    @unlink($file_path);
}
```

---

## 15. Testing & Quality Assurance

### Manual Testing Checklist

Before each release:

```
[ ] Fresh install works
[ ] Plugin activates without errors
[ ] Settings save correctly
[ ] All AJAX functions work
[ ] Forms validate properly
[ ] Error messages display
[ ] Success messages display
[ ] Deactivation doesn't break site
[ ] Reactivation preserves settings
[ ] No JavaScript errors in console
[ ] No PHP errors in debug log
[ ] Works with default WordPress theme
[ ] Mobile responsive
```

### Automated Testing (Simple Approach)

Create a test script in your plugin:

```php
// test-api.php (place in plugin root, remove before distribution)
<?php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Plugin Name - API Test</h1>\n";

// Test 1: Check settings
echo "<h2>Settings Check</h2>\n";
$api_url = get_option('plugin_name_api_url');
$api_key = get_option('plugin_name_api_key');
echo "API URL: " . ($api_url ? '✓ Set' : '✗ Not set') . "<br>\n";
echo "API Key: " . ($api_key ? '✓ Set' : '✗ Not set') . "<br>\n";

// Test 2: Test API connection
echo "<h2>API Connection Test</h2>\n";
$result = plugin_name_api_request('/status');
if (is_wp_error($result)) {
    echo "✗ Failed: " . $result->get_error_message() . "<br>\n";
} else {
    echo "✓ Success<br>\n";
    echo "<pre>" . print_r($result, true) . "</pre>\n";
}

// Test 3: Test database
echo "<h2>Database Test</h2>\n";
global $wpdb;
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}plugin_table'");
echo "Custom table: " . ($table_exists ? '✓ Exists' : '✗ Not found') . "<br>\n";

echo "<hr>\n";
echo "<p>Test completed at " . date('Y-m-d H:i:s') . "</p>\n";
```

### Debug Mode for Developers

```php
// Add debug mode toggle in settings
function plugin_name_is_debug_mode() {
    return get_option('plugin_name_debug_mode', false);
}

// Use throughout plugin
if (plugin_name_is_debug_mode()) {
    error_log('Plugin Name Debug: ' . $message);
}
```

### Browser Console Testing

Add JavaScript debugging:

```javascript
// Add to your main JS file
var PluginDebug = {
    enabled: false,
    
    log: function(message, data) {
        if (this.enabled) {
            console.log('[Plugin Name]', message, data || '');
        }
    },
    
    error: function(message, data) {
        if (this.enabled) {
            console.error('[Plugin Name]', message, data || '');
        }
    }
};

// Enable via browser console:
// PluginDebug.enabled = true;
```

---

## 16. Performance Optimization

### Only Load What's Needed

```php
// Enqueue assets only on plugin pages
function plugin_name_enqueue_assets($hook) {
    // Only on specific admin pages
    if (strpos($hook, 'plugin-name') === false) {
        return;
    }
    
    wp_enqueue_style('plugin-name-style', /*...*/);
    wp_enqueue_script('plugin-name-script', /*...*/);
}
add_action('admin_enqueue_scripts', 'plugin_name_enqueue_assets');
```

### Minimize Database Queries

```php
// BAD - Query in loop
foreach ($items as $item) {
    $meta = get_post_meta($item->ID, 'some_key', true); // Query every iteration!
}

// GOOD - Fetch all at once
$all_meta = get_post_meta($item->ID); // One query
foreach ($items as $item) {
    $meta = $all_meta['some_key'][0] ?? '';
}
```

### Use Transients for Expensive Operations

```php
function plugin_name_get_stats() {
    $cache_key = 'plugin_stats_' . get_current_user_id();
    $stats = get_transient($cache_key);
    
    if ($stats === false) {
        // Expensive calculation
        $stats = calculate_complex_stats();
        set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);
    }
    
    return $stats;
}
```

### Lazy Load Heavy Features

```php
// Don't load GitHub updater unless needed
if (is_admin()) {
    require_once PLUGIN_DIR . 'includes/github-updater.php';
}

// Don't initialize features until needed
function plugin_name_init_feature() {
    static $initialized = false;
    
    if (!$initialized) {
        require_once PLUGIN_DIR . 'includes/heavy-feature.php';
        $initialized = true;
    }
}
```

### Optimize Asset Delivery

```php
// Register assets in footer when possible
wp_enqueue_script(
    'plugin-name-script',
    PLUGIN_URL . 'assets/js/script.js',
    array('jquery'),
    VERSION,
    true  // Load in footer
);

// Only pass necessary data to JavaScript
wp_localize_script('plugin-name-script', 'pluginData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('plugin_nonce'),
    // Don't pass large datasets here - fetch via AJAX when needed
));
```

### Pagination for Large Datasets

```php
function plugin_name_get_results($page = 1, $per_page = 50) {
    global $wpdb;
    $offset = ($page - 1) * $per_page;
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}plugin_table 
         ORDER BY created_at DESC 
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    return $results;
}
```

---

## 17. Multi-site Support

### Check for Multi-site

```php
function plugin_name_init() {
    if (is_multisite()) {
        // Multi-site specific initialization
        add_action('network_admin_menu', 'plugin_name_network_menu');
    } else {
        // Single site initialization
        add_action('admin_menu', 'plugin_name_menu');
    }
}
```

### Network-wide Settings

```php
// Network activation hook
function plugin_name_network_activate($network_wide) {
    if ($network_wide) {
        // Set network-wide options
        update_site_option('plugin_name_network_setting', 'value');
        
        // Or activate for each site
        $sites = get_sites();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            plugin_name_activate();
            restore_current_blog();
        }
    } else {
        plugin_name_activate();
    }
}
register_activation_hook(__FILE__, 'plugin_name_network_activate');
```

### Network Admin Menu

```php
function plugin_name_network_menu() {
    add_menu_page(
        'Plugin Name',
        'Plugin Name',
        'manage_network_options',  // Network capability
        'plugin-name-network',
        'plugin_name_network_page'
    );
}

function plugin_name_network_page() {
    if (!current_user_can('manage_network_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Handle network-wide settings
    if (isset($_POST['save_network_settings'])) {
        check_admin_referer('plugin_network_settings');
        update_site_option('plugin_name_setting', $_POST['setting']);
    }
    
    $setting = get_site_option('plugin_name_setting', '');
    ?>
    <div class="wrap">
        <h1>Network Settings</h1>
        <form method="post">
            <?php wp_nonce_field('plugin_network_settings'); ?>
            <!-- Settings form -->
            <input type="submit" name="save_network_settings" value="Save Network Settings">
        </form>
    </div>
    <?php
}
```

### Per-site vs Network-wide Data

```php
function plugin_name_get_setting($key, $default = '') {
    if (is_multisite()) {
        // Check if network-wide setting exists
        $network_value = get_site_option('plugin_name_' . $key);
        if ($network_value !== false) {
            return $network_value;
        }
    }
    
    // Fall back to site-specific setting
    return get_option('plugin_name_' . $key, $default);
}
```

**KEEP IT SIMPLE:** Only add multi-site support if you actually need it. Most plugins work fine with just single-site support.

---

## Conclusion

This guide represents the standard for WordPress plugin development in this project. All new plugins should follow these patterns and principles. When in doubt, refer to the Graylog Search plugin as the reference implementation.

**Core Principles:**
1. **Security First** - Sanitize input, escape output, verify permissions
2. **Keep It Simple** - Don't over-engineer, add complexity only when needed
3. **User Experience** - Clear errors, loading states, responsive design
4. **Maintainability** - Document changes, version properly, clean code
5. **Performance** - Cache expensive operations, load only what's needed

**Remember:** Start simple and add features as needed. A working, secure plugin is better than a complex, broken one.

