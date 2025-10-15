<?php
/**
 * Settings page for Web Embed plugin
 * 
 * Manages the admin interface including menu, settings, and builder tab.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Add admin menu
 * 
 * Creates top-level menu item with Builder and Settings submenus.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_add_admin_menu() {
    // Main menu item - Builder page
    add_menu_page(
        __('Web Embed', 'web-embed'),
        __('Web Embed', 'web-embed'),
        'edit_posts', // Capability for builder
        'web-embed',
        'web_embed_main_page',
        'dashicons-embed-generic',
        30
    );
    
    // Submenu - Builder (rename first submenu to match)
    add_submenu_page(
        'web-embed',
        __('Shortcode Builder', 'web-embed'),
        __('Builder', 'web-embed'),
        'edit_posts',
        'web-embed', // Same slug as parent to make it the default tab
        'web_embed_main_page'
    );
    
    // Submenu - Settings
    add_submenu_page(
        'web-embed',
        __('Web Embed Settings', 'web-embed'),
        __('Settings', 'web-embed'),
        'manage_options', // Capability for settings
        'web-embed-settings',
        'web_embed_settings_page'
    );
}
add_action('admin_menu', 'web_embed_add_admin_menu');

/**
 * Main page with tabs
 * 
 * Renders the main admin page with tab navigation.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_main_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'builder';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Web Embed', 'web-embed'); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=web-embed&tab=builder" class="nav-tab <?php echo $active_tab === 'builder' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Shortcode Builder', 'web-embed'); ?>
            </a>
            <a href="?page=web-embed-settings" class="nav-tab">
                <?php esc_html_e('Settings', 'web-embed'); ?>
            </a>
        </h2>
        
        <div class="tab-content">
            <?php web_embed_builder_tab(); ?>
        </div>
    </div>
    <?php
}

/**
 * Builder tab content
 * 
 * Renders the visual shortcode builder interface.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_builder_tab() {
    // Check user capability
    if (!web_embed_user_can('builder')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'web-embed'));
    }
    ?>
    <div class="web-embed-builder-container">
        <div class="web-embed-builder-form">
            <h2><?php esc_html_e('Create Your Shortcode', 'web-embed'); ?></h2>
            
            <form id="web-embed-builder-form">
                <?php wp_nonce_field('web_embed_nonce', 'web_embed_nonce'); ?>
                
                <!-- URL -->
                <div class="form-field">
                    <label for="builder_url">
                        <?php esc_html_e('URL', 'web-embed'); ?> <span class="required">*</span>
                    </label>
                    <input type="url" 
                           id="builder_url" 
                           name="url" 
                           class="regular-text" 
                           placeholder="https://example.com"
                           required>
                    <p class="description">
                        <?php esc_html_e('The URL of the content you want to embed', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Width -->
                <div class="form-field">
                    <label for="builder_width"><?php esc_html_e('Width', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_width" 
                           name="width" 
                           class="regular-text" 
                           value="100%" 
                           placeholder="100%">
                    <p class="description">
                        <?php esc_html_e('Width (e.g., 100%, 800px)', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Height -->
                <div class="form-field">
                    <label for="builder_height"><?php esc_html_e('Height', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_height" 
                           name="height" 
                           class="regular-text" 
                           value="600px" 
                           placeholder="600px">
                    <p class="description">
                        <?php esc_html_e('Height (e.g., 600px, 50vh)', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Responsive -->
                <div class="form-field">
                    <label>
                        <input type="checkbox" 
                               id="builder_responsive" 
                               name="responsive" 
                               value="true" 
                               checked>
                        <?php esc_html_e('Make responsive (recommended)', 'web-embed'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Automatically adjust size for mobile devices', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Border -->
                <div class="form-field">
                    <label for="builder_border"><?php esc_html_e('Border', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_border" 
                           name="border" 
                           class="regular-text" 
                           value="none" 
                           placeholder="none">
                    <p class="description">
                        <?php esc_html_e('CSS border (e.g., 2px solid #ccc, none)', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Border Radius -->
                <div class="form-field">
                    <label for="builder_border_radius"><?php esc_html_e('Border Radius', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_border_radius" 
                           name="border_radius" 
                           class="regular-text" 
                           value="0" 
                           placeholder="0">
                    <p class="description">
                        <?php esc_html_e('Rounded corners (e.g., 10px, 0)', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Title -->
                <div class="form-field">
                    <label for="builder_title"><?php esc_html_e('Title', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_title" 
                           name="title" 
                           class="regular-text" 
                           value="<?php esc_attr_e('Embedded Content', 'web-embed'); ?>" 
                           placeholder="<?php esc_attr_e('Embedded Content', 'web-embed'); ?>">
                    <p class="description">
                        <?php esc_html_e('Accessible title for screen readers', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Loading -->
                <div class="form-field">
                    <label>
                        <input type="checkbox" 
                               id="builder_loading" 
                               name="loading" 
                               value="lazy" 
                               checked>
                        <?php esc_html_e('Lazy load (better performance)', 'web-embed'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Load embed only when visible', 'web-embed'); ?>
                    </p>
                </div>
                
                <!-- Custom Class -->
                <div class="form-field">
                    <label for="builder_class"><?php esc_html_e('Custom CSS Class', 'web-embed'); ?></label>
                    <input type="text" 
                           id="builder_class" 
                           name="class" 
                           class="regular-text" 
                           placeholder="my-custom-class">
                    <p class="description">
                        <?php esc_html_e('Optional CSS class for custom styling', 'web-embed'); ?>
                    </p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e('Generate and Preview', 'web-embed'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="web-embed-shortcode-output" style="display: none;">
            <h2><?php esc_html_e('Your Shortcode', 'web-embed'); ?></h2>
            <div class="shortcode-display-wrapper">
                <code class="shortcode-display" id="shortcode-output"></code>
                <button type="button" class="button copy-shortcode" id="copy-shortcode" disabled>
                    <?php esc_html_e('Copy to Clipboard', 'web-embed'); ?>
                </button>
                <span class="copy-feedback" style="display: none;">
                    <?php esc_html_e('Copied!', 'web-embed'); ?>
                </span>
            </div>
            
            <h2><?php esc_html_e('Live Preview', 'web-embed'); ?></h2>
            <div class="preview-container" id="preview-container">
                <p class="description"><?php esc_html_e('Preview will appear here...', 'web-embed'); ?></p>
            </div>
        </div>
        
        <div class="web-embed-builder-tips">
            <h3><?php esc_html_e('💡 Quick Tips', 'web-embed'); ?></h3>
            <ul>
                <li><strong><?php esc_html_e('Required:', 'web-embed'); ?></strong> <?php esc_html_e('Only the URL field is required', 'web-embed'); ?></li>
                <li><strong><?php esc_html_e('Responsive:', 'web-embed'); ?></strong> <?php esc_html_e('Enable for mobile-friendly embeds', 'web-embed'); ?></li>
                <li><strong><?php esc_html_e('Border:', 'web-embed'); ?></strong> <?php esc_html_e('Use CSS syntax like "2px solid #ccc"', 'web-embed'); ?></li>
                <li><strong><?php esc_html_e('Testing:', 'web-embed'); ?></strong> <?php esc_html_e('Preview shows exactly how it will appear on your site', 'web-embed'); ?></li>
                <li><strong><?php esc_html_e('Whitelist:', 'web-embed'); ?></strong> 
                    <?php
                    printf(
                        /* translators: %s: link to settings page */
                        esc_html__('Check %s if URL is blocked', 'web-embed'),
                        '<a href="' . esc_url(admin_url('admin.php?page=web-embed-settings')) . '">' . esc_html__('settings', 'web-embed') . '</a>'
                    );
                    ?>
                </li>
            </ul>
            
            <h3><?php esc_html_e('⚠️ Sites That Block Embedding', 'web-embed'); ?></h3>
            <p><?php esc_html_e('Many major websites (Google, Facebook, Twitter, etc.) prevent embedding for security reasons. The preview will appear empty for these sites.', 'web-embed'); ?></p>
            
            <h3><?php esc_html_e('✅ URLs That Work Well', 'web-embed'); ?></h3>
            <ul>
                <li><strong><?php esc_html_e('Google Maps:', 'web-embed'); ?></strong> https://www.google.com/maps/embed?pb=...</li>
                <li><strong><?php esc_html_e('YouTube:', 'web-embed'); ?></strong> https://www.youtube.com/embed/VIDEO_ID</li>
                <li><strong><?php esc_html_e('Your dashboards:', 'web-embed'); ?></strong> <?php esc_html_e('Internal tools and monitoring systems', 'web-embed'); ?></li>
                <li><strong><?php esc_html_e('Most documentation:', 'web-embed'); ?></strong> <?php esc_html_e('ReadTheDocs, GitHub Pages', 'web-embed'); ?></li>
            </ul>
            
            <h3><?php esc_html_e('🏢 Enterprise Apps Not Working?', 'web-embed'); ?></h3>
            <p><strong><?php esc_html_e('Common issue:', 'web-embed'); ?></strong> <?php esc_html_e('Your internal apps may have X-Frame-Options enabled.', 'web-embed'); ?></p>
            <p><?php esc_html_e('See the included documentation for configuration guides by platform (Spring, Django, .NET, etc.)', 'web-embed'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Settings page
 * 
 * Renders the settings page with security and cache options.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_settings_page() {
    // Check user capability
    if (!web_embed_user_can('settings')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'web-embed'));
    }
    
    // Handle form submission
    if (isset($_POST['web_embed_settings_submit'])) {
        check_admin_referer('web_embed_settings_nonce');
        web_embed_save_settings();
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             esc_html__('Settings saved successfully!', 'web-embed') . 
             '</p></div>';
    }
    
    // Handle cache clear
    if (isset($_POST['web_embed_clear_cache'])) {
        check_admin_referer('web_embed_cache_nonce');
        $count = web_embed_clear_all_cache();
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(
                 /* translators: %d: number of caches cleared */
                 esc_html(_n('%d cache cleared.', '%d caches cleared.', $count, 'web-embed')),
                 $count
             ) . 
             '</p></div>';
    }
    
    // Get current settings
    $whitelist_mode = get_option('web_embed_whitelist_mode', '0');
    $allowed_domains = get_option('web_embed_allowed_domains', '');
    $https_only = get_option('web_embed_https_only', '1');
    $cache_duration = get_option('web_embed_cache_duration', 3600);
    $default_width = get_option('web_embed_default_width', '100%');
    $default_height = get_option('web_embed_default_height', '600px');
    $default_responsive = get_option('web_embed_default_responsive', '1');
    $custom_css_class = get_option('web_embed_custom_css_class', '');
    
    // Get cache stats
    $cache_stats = web_embed_get_cache_stats();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Web Embed Settings', 'web-embed'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('web_embed_settings_nonce'); ?>
            
            <h2><?php esc_html_e('Security Settings', 'web-embed'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="whitelist_mode"><?php esc_html_e('Domain Whitelist', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="whitelist_mode" 
                                   name="whitelist_mode" 
                                   value="1" 
                                   <?php checked($whitelist_mode, '1'); ?>>
                            <?php esc_html_e('Enable domain whitelist (only allow approved domains)', 'web-embed'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, only domains in the list below can be embedded.', 'web-embed'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="allowed_domains"><?php esc_html_e('Allowed Domains', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <textarea id="allowed_domains" 
                                  name="allowed_domains" 
                                  rows="5" 
                                  class="large-text"><?php echo esc_textarea($allowed_domains); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('One domain per line. Use *.example.com for subdomains.', 'web-embed'); ?><br>
                            <?php esc_html_e('Example: dashboard.company.com or *.company.com', 'web-embed'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="https_only"><?php esc_html_e('HTTPS Only', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="https_only" 
                                   name="https_only" 
                                   value="1" 
                                   <?php checked($https_only, '1'); ?>>
                            <?php esc_html_e('Only allow HTTPS URLs (recommended for security)', 'web-embed'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e('Cache Settings', 'web-embed'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php esc_html_e('Cache Duration', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cache_duration" 
                               name="cache_duration" 
                               value="<?php echo esc_attr($cache_duration); ?>" 
                               class="small-text" 
                               min="0">
                        <?php esc_html_e('seconds', 'web-embed'); ?>
                        <p class="description">
                            <?php esc_html_e('How long to cache embed HTML. 3600 = 1 hour. Set to 0 to disable caching.', 'web-embed'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cache Status', 'web-embed'); ?></th>
                    <td>
                        <p>
                            <?php
                            printf(
                                /* translators: 1: number of cached items, 2: cache size, 3: cache type */
                                esc_html__('%1$d items cached (%2$s) using %3$s', 'web-embed'),
                                $cache_stats['total_cached'],
                                $cache_stats['cache_size'],
                                $cache_stats['object_cache'] ? 
                                    esc_html__('object cache + transients', 'web-embed') : 
                                    esc_html__('transients only', 'web-embed')
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e('Default Shortcode Settings', 'web-embed'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="default_width"><?php esc_html_e('Default Width', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="default_width" 
                               name="default_width" 
                               value="<?php echo esc_attr($default_width); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="default_height"><?php esc_html_e('Default Height', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="default_height" 
                               name="default_height" 
                               value="<?php echo esc_attr($default_height); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="default_responsive"><?php esc_html_e('Default Responsive', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="default_responsive" 
                                   name="default_responsive" 
                                   value="1" 
                                   <?php checked($default_responsive, '1'); ?>>
                            <?php esc_html_e('Make embeds responsive by default', 'web-embed'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="custom_css_class"><?php esc_html_e('Custom CSS Class', 'web-embed'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="custom_css_class" 
                               name="custom_css_class" 
                               value="<?php echo esc_attr($custom_css_class); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Add this class to all embeds by default', 'web-embed'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="web_embed_settings_submit" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Settings', 'web-embed'); ?>">
            </p>
        </form>
        
        <hr>
        
        <h2><?php esc_html_e('Cache Management', 'web-embed'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('web_embed_cache_nonce'); ?>
            <p>
                <?php esc_html_e('Clear all cached embed HTML. Use this if embeds are not updating or after changing settings.', 'web-embed'); ?>
            </p>
            <p class="submit">
                <input type="submit" 
                       name="web_embed_clear_cache" 
                       class="button" 
                       value="<?php esc_attr_e('Clear All Cache', 'web-embed'); ?>">
            </p>
        </form>
    </div>
    <?php
}

/**
 * Save settings
 * 
 * Processes and saves settings form data.
 *
 * @since 1.0.0
 * @return void
 */
function web_embed_save_settings() {
    // Sanitize and save each setting
    update_option('web_embed_whitelist_mode', isset($_POST['whitelist_mode']) ? '1' : '0');
    update_option('web_embed_allowed_domains', sanitize_textarea_field($_POST['allowed_domains'] ?? ''));
    update_option('web_embed_https_only', isset($_POST['https_only']) ? '1' : '0');
    update_option('web_embed_cache_duration', absint($_POST['cache_duration'] ?? 3600));
    update_option('web_embed_default_width', sanitize_text_field($_POST['default_width'] ?? '100%'));
    update_option('web_embed_default_height', sanitize_text_field($_POST['default_height'] ?? '600px'));
    update_option('web_embed_default_responsive', isset($_POST['default_responsive']) ? '1' : '0');
    update_option('web_embed_custom_css_class', sanitize_html_class($_POST['custom_css_class'] ?? ''));
}

