<?php
/**
 * GitHub Auto-Updater
 * Checks for updates from GitHub repository and enables one-click updates
 */

if (!defined('WPINC')) {
    die;
}

class Graylog_Search_GitHub_Updater {
    
    private $plugin_slug;
    private $plugin_basename;
    private $github_repo; // format: username/repository
    private $github_token; // optional, for private repos or higher rate limits
    private $plugin_data;
    
    public function __construct($plugin_file, $github_repo) {
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_basename = $plugin_file;
        $this->github_repo = $github_repo;
        $this->plugin_data = get_plugin_data($plugin_file);
        
        // Get GitHub token from settings if available
        $this->github_token = get_option('graylog_search_github_token', '');
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_source_directory'), 10, 4);
    }
    
    /**
     * Check GitHub for updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get latest release from GitHub
        $remote_data = $this->get_github_release_info();
        
        if (!$remote_data) {
            return $transient;
        }
        
        // Compare versions
        $current_version = $this->plugin_data['Version'];
        $remote_version = $remote_data['version'];
        
        if (version_compare($current_version, $remote_version, '<')) {
            $plugin_data = array(
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $remote_data['homepage'],
                'package' => $remote_data['download_url'],
                'tested' => '6.7',
                'icons' => array(),
            );
            
            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Get plugin info for update screen
     */
    public function plugin_info($false, $action, $args) {
        if ($action !== 'plugin_information') {
            return $false;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $false;
        }
        
        $remote_data = $this->get_github_release_info();
        
        if (!$remote_data) {
            return $false;
        }
        
        $plugin_info = new stdClass();
        $plugin_info->name = $this->plugin_data['Name'];
        $plugin_info->slug = dirname($this->plugin_slug);
        $plugin_info->version = $remote_data['version'];
        $plugin_info->author = $this->plugin_data['Author'];
        $plugin_info->homepage = $remote_data['homepage'];
        $plugin_info->requires = '5.0';
        $plugin_info->tested = '6.7';
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $remote_data['last_updated'];
        $plugin_info->sections = array(
            'description' => $this->plugin_data['Description'],
            'changelog' => $remote_data['changelog'],
        );
        $plugin_info->download_link = $remote_data['download_url'];
        
        return $plugin_info;
    }
    
    /**
     * Fix source directory name after download
     * GitHub zips extract with repo-name format, WordPress expects plugin-slug format
     */
    public function fix_source_directory($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;
        
        // Only fix if this is our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $source;
        }
        
        $corrected_source = trailingslashit($remote_source) . dirname($this->plugin_slug) . '/';
        
        if ($wp_filesystem->move($source, $corrected_source)) {
            return $corrected_source;
        }
        
        return $source;
    }
    
    /**
     * Get latest release info from GitHub API
     */
    private function get_github_release_info() {
        // Check cache first (1 hour)
        $cache_key = 'graylog_search_github_release';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Fetch from GitHub API
        $api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
            'timeout' => 15,
        );
        
        // Add token if available (increases rate limit)
        if (!empty($this->github_token)) {
            $args['headers']['Authorization'] = "token {$this->github_token}";
        }
        
        // Handle SSL verification setting
        $disable_ssl = get_option('graylog_search_disable_ssl_verify', false);
        if ($disable_ssl) {
            $args['sslverify'] = false;
        }
        
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            error_log('Graylog Search GitHub Updater: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);
        
        if (empty($release) || !isset($release['tag_name'])) {
            return false;
        }
        
        // Parse release data
        $version = ltrim($release['tag_name'], 'v'); // Remove 'v' prefix if present
        
        // Find the .zip asset
        $download_url = '';
        if (isset($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (isset($asset['name']) && strpos($asset['name'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        // Fallback to zipball if no asset found
        if (empty($download_url)) {
            $download_url = $release['zipball_url'];
        }
        
        $data = array(
            'version' => $version,
            'download_url' => $download_url,
            'homepage' => $release['html_url'],
            'last_updated' => $release['published_at'],
            'changelog' => !empty($release['body']) ? $release['body'] : 'See GitHub release for details.',
        );
        
        // Cache for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        
        return $data;
    }
    
    /**
     * Force check for updates (called from admin page)
     */
    public static function force_check() {
        delete_transient('graylog_search_github_release');
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
    
    /**
     * Get current update status
     */
    public static function get_update_status() {
        $plugin_slug = 'graylog-search/graylog-search.php';
        $update_plugins = get_site_transient('update_plugins');
        
        if (isset($update_plugins->response[$plugin_slug])) {
            return array(
                'update_available' => true,
                'current_version' => GRAYLOG_SEARCH_VERSION,
                'new_version' => $update_plugins->response[$plugin_slug]->new_version,
                'package' => $update_plugins->response[$plugin_slug]->package,
            );
        }
        
        return array(
            'update_available' => false,
            'current_version' => GRAYLOG_SEARCH_VERSION,
        );
    }
}

// Initialize updater
function graylog_search_init_updater() {
    $plugin_file = GRAYLOG_SEARCH_PLUGIN_DIR . 'graylog-search.php';
    $github_repo = 'DroppedLink/Graylog-Search'; // username/repository
    
    new Graylog_Search_GitHub_Updater($plugin_file, $github_repo);
}
add_action('admin_init', 'graylog_search_init_updater');

// Handle AJAX check for updates
add_action('wp_ajax_graylog_check_updates', 'graylog_search_ajax_check_updates');
function graylog_search_ajax_check_updates() {
    check_ajax_referer('graylog-search-nonce', 'nonce');
    
    if (!current_user_can('update_plugins')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    Graylog_Search_GitHub_Updater::force_check();
    $status = Graylog_Search_GitHub_Updater::get_update_status();
    
    wp_send_json_success($status);
}

