<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Builder menu is now handled in settings.php as part of the main menu

// AJAX handler for preview
add_action('wp_ajax_web_embed_preview', 'web_embed_ajax_preview');
function web_embed_ajax_preview() {
    check_ajax_referer('web_embed_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        return;
    }
    
    // Get parameters from AJAX request
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $width = isset($_POST['width']) ? $_POST['width'] : '100%';
    $height = isset($_POST['height']) ? $_POST['height'] : '600px';
    $responsive = isset($_POST['responsive']) ? $_POST['responsive'] : 'true';
    $border = isset($_POST['border']) ? $_POST['border'] : 'none';
    $border_radius = isset($_POST['border_radius']) ? $_POST['border_radius'] : '0';
    $custom_class = isset($_POST['custom_class']) ? $_POST['custom_class'] : '';
    $title = isset($_POST['title']) ? $_POST['title'] : 'Embedded Content';
    $loading = isset($_POST['loading']) ? $_POST['loading'] : 'lazy';
    $fallback = isset($_POST['fallback']) ? $_POST['fallback'] : '';
    
    // Build shortcode
    $atts = array(
        'url' => $url,
        'width' => $width,
        'height' => $height,
        'responsive' => $responsive,
        'border' => $border,
        'border_radius' => $border_radius,
        'class' => $custom_class,
        'title' => $title,
        'loading' => $loading,
        'fallback' => $fallback
    );
    
    // Generate the shortcode string
    $shortcode = web_embed_generate_shortcode_string($atts);
    
    // Generate the HTML preview
    $html = web_embed_shortcode($atts);
    
    wp_send_json_success(array(
        'html' => $html,
        'shortcode' => $shortcode
    ));
}

// Generate shortcode string from attributes
function web_embed_generate_shortcode_string($atts) {
    $shortcode = '[web_embed';
    
    foreach ($atts as $key => $value) {
        if (!empty($value)) {
            // Handle values with spaces or special characters
            if (strpos($value, ' ') !== false || strpos($value, '"') !== false) {
                $value = str_replace('"', '\"', $value);
                $shortcode .= ' ' . $key . '="' . $value . '"';
            } else {
                $shortcode .= ' ' . $key . '="' . $value . '"';
            }
        }
    }
    
    $shortcode .= ']';
    return $shortcode;
}

// Builder page content is now in settings.php as web_embed_builder_tab()
// Keeping this function for backwards compatibility (not used)
function web_embed_builder_page() {
    // Get current settings for defaults
    $default_width = get_option('web_embed_default_width', '100%');
    $default_height = get_option('web_embed_default_height', '600px');
    $default_responsive = get_option('web_embed_default_responsive', '1') === '1' ? 'true' : 'false';
    $default_class = get_option('web_embed_custom_css_class', '');
    
    ?>
    <div class="wrap web-embed-builder-wrap">
        <h1>Web Embed Shortcode Builder</h1>
        <p class="description">Create and test your embed shortcodes before adding them to your content.</p>
        
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
                        <li><strong>Whitelist:</strong> Check <a href="<?php echo admin_url('options-general.php?page=web-embed-settings'); ?>">settings</a> if URL is blocked</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Enqueue builder-specific assets
add_action('admin_enqueue_scripts', 'web_embed_enqueue_builder_assets');
function web_embed_enqueue_builder_assets($hook) {
    // Load on our main pages
    if ($hook !== 'toplevel_page_web-embed' && $hook !== 'web-embed_page_web-embed-settings') {
        return;
    }
    
    // Enqueue existing assets
    wp_enqueue_style(
        'web-embed-admin-style',
        WEB_EMBED_PLUGIN_URL . 'assets/css/style.css',
        array(),
        WEB_EMBED_VERSION
    );
    
    // Only enqueue builder JS on the main/builder page
    if ($hook === 'toplevel_page_web-embed') {
        wp_enqueue_script(
            'web-embed-builder-script',
            WEB_EMBED_PLUGIN_URL . 'assets/js/builder.js',
            array('jquery'),
            WEB_EMBED_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('web-embed-builder-script', 'webEmbedBuilder', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('web_embed_nonce')
        ));
    }
}

