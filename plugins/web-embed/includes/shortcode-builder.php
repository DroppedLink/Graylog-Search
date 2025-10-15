<?php
/**
 * Shortcode Builder functionality
 * 
 * Handles AJAX preview generation and admin asset loading.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * AJAX handler for preview generation
 * 
 * Generates shortcode and HTML preview via AJAX.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_ajax_preview() {
    // Verify nonce
    check_ajax_referer('web_embed_nonce', 'nonce');
    
    // Check capability
    if (!web_embed_user_can('builder')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to preview embeds.', 'web-embed')
        ));
    }
    
    // Get and sanitize parameters
    $atts = web_embed_sanitize_attributes($_POST);
    
    // Validate URL
    $validation = web_embed_validate_url($atts['url']);
    if (!$validation['valid']) {
        wp_send_json_error(array(
            'message' => $validation['message']
        ));
    }
    
    // Generate shortcode string
    $shortcode = web_embed_generate_shortcode_string($atts);
    
    // Generate HTML preview (don't use cache for preview)
    $html = web_embed_render_embed($validation['url'], $atts);
    
    // Return success
    wp_send_json_success(array(
        'shortcode' => $shortcode,
        'html' => $html
    ));
}
add_action('wp_ajax_web_embed_preview', 'web_embed_ajax_preview');

/**
 * Generate shortcode string from attributes
 * 
 * Builds the shortcode string for display/copying.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string Formatted shortcode.
 */
function web_embed_generate_shortcode_string($atts) {
    $params = array();
    
    // Add URL (required)
    if (!empty($atts['url'])) {
        $params[] = 'url="' . esc_attr($atts['url']) . '"';
    }
    
    // Add optional parameters (only if different from defaults)
    $defaults = array(
        'width' => get_option('web_embed_default_width', '100%'),
        'height' => get_option('web_embed_default_height', '600px'),
        'responsive' => get_option('web_embed_default_responsive', '1') === '1' ? 'true' : 'false',
    );
    
    if (!empty($atts['width']) && $atts['width'] !== $defaults['width']) {
        $params[] = 'width="' . esc_attr($atts['width']) . '"';
    }
    
    if (!empty($atts['height']) && $atts['height'] !== $defaults['height']) {
        $params[] = 'height="' . esc_attr($atts['height']) . '"';
    }
    
    if (isset($atts['responsive'])) {
        $responsive = ($atts['responsive'] === 'true' || $atts['responsive'] === '1') ? 'true' : 'false';
        if ($responsive !== $defaults['responsive']) {
            $params[] = 'responsive="' . $responsive . '"';
        }
    }
    
    if (!empty($atts['border']) && $atts['border'] !== 'none') {
        $params[] = 'border="' . esc_attr($atts['border']) . '"';
    }
    
    if (!empty($atts['border_radius']) && $atts['border_radius'] !== '0') {
        $params[] = 'border_radius="' . esc_attr($atts['border_radius']) . '"';
    }
    
    if (!empty($atts['title']) && $atts['title'] !== __('Embedded Content', 'web-embed')) {
        $params[] = 'title="' . esc_attr($atts['title']) . '"';
    }
    
    if (isset($atts['loading']) && $atts['loading'] !== 'lazy') {
        $params[] = 'loading="' . esc_attr($atts['loading']) . '"';
    }
    
    if (!empty($atts['class'])) {
        $params[] = 'class="' . esc_attr($atts['class']) . '"';
    }
    
    // Build shortcode
    return '[web_embed ' . implode(' ', $params) . ']';
}

/**
 * Enqueue builder-specific assets
 * 
 * Loads CSS and JavaScript for the admin builder page.
 *
 * @since 1.0.0
 * @param string $hook The current admin page hook.
 * @return void
 */
function web_embed_enqueue_builder_assets($hook) {
    // Load on our main pages
    if ($hook !== 'toplevel_page_web-embed' && $hook !== 'web-embed_page_web-embed-settings') {
        return;
    }
    
    // Determine which files to load based on debug mode
    $css_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'style.css' : 'style.min.css';
    $js_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'builder.js' : 'builder.min.js';
    
    // Enqueue admin styles
    wp_enqueue_style(
        'web-embed-admin-style',
        WEB_EMBED_PLUGIN_URL . 'assets/css/' . $css_file,
        array(),
        WEB_EMBED_VERSION
    );
    
    // Only enqueue builder JS on the main/builder page
    if ($hook === 'toplevel_page_web-embed') {
        wp_enqueue_script(
            'web-embed-builder-script',
            WEB_EMBED_PLUGIN_URL . 'assets/js/' . $js_file,
            array('jquery'),
            WEB_EMBED_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('web-embed-builder-script', 'webEmbedBuilder', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('web_embed_nonce'),
            'strings' => array(
                'copied' => __('Copied!', 'web-embed'),
                'copyFailed' => __('Failed to copy. Please select and copy manually.', 'web-embed'),
                'generating' => __('Generating preview...', 'web-embed'),
                'error' => __('Error', 'web-embed'),
            )
        ));
    }
}
add_action('admin_enqueue_scripts', 'web_embed_enqueue_builder_assets');

