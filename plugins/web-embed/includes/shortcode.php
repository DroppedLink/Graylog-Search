<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Register shortcode
add_shortcode('web_embed', 'web_embed_shortcode');

function web_embed_shortcode($atts) {
    // Get default values from settings
    $default_width = get_option('web_embed_default_width', '100%');
    $default_height = get_option('web_embed_default_height', '600px');
    $default_responsive = get_option('web_embed_default_responsive', '1') === '1' ? 'true' : 'false';
    $default_class = get_option('web_embed_custom_css_class', '');
    
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'url' => '',
        'width' => $default_width,
        'height' => $default_height,
        'responsive' => $default_responsive,
        'border' => 'none',
        'border_radius' => '0',
        'class' => $default_class,
        'title' => 'Embedded Content',
        'loading' => 'lazy',
        'fallback' => ''
    ), $atts);
    
    // Validate URL
    $validation = web_embed_validate_url($atts['url']);
    if (!$validation['valid']) {
        return '<div class="web-embed-error">Error: ' . esc_html($validation['error']) . '</div>';
    }
    
    // Sanitize all parameters
    $url = esc_url($atts['url']);
    $width = sanitize_text_field($atts['width']);
    $height = sanitize_text_field($atts['height']);
    $responsive = filter_var($atts['responsive'], FILTER_VALIDATE_BOOLEAN);
    $border = sanitize_text_field($atts['border']);
    $border_radius = sanitize_text_field($atts['border_radius']);
    $custom_class = sanitize_text_field($atts['class']);
    $title = sanitize_text_field($atts['title']);
    $loading = in_array($atts['loading'], array('lazy', 'eager')) ? $atts['loading'] : 'lazy';
    $fallback = !empty($atts['fallback']) ? wp_kses_post($atts['fallback']) : 'This content cannot be displayed. <a href="' . $url . '" target="_blank" rel="noopener">View in new window</a>';
    
    // Check cache
    $cache_key = md5(serialize($atts));
    $cached_html = web_embed_get_cache($cache_key);
    if ($cached_html !== false) {
        web_embed_enqueue_frontend_assets();
        return $cached_html;
    }
    
    // Generate unique ID for this embed
    $embed_id = 'web-embed-' . uniqid();
    
    // Build CSS classes
    $classes = array('web-embed-container');
    if ($responsive) {
        $classes[] = 'web-embed-responsive';
    }
    if (!empty($custom_class)) {
        $classes[] = $custom_class;
    }
    
    // Build inline styles
    $styles = array();
    if ($border !== 'none') {
        $styles[] = 'border: ' . $border;
    }
    if ($border_radius !== '0') {
        $styles[] = 'border-radius: ' . $border_radius;
    }
    
    // Build object/embed HTML
    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
         id="<?php echo esc_attr($embed_id); ?>"
         <?php if (!empty($styles)): ?>
         style="<?php echo esc_attr(implode('; ', $styles)); ?>"
         <?php endif; ?>>
        <?php if ($responsive): ?>
        <div class="web-embed-responsive-wrapper">
        <?php endif; ?>
            <object data="<?php echo $url; ?>" 
                    <?php if (!$responsive): ?>
                    width="<?php echo esc_attr($width); ?>" 
                    height="<?php echo esc_attr($height); ?>"
                    <?php endif; ?>
                    type="text/html"
                    title="<?php echo esc_attr($title); ?>"
                    aria-label="<?php echo esc_attr($title); ?>">
                <embed src="<?php echo $url; ?>" 
                       <?php if (!$responsive): ?>
                       width="<?php echo esc_attr($width); ?>" 
                       height="<?php echo esc_attr($height); ?>"
                       <?php endif; ?>
                       type="text/html" />
                <div class="web-embed-fallback">
                    <?php echo $fallback; ?>
                </div>
            </object>
        <?php if ($responsive): ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    
    $html = ob_get_clean();
    
    // Cache the output
    web_embed_set_cache($cache_key, $html);
    
    // Enqueue frontend assets
    web_embed_enqueue_frontend_assets();
    
    return $html;
}

// Enqueue scripts for shortcode on frontend
add_action('wp_enqueue_scripts', 'web_embed_register_frontend_assets');
function web_embed_register_frontend_assets() {
    // Register (but don't enqueue yet - only when shortcode is used)
    wp_register_style(
        'web-embed-style',
        WEB_EMBED_PLUGIN_URL . 'assets/css/style.css',
        array(),
        WEB_EMBED_VERSION
    );
    
    wp_register_script(
        'web-embed-script',
        WEB_EMBED_PLUGIN_URL . 'assets/js/embed.js',
        array('jquery'),
        WEB_EMBED_VERSION,
        true
    );
}

// Function to enqueue assets when shortcode is actually used
function web_embed_enqueue_frontend_assets() {
    wp_enqueue_style('web-embed-style');
    wp_enqueue_script('web-embed-script');
}

