<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Add admin menu
add_action('admin_menu', 'web_embed_add_admin_menu');
function web_embed_add_admin_menu() {
    // Main menu item - Builder page
    add_menu_page(
        'Web Embed',
        'Web Embed',
        'edit_posts',
        'web-embed',
        'web_embed_main_page',
        'dashicons-embed-generic',
        30
    );
    
    // Submenu - Builder (rename first submenu to match)
    add_submenu_page(
        'web-embed',
        'Shortcode Builder',
        'Builder',
        'edit_posts',
        'web-embed',
        'web_embed_main_page'
    );
    
    // Submenu - Settings
    add_submenu_page(
        'web-embed',
        'Web Embed Settings',
        'Settings',
        'manage_options',
        'web-embed-settings',
        'web_embed_settings_page'
    );
}

// Main page with tabs
function web_embed_main_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'builder';
    ?>
    <div class="wrap">
        <h1>Web Embed</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=web-embed&tab=builder" class="nav-tab <?php echo $active_tab == 'builder' ? 'nav-tab-active' : ''; ?>">
                Shortcode Builder
            </a>
            <a href="?page=web-embed-settings" class="nav-tab">
                Settings
            </a>
        </h2>
        
        <div class="tab-content">
            <?php
            if ($active_tab == 'builder') {
                web_embed_builder_tab();
            }
            ?>
        </div>
    </div>
    <?php
}

