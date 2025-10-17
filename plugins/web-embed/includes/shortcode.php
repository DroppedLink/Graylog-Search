<?php
/**
 * Shortcode handling for Web Embed plugin
 * 
 * Registers and renders the [web_embed] shortcode.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Register the shortcode
 */
add_shortcode('web_embed', 'web_embed_shortcode');

/**
 * Render the web embed shortcode
 * 
 * Generates HTML for embedding external URLs using object/embed tags.
 *
 * @since 1.0.0
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content (unused).
 * @return string HTML output for the embed.
 */
function web_embed_shortcode($atts, $content = null) {
    // Default attributes
    $defaults = array(
        'url' => '',
        'width' => get_option('web_embed_default_width', '100%'),
        'height' => get_option('web_embed_default_height', '600px'),
        'responsive' => get_option('web_embed_default_responsive', 'true'),
        'border' => 'none',
        'border_radius' => '0',
        'class' => get_option('web_embed_custom_css_class', ''),
        'title' => __('Embedded Content', 'web-embed'),
        'loading' => 'lazy',
        'fallback' => '',
    );
    
    // Merge with user attributes
    $atts = shortcode_atts($defaults, $atts, 'web_embed');
    
    // Sanitize attributes
    $atts = web_embed_sanitize_attributes($atts);
    
    // Validate URL
    $validation = web_embed_validate_url($atts['url']);
    if (!$validation['valid']) {
        return web_embed_render_error($validation['message']);
    }
    
    $url = $validation['url'];
    
    // Check cache
    $cache_key = web_embed_generate_cache_key($atts);
    $cached_html = web_embed_get_cache($cache_key);
    
    if ($cached_html !== false) {
        return $cached_html;
    }
    
    // Generate HTML
    $html = web_embed_render_embed($url, $atts);
    
    // Cache the output
    web_embed_set_cache($cache_key, $html);
    
    return $html;
}

/**
 * Render the embed HTML
 * 
 * Creates the HTML structure for the embed with fallback.
 *
 * @since 1.0.0
 * @param string $url  The URL to embed.
 * @param array  $atts Shortcode attributes.
 * @return string HTML output.
 */
function web_embed_render_embed($url, $atts) {
    $responsive = ($atts['responsive'] === 'true' || $atts['responsive'] === '1');
    $loading = $atts['loading'] === 'lazy' ? 'lazy' : 'eager';
    
    // Build CSS classes
    $classes = array('web-embed-container');
    if ($responsive) {
        $classes[] = 'web-embed-responsive';
    }
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    
    // Build inline styles for object
    $object_styles = array();
    if (!$responsive) {
        $object_styles[] = 'width: ' . esc_attr($atts['width']);
        $object_styles[] = 'height: ' . esc_attr($atts['height']);
    }
    if ($atts['border'] !== 'none') {
        $object_styles[] = 'border: ' . esc_attr($atts['border']);
    }
    if ($atts['border_radius'] !== '0') {
        $object_styles[] = 'border-radius: ' . esc_attr($atts['border_radius']);
    }
    
    // Get fallback content
    $fallback = web_embed_get_fallback($url, $atts);
    
    // Build HTML
    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <object
            data="<?php echo esc_url($url); ?>"
            type="text/html"
            <?php if (!$responsive): ?>
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>"
            <?php endif; ?>
            <?php if (!empty($object_styles)): ?>
                style="<?php echo esc_attr(implode('; ', $object_styles)); ?>"
            <?php endif; ?>
            title="<?php echo esc_attr($atts['title']); ?>"
            aria-label="<?php echo esc_attr($atts['title']); ?>"
        >
            <embed
                src="<?php echo esc_url($url); ?>"
                type="text/html"
                <?php if (!$responsive): ?>
                    width="<?php echo esc_attr($atts['width']); ?>"
                    height="<?php echo esc_attr($atts['height']); ?>"
                <?php endif; ?>
                title="<?php echo esc_attr($atts['title']); ?>"
            />
            <?php echo $fallback; // Already escaped in web_embed_get_fallback() ?>
        </object>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get fallback content
 * 
 * Returns fallback HTML when content can't be embedded.
 *
 * @since 1.0.0
 * @param string $url  The URL that failed to embed.
 * @param array  $atts Shortcode attributes.
 * @return string Fallback HTML.
 */
function web_embed_get_fallback($url, $atts) {
    // Use custom fallback if provided
    if (!empty($atts['fallback'])) {
        return $atts['fallback']; // Already sanitized with wp_kses_post
    }
    
    // Default fallback
    $domain = parse_url($url, PHP_URL_HOST);
    
    ob_start();
    ?>
    <div class="web-embed-fallback">
        <div class="web-embed-fallback-content">
            <svg class="web-embed-fallback-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
            </svg>
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p><?php
                /* translators: %s: domain name */
                printf(
                    esc_html__('This content from %s cannot be displayed inline. It may be protected by security settings.', 'web-embed'),
                    '<strong>' . esc_html($domain) . '</strong>'
                );
            ?></p>
            <a href="<?php echo esc_url($url); ?>" 
               class="web-embed-fallback-button" 
               target="_blank" 
               rel="noopener noreferrer">
                <?php esc_html_e('Open in New Tab', 'web-embed'); ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render error message
 * 
 * Returns HTML for displaying an error.
 *
 * @since 1.0.0
 * @param string $message The error message.
 * @return string HTML output.
 */
function web_embed_render_error($message) {
    // Only show detailed errors to admins
    if (!current_user_can('manage_options')) {
        $message = __('This embed could not be displayed.', 'web-embed');
    }
    
    ob_start();
    ?>
    <div class="web-embed-error">
        <p><strong><?php esc_html_e('Web Embed Error:', 'web-embed'); ?></strong> <?php echo esc_html($message); ?></p>
    </div>
    <?php
    return ob_get_clean();
}