// Builder tab content (moved from shortcode-builder.php)
function web_embed_builder_tab() {
    // Get current settings for defaults
    $default_width = get_option('web_embed_default_width', '100%');
    $default_height = get_option('web_embed_default_height', '600px');
    $default_responsive = get_option('web_embed_default_responsive', '1') === '1' ? 'true' : 'false';
    $default_class = get_option('web_embed_custom_css_class', '');
    
    ?>
    <p class="description" style="margin-top: 15px;">Create and test your embed shortcodes before adding them to your content.</p>
    
    <div class="web-embed-builder-container">
        <div class="web-embed-builder-form">
            <h2>Shortcode Parameters</h2>
            
            <form id="web-embed-builder-form">
                <?php wp_nonce_field('web_embed_nonce', 'web_embed_nonce_field'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="builder_url">URL <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="builder_url" 
                                   name="url" 
                                   value="" 
                                   class="large-text"
                                   placeholder="https://example.com"
                                   required>
                            <p class="description">The URL you want to embed</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_width">Width</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_width" 
                                   name="width" 
                                   value="<?php echo esc_attr($default_width); ?>" 
                                   class="regular-text"
                                   placeholder="100%">
                            <p class="description">Width (e.g., 100%, 800px, 80vw)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_height">Height</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_height" 
                                   name="height" 
                                   value="<?php echo esc_attr($default_height); ?>" 
                                   class="regular-text"
                                   placeholder="600px">
                            <p class="description">Height (e.g., 600px, 80vh)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_responsive">Responsive Mode</label>
                        </th>
                        <td>
                            <select id="builder_responsive" name="responsive" class="regular-text">
                                <option value="true" <?php selected($default_responsive, 'true'); ?>>Enabled</option>
                                <option value="false" <?php selected($default_responsive, 'false'); ?>>Disabled</option>
                            </select>
                            <p class="description">Makes embed responsive (maintains aspect ratio)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_border">Border</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_border" 
                                   name="border" 
                                   value="none" 
                                   class="regular-text"
                                   placeholder="none">
                            <p class="description">CSS border (e.g., 2px solid #ccc, 1px dashed #999)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_border_radius">Border Radius</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_border_radius" 
                                   name="border_radius" 
                                   value="0" 
                                   class="regular-text"
                                   placeholder="0">
                            <p class="description">CSS border radius (e.g., 5px, 10px)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_class">Custom CSS Classes</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_class" 
                                   name="custom_class" 
                                   value="<?php echo esc_attr($default_class); ?>" 
                                   class="regular-text"
                                   placeholder="">
                            <p class="description">Space-separated CSS class names</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_title">Title (Accessibility)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="builder_title" 
                                   name="title" 
                                   value="Embedded Content" 
                                   class="regular-text"
                                   placeholder="Embedded Content">
                            <p class="description">Descriptive title for screen readers</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_loading">Loading Strategy</label>
                        </th>
                        <td>
                            <select id="builder_loading" name="loading" class="regular-text">
                                <option value="lazy">Lazy (Load when visible)</option>
                                <option value="eager">Eager (Load immediately)</option>
                            </select>
                            <p class="description">When to load the embedded content</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="builder_fallback">Fallback Message</label>
                        </th>
                        <td>
                            <textarea id="builder_fallback" 
                                      name="fallback" 
                                      rows="3" 
                                      class="large-text"
                                      placeholder="Leave empty for default message"></textarea>
                            <p class="description">Message to show if embedding fails (HTML allowed)</p>
                        </td>
                    </tr>
                </table>
                
                <div class="web-embed-builder-actions">
                    <button type="button" id="web-embed-generate-preview" class="button button-primary button-large">
                        Generate Preview & Shortcode
                    </button>
                    <button type="button" id="web-embed-clear-form" class="button button-large">
                        Clear Form
                    </button>
                </div>
            </form>
        </div>
        
        <div class="web-embed-builder-output">
            <div class="web-embed-shortcode-output">
                <h2>Generated Shortcode</h2>
                <div class="shortcode-display-wrapper">
                    <pre id="web-embed-shortcode-display" class="shortcode-display">Fill in the form and click "Generate Preview & Shortcode"</pre>
                    <button type="button" id="web-embed-copy-shortcode" class="button button-secondary" disabled>
                        Copy Shortcode
                    </button>
                </div>
                <div id="web-embed-copy-feedback" class="copy-feedback"></div>
            </div>
            
            <div class="web-embed-preview-output">
                <h2>Live Preview</h2>
                <div id="web-embed-preview-loading" class="preview-loading" style="display: none;">
                    <span class="spinner is-active"></span> Generating preview...
                </div>
                <div id="web-embed-preview-error" class="preview-error notice notice-error" style="display: none;"></div>
                <div id="web-embed-preview-container" class="preview-container">
                    <p class="preview-placeholder">Your embed preview will appear here</p>
                </div>
            </div>
            
                <div class="web-embed-builder-tips">
                <h3>💡 Quick Tips</h3>
                <ul>
                    <li><strong>Required:</strong> Only the URL field is required</li>
                    <li><strong>Responsive:</strong> Enable for mobile-friendly embeds</li>
                    <li><strong>Border:</strong> Use CSS syntax like "2px solid #ccc"</li>
                    <li><strong>Testing:</strong> Preview shows exactly how it will appear on your site</li>
                    <li><strong>Whitelist:</strong> Check <a href="<?php echo admin_url('admin.php?page=web-embed-settings'); ?>">settings</a> if URL is blocked</li>
                </ul>
                
                <h3>⚠️ Sites That Block Embedding</h3>
                <p>Many major websites (Google, Facebook, Twitter, etc.) prevent embedding for security reasons. The preview will appear empty for these sites.</p>
                
                <h3>✅ URLs That Work Well</h3>
                <ul>
                    <li><strong>Google Maps:</strong> https://www.google.com/maps/embed?pb=...</li>
                    <li><strong>YouTube:</strong> https://www.youtube.com/embed/VIDEO_ID</li>
                    <li><strong>Your dashboards:</strong> Internal tools and monitoring systems</li>
                    <li><strong>Most documentation:</strong> ReadTheDocs, GitHub Pages</li>
                </ul>
                
                <h3>🏢 Enterprise Apps Not Working?</h3>
                <p><strong>Common issue:</strong> Your internal apps may have X-Frame-Options enabled.</p>
                <p><strong>Good news:</strong> You can fix it! See <strong>ENTERPRISE_APPS_GUIDE.md</strong> for:</p>
                <ul>
                    <li>How to check if X-Frame-Options is blocking</li>
                    <li>Configuration by platform (Spring, Django, .NET, etc.)</li>
                    <li>Safe ways to allow embedding from WordPress</li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler for clearing cache
add_action('wp_ajax_web_embed_clear_cache', 'web_embed_ajax_clear_cache');
function web_embed_ajax_clear_cache() {
    check_ajax_referer('web_embed_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }
    
    $count = web_embed_clear_all_cache();
    wp_send_json_success(array('message' => "Cleared {$count} cache entries"));
}

// Settings page content
function web_embed_settings_page() {
    // Save settings if form submitted
    if (isset($_POST['web_embed_settings_submit'])) {
        check_admin_referer('web_embed_settings_nonce');
        
        // Security settings
        update_option('web_embed_whitelist_enabled', isset($_POST['web_embed_whitelist_enabled']) ? '1' : '0');
        update_option('web_embed_allowed_domains', sanitize_textarea_field($_POST['web_embed_allowed_domains']));
        update_option('web_embed_https_only', isset($_POST['web_embed_https_only']) ? '1' : '0');
        
        // Caching options
        update_option('web_embed_cache_enabled', isset($_POST['web_embed_cache_enabled']) ? '1' : '0');
        update_option('web_embed_cache_duration', absint($_POST['web_embed_cache_duration']));
        
        // Advanced options
        update_option('web_embed_default_width', sanitize_text_field($_POST['web_embed_default_width']));
        update_option('web_embed_default_height', sanitize_text_field($_POST['web_embed_default_height']));
        update_option('web_embed_default_responsive', isset($_POST['web_embed_default_responsive']) ? '1' : '0');
        update_option('web_embed_custom_css_class', sanitize_text_field($_POST['web_embed_custom_css_class']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $whitelist_enabled = get_option('web_embed_whitelist_enabled', '0');
    $allowed_domains = get_option('web_embed_allowed_domains', '');
    $https_only = get_option('web_embed_https_only', '0');
    $cache_enabled = get_option('web_embed_cache_enabled', '1');
    $cache_duration = get_option('web_embed_cache_duration', '3600');
    $default_width = get_option('web_embed_default_width', '100%');
    $default_height = get_option('web_embed_default_height', '600px');
    $default_responsive = get_option('web_embed_default_responsive', '1');
    $custom_css_class = get_option('web_embed_custom_css_class', '');
    ?>
    <div class="wrap">
        <h1>Web Embed Settings</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=web-embed" class="nav-tab">
                Shortcode Builder
            </a>
            <a href="?page=web-embed-settings" class="nav-tab nav-tab-active">
                Settings
            </a>
        </h2>
        
        <div class="tab-content" style="margin-top: 20px;">
            <form method="post" action="">
            <?php wp_nonce_field('web_embed_settings_nonce'); ?>
            
            <h2>Security Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="web_embed_whitelist_enabled">Enable Whitelist Mode</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="web_embed_whitelist_enabled" 
                               name="web_embed_whitelist_enabled" 
                               value="1"
                               <?php checked($whitelist_enabled, '1'); ?>>
                        <p class="description">When enabled, only domains in the allowed list below can be embedded</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_allowed_domains">Allowed Domains</label>
                    </th>
                    <td>
                        <textarea id="web_embed_allowed_domains" 
                                  name="web_embed_allowed_domains" 
                                  rows="8" 
                                  cols="50" 
                                  class="large-text"
                                  placeholder="example.com&#10;trusted-site.org&#10;another-domain.net"><?php echo esc_textarea($allowed_domains); ?></textarea>
                        <p class="description">Enter one domain per line (e.g., example.com). Subdomains will be automatically allowed.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_https_only">HTTPS Only</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="web_embed_https_only" 
                               name="web_embed_https_only" 
                               value="1"
                               <?php checked($https_only, '1'); ?>>
                        <p class="description">Only allow HTTPS URLs to be embedded (recommended for security)</p>
                    </td>
                </tr>
            </table>
            
            <h2>Caching Options</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="web_embed_cache_enabled">Enable Caching</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="web_embed_cache_enabled" 
                               name="web_embed_cache_enabled" 
                               value="1"
                               <?php checked($cache_enabled, '1'); ?>>
                        <p class="description">Cache embed HTML to improve performance</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_cache_duration">Cache Duration (seconds)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="web_embed_cache_duration" 
                               name="web_embed_cache_duration" 
                               value="<?php echo esc_attr($cache_duration); ?>" 
                               min="60"
                               class="regular-text">
                        <p class="description">How long to cache embeds (default: 3600 = 1 hour)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Clear Cache</th>
                    <td>
                        <button type="button" id="web-embed-clear-cache" class="button">Clear All Cache</button>
                        <span id="web-embed-cache-result"></span>
                        <p class="description">Clear all cached embed content</p>
                    </td>
                </tr>
            </table>
            
            <h2>Advanced Options</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="web_embed_default_width">Default Width</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="web_embed_default_width" 
                               name="web_embed_default_width" 
                               value="<?php echo esc_attr($default_width); ?>" 
                               class="regular-text"
                               placeholder="100%">
                        <p class="description">Default width for embeds (e.g., 100%, 800px)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_default_height">Default Height</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="web_embed_default_height" 
                               name="web_embed_default_height" 
                               value="<?php echo esc_attr($default_height); ?>" 
                               class="regular-text"
                               placeholder="600px">
                        <p class="description">Default height for embeds (e.g., 600px, 80vh)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_default_responsive">Responsive by Default</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="web_embed_default_responsive" 
                               name="web_embed_default_responsive" 
                               value="1"
                               <?php checked($default_responsive, '1'); ?>>
                        <p class="description">Make embeds responsive by default (maintains aspect ratio)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="web_embed_custom_css_class">Custom CSS Classes</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="web_embed_custom_css_class" 
                               name="web_embed_custom_css_class" 
                               value="<?php echo esc_attr($custom_css_class); ?>" 
                               class="regular-text"
                               placeholder="my-custom-class">
                        <p class="description">Default CSS classes to add to embed containers (space-separated)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="web_embed_settings_submit" 
                       id="submit" 
                       class="button button-primary" 
                       value="Save Settings">
            </p>
        </form>
        
        <hr>
        
        <h2>Shortcode Usage Guide</h2>
        <p>Use the <code>[web_embed]</code> shortcode to embed URLs in your pages and posts.</p>
        
        <h3>Basic Usage:</h3>
        <pre><code>[web_embed url="https://example.com"]</code></pre>
        
        <h3>With Custom Dimensions:</h3>
        <pre><code>[web_embed url="https://example.com" width="800px" height="600px"]</code></pre>
        
        <h3>With Styling Options:</h3>
        <pre><code>[web_embed url="https://example.com" border="2px solid #ccc" border_radius="10px" responsive="false"]</code></pre>
        
        <h3>All Available Parameters:</h3>
        <ul>
            <li><strong>url</strong> - The URL to embed (required)</li>
            <li><strong>width</strong> - Width of the embed (default: <?php echo esc_html($default_width); ?>)</li>
            <li><strong>height</strong> - Height of the embed (default: <?php echo esc_html($default_height); ?>)</li>
            <li><strong>responsive</strong> - Make responsive (true/false, default: <?php echo $default_responsive === '1' ? 'true' : 'false'; ?>)</li>
            <li><strong>border</strong> - CSS border style (default: none)</li>
            <li><strong>border_radius</strong> - CSS border radius (default: 0)</li>
            <li><strong>class</strong> - Custom CSS classes</li>
            <li><strong>title</strong> - Accessibility title (default: Embedded Content)</li>
            <li><strong>loading</strong> - Loading strategy: lazy or eager (default: lazy)</li>
            <li><strong>fallback</strong> - Fallback message if embedding fails</li>
        </ul>
        
        <p>For more detailed examples, see the <a href="<?php echo esc_url(WEB_EMBED_PLUGIN_URL . 'USAGE_GUIDE.md'); ?>" target="_blank">Usage Guide</a>.</p>
        </div>
    </div>
    <?php
}

