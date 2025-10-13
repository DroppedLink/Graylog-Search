<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

class AI_Comment_Moderator_Remote_Site_Manager {
    
    /**
     * Add a new remote site
     */
    public static function add_site($site_name, $site_url, $username, $app_password) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_remote_sites';
        
        // Ensure URL has trailing slash
        $site_url = rtrim($site_url, '/') . '/';
        
        // Encrypt the app password
        $encrypted_password = self::encrypt_password($app_password);
        
        $result = $wpdb->insert($table, array(
            'site_name' => sanitize_text_field($site_name),
            'site_url' => esc_url_raw($site_url),
            'username' => sanitize_text_field($username),
            'app_password' => $encrypted_password,
            'is_active' => 1,
            'created_at' => current_time('mysql')
        ));
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get all remote sites
     */
    public static function get_sites($active_only = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_remote_sites';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY site_name ASC");
    }
    
    /**
     * Get single site
     */
    public static function get_site($site_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_remote_sites';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $site_id
        ));
    }
    
    /**
     * Update site
     */
    public static function update_site($site_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_remote_sites';
        
        $update_data = array();
        
        if (isset($data['site_name'])) {
            $update_data['site_name'] = sanitize_text_field($data['site_name']);
        }
        if (isset($data['site_url'])) {
            $update_data['site_url'] = esc_url_raw(rtrim($data['site_url'], '/') . '/');
        }
        if (isset($data['username'])) {
            $update_data['username'] = sanitize_text_field($data['username']);
        }
        if (isset($data['app_password'])) {
            $update_data['app_password'] = self::encrypt_password($data['app_password']);
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = $data['is_active'] ? 1 : 0;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $update_data, array('id' => $site_id));
    }
    
    /**
     * Delete site
     */
    public static function delete_site($site_id) {
        global $wpdb;
        
        // Delete associated comments first
        $wpdb->delete($wpdb->prefix . 'ai_remote_comments', array('site_id' => $site_id));
        
        // Delete site
        return $wpdb->delete($wpdb->prefix . 'ai_remote_sites', array('id' => $site_id));
    }
    
    /**
     * Test connection to remote site
     */
    public static function test_connection($site_url, $username, $app_password) {
        $site_url = rtrim($site_url, '/') . '/';
        $api_url = $site_url . 'wp-json/wp/v2/users/me';
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return array(
                'success' => true,
                'user' => $body['name'] ?? 'Unknown',
                'roles' => $body['roles'] ?? array()
            );
        }
        
        return array(
            'success' => false,
            'error' => 'HTTP ' . $code . ': ' . wp_remote_retrieve_response_message($response)
        );
    }
    
    /**
     * Fetch comments from remote site
     */
    public static function fetch_comments($site_id, $limit = 100, $status = 'hold', $page = 1) {
        $site = self::get_site($site_id);
        if (!$site) {
            return array('success' => false, 'error' => 'Site not found');
        }
        
        $api_url = $site->site_url . 'wp-json/wp/v2/comments';
        $api_url = add_query_arg(array(
            'status' => $status,
            'per_page' => min($limit, 100),
            'page' => $page,
            'order' => 'desc',
            'orderby' => 'date'
        ), $api_url);
        
        $app_password = self::decrypt_password($site->app_password);
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($site->username . ':' . $app_password)
            )
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_msg = 'HTTP ' . $code;
            
            // Try to parse error details from response
            if ($body) {
                $error_data = json_decode($body, true);
                if (isset($error_data['message'])) {
                    $error_msg .= ': ' . $error_data['message'];
                }
            }
            
            return array('success' => false, 'error' => $error_msg);
        }
        
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        $comments = json_decode($body, true);
        
        // Validate response
        if (!is_array($comments)) {
            return array('success' => false, 'error' => 'Invalid response from remote site');
        }
        
        // Store comments in local cache
        $stored = self::store_remote_comments($site_id, $comments);
        
        // Update site stats
        self::update_site_stats($site_id);
        
        // Extract pagination metadata from headers
        $total_comments = isset($headers['x-wp-total']) ? intval($headers['x-wp-total']) : null;
        $total_pages = isset($headers['x-wp-totalpages']) ? intval($headers['x-wp-totalpages']) : null;
        
        return array(
            'success' => true,
            'comments' => $comments,
            'count' => count($comments),
            'stored' => $stored,
            'total_available' => $total_comments,
            'total_pages' => $total_pages,
            'current_page' => $page
        );
    }
    
    /**
     * Store remote comments in local database
     */
    private static function store_remote_comments($site_id, $comments) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_remote_comments';
        $stored = 0;
        
        foreach ($comments as $comment) {
            // Check if already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE site_id = %d AND remote_comment_id = %d",
                $site_id,
                $comment['id']
            ));
            
            $data = array(
                'site_id' => $site_id,
                'remote_comment_id' => $comment['id'],
                'comment_author' => $comment['author_name'] ?? '',
                'comment_author_email' => $comment['author_email'] ?? '',
                'comment_content' => $comment['content']['rendered'] ?? '',
                'comment_date' => $comment['date'] ?? current_time('mysql'),
                'post_id' => $comment['post'] ?? 0,
                'post_title' => '', // Will be fetched separately if needed
                'comment_status' => $comment['status'] ?? 'hold',
                'moderation_status' => 'pending'
            );
            
            if ($exists) {
                $wpdb->update($table, $data, array('id' => $exists));
            } else {
                $wpdb->insert($table, $data);
                $stored++;
            }
        }
        
        return $stored;
    }
    
    /**
     * Sync moderation decision back to remote site
     */
    public static function sync_decision_to_remote($site_id, $remote_comment_id, $action) {
        $site = self::get_site($site_id);
        if (!$site) {
            return array('success' => false, 'error' => 'Site not found');
        }
        
        $api_url = $site->site_url . 'wp-json/wp/v2/comments/' . $remote_comment_id;
        $app_password = self::decrypt_password($site->app_password);
        
        // Map our actions to WordPress comment statuses
        $status_map = array(
            'approve' => 'approved',
            'spam' => 'spam',
            'trash' => 'trash',
            'hold' => 'hold'
        );
        
        $new_status = $status_map[$action] ?? 'hold';
        
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($site->username . ':' . $app_password),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('status' => $new_status))
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            // Mark as synced in local database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'ai_remote_comments',
                array('synced_back' => 1, 'updated_at' => current_time('mysql')),
                array('site_id' => $site_id, 'remote_comment_id' => $remote_comment_id)
            );
            
            return array('success' => true);
        }
        
        return array('success' => false, 'error' => 'HTTP ' . $code);
    }
    
    /**
     * Get pending comments from all sites
     */
    public static function get_pending_comments($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT rc.*, rs.site_name, rs.site_url
            FROM {$wpdb->prefix}ai_remote_comments rc
            INNER JOIN {$wpdb->prefix}ai_remote_sites rs ON rc.site_id = rs.id
            WHERE rc.moderation_status = 'pending'
            AND rs.is_active = 1
            ORDER BY rc.comment_date DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Update site statistics
     */
    private static function update_site_stats($site_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM {$wpdb->prefix}ai_remote_comments
            WHERE site_id = %d
        ", $site_id));
        
        $wpdb->update(
            $wpdb->prefix . 'ai_remote_sites',
            array(
                'total_comments' => $stats->total,
                'pending_moderation' => $stats->pending,
                'last_sync' => current_time('mysql')
            ),
            array('id' => $site_id)
        );
    }
    
    /**
     * Encrypt app password for storage
     */
    private static function encrypt_password($password) {
        if (defined('AUTH_KEY') && AUTH_KEY) {
            return base64_encode($password . '::' . md5(AUTH_KEY));
        }
        return base64_encode($password);
    }
    
    /**
     * Decrypt app password
     */
    private static function decrypt_password($encrypted) {
        $decoded = base64_decode($encrypted);
        if (strpos($decoded, '::') !== false) {
            list($password, $hash) = explode('::', $decoded, 2);
            return $password;
        }
        return $decoded;
    }
}

// Add remote sites menu
add_action('admin_menu', 'ai_moderator_add_remote_sites_menu', 13);
function ai_moderator_add_remote_sites_menu() {
    add_submenu_page(
        'ai-comment-moderator',
        'Remote Sites',
        'Remote Sites',
        'manage_options',
        'ai-comment-moderator-remote',
        'ai_moderator_remote_sites_page'
    );
}

function ai_moderator_remote_sites_page() {
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    $site_id = isset($_GET['site']) ? intval($_GET['site']) : 0;
    
    if ($action === 'edit' && $site_id) {
        ai_moderator_edit_remote_site_page($site_id);
    } elseif ($action === 'add') {
        ai_moderator_add_remote_site_page();
    } elseif ($action === 'sync' && $site_id) {
        ai_moderator_sync_remote_site($site_id);
    } else {
        ai_moderator_list_remote_sites_page();
    }
}

function ai_moderator_list_remote_sites_page() {
    $sites = AI_Comment_Moderator_Remote_Site_Manager::get_sites();
    
    // Handle form submissions
    if (isset($_POST['add_remote_site'])) {
        check_admin_referer('ai_moderator_remote_nonce');
        
        $site_name = sanitize_text_field($_POST['site_name']);
        $site_url = esc_url_raw($_POST['site_url']);
        $username = sanitize_text_field($_POST['username']);
        $app_password = $_POST['app_password']; // Don't sanitize - may contain special chars
        
        // Test connection first
        $test_result = AI_Comment_Moderator_Remote_Site_Manager::test_connection(
            $site_url,
            $username,
            $app_password
        );
        
        if ($test_result['success']) {
            // Connection successful, add the site
            $site_id = AI_Comment_Moderator_Remote_Site_Manager::add_site(
                $site_name,
                $site_url,
                $username,
                $app_password
            );
            
            if ($site_id) {
                echo '<div class="notice notice-success"><p>✓ Remote site added successfully! Connected as: ' . esc_html($test_result['user']) . '</p></div>';
                $sites = AI_Comment_Moderator_Remote_Site_Manager::get_sites(); // Refresh
            } else {
                echo '<div class="notice notice-error"><p>✗ Failed to add remote site to database.</p></div>';
            }
        } else {
            // Connection failed, show detailed error
            echo '<div class="notice notice-error"><p>✗ Failed to connect to remote site: ' . esc_html($test_result['error']) . '</p>';
            echo '<p><strong>Troubleshooting tips:</strong></p>';
            echo '<ul style="list-style: disc; margin-left: 20px;">';
            echo '<li>Verify the site URL is correct and includes https://</li>';
            echo '<li>Check that the username exists on the remote site</li>';
            echo '<li>Ensure the Application Password is valid and not revoked</li>';
            echo '<li>Confirm the remote site has WordPress REST API enabled</li>';
            echo '<li>Make sure the remote site is accessible from this server</li>';
            echo '</ul></div>';
        }
    }
    
    // Display sync message from transient
    $sync_message = get_transient('ai_moderator_sync_message');
    if ($sync_message) {
        delete_transient('ai_moderator_sync_message');
        $notice_class = $sync_message['type'] === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $notice_class . '"><p>' . esc_html($sync_message['message']) . '</p></div>';
    }
    
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>Remote site deleted.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>
            Remote Sites
            <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-remote&action=add'); ?>" class="page-title-action">Add New Site</a>
        </h1>
        
        <p>Manage multiple WordPress sites and moderate their comments from this central location.</p>
        
        <?php if (empty($sites)): ?>
            <div class="notice notice-info">
                <p>No remote sites configured yet. Add your first site to get started!</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Site Name</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Total Comments</th>
                        <th>Pending Moderation</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                    <tr>
                        <td><strong><?php echo esc_html($site->site_name); ?></strong></td>
                        <td><?php echo esc_html($site->site_url); ?></td>
                        <td>
                            <?php if ($site->is_active): ?>
                                <span style="color: #46b450;">● Active</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">● Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($site->total_comments); ?></td>
                        <td>
                            <?php if ($site->pending_moderation > 0): ?>
                                <strong style="color: #f56e28;"><?php echo number_format($site->pending_moderation); ?></strong>
                            <?php else: ?>
                                0
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($site->last_sync): ?>
                                <?php echo human_time_diff(strtotime($site->last_sync), current_time('timestamp')) . ' ago'; ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small sync-remote-site-btn" data-site-id="<?php echo $site->id; ?>" data-site-name="<?php echo esc_attr($site->site_name); ?>">Sync Now</button>
                            <span class="sync-status-<?php echo $site->id; ?>" style="margin-left: 5px;"></span>
                            <a href="<?php echo admin_url('admin.php?page=ai-comment-moderator-remote&action=edit&site=' . $site->id); ?>" class="button button-small">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="ai-moderator-widget" style="margin-top: 20px;">
            <h2>Quick Add Site</h2>
            <form method="post" action="" id="remote-site-form">
                <?php wp_nonce_field('ai_moderator_remote_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Site Name</th>
                        <td><input type="text" name="site_name" id="remote_site_name" class="regular-text" required placeholder="My WordPress Site" /></td>
                    </tr>
                    <tr>
                        <th>Site URL</th>
                        <td>
                            <input type="url" name="site_url" id="remote_site_url" class="regular-text" required placeholder="https://example.com" />
                            <p class="description">Must include https:// or http://</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Username</th>
                        <td>
                            <input type="text" name="username" id="remote_username" class="regular-text" required placeholder="admin" />
                            <p class="description">Your WordPress username on the remote site (case-sensitive)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Application Password</th>
                        <td>
                            <input type="text" name="app_password" id="remote_app_password" class="regular-text" required placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" />
                            <p class="description">
                                On the remote site, go to <strong>Users → Profile</strong>, scroll to <strong>Application Passwords</strong>, 
                                create a new password with name "AI Moderator", and paste it here. <strong>Spaces are OK.</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>Test Connection</th>
                        <td>
                            <button type="button" id="test-remote-connection" class="button">Test Connection First</button>
                            <span id="remote-connection-status"></span>
                            <p class="description">Test the connection before adding the site to verify credentials.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Add Remote Site', 'primary', 'add_remote_site'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test connection button
            $('#test-remote-connection').on('click', function() {
                var $button = $(this);
                var $status = $('#remote-connection-status');
                
                var siteUrl = $('#remote_site_url').val();
                var username = $('#remote_username').val();
                var appPassword = $('#remote_app_password').val();
                
                if (!siteUrl || !username || !appPassword) {
                    $status.html('<span style="color: #dc3232;"> ✗ Please fill in all fields first</span>');
                    return;
                }
                
                $button.prop('disabled', true).text('Testing...');
                $status.html('<span style="color: #999;"> Testing connection...</span>');
                
                $.post(ajaxurl, {
                    action: 'ai_moderator_test_remote_connection',
                    nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                    site_url: siteUrl,
                    username: username,
                    app_password: appPassword
                }, function(response) {
                    if (response.success) {
                        $status.html('<span style="color: #46b450;"> ✓ Connection successful! User: ' + response.data.user + '</span>');
                    } else {
                        $status.html('<span style="color: #dc3232;"> ✗ ' + response.data + '</span>');
                    }
                }).fail(function() {
                    $status.html('<span style="color: #dc3232;"> ✗ Network error</span>');
                }).always(function() {
                    $button.prop('disabled', false).text('Test Connection First');
                });
            });
            
            // Sync remote site button
            $('.sync-remote-site-btn').on('click', function() {
                var $button = $(this);
                var siteId = $button.data('site-id');
                var siteName = $button.data('site-name');
                var $status = $('.sync-status-' + siteId);
                
                if (!confirm('Sync comments from "' + siteName + '"? This may take a moment for sites with many comments.')) {
                    return;
                }
                
                $button.prop('disabled', true).text('Syncing...');
                $status.html('<span style="color: #999;">Syncing...</span>');
                
                $.post(ajaxurl, {
                    action: 'ai_moderator_sync_remote_site',
                    nonce: '<?php echo wp_create_nonce('ai_comment_moderator_nonce'); ?>',
                    site_id: siteId
                }, function(response) {
                    if (response.success) {
                        $status.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
                        // Reload page after 2 seconds to show updated stats
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $status.html('<span style="color: #dc3232;">✗ ' + response.data + '</span>');
                        $button.prop('disabled', false).text('Sync Now');
                    }
                }).fail(function() {
                    $status.html('<span style="color: #dc3232;">✗ Network error</span>');
                    $button.prop('disabled', false).text('Sync Now');
                });
            });
        });
        </script>
    </div>
    <?php
}

function ai_moderator_add_remote_site_page() {
    // Similar to quick add form above
    echo '<div class="wrap"><h1>Add Remote Site</h1>';
    echo '<p><a href="' . admin_url('admin.php?page=ai-comment-moderator-remote') . '">← Back to Remote Sites</a></p>';
    echo '<p>Use the form on the main page.</p></div>';
}

function ai_moderator_edit_remote_site_page($site_id) {
    $site = AI_Comment_Moderator_Remote_Site_Manager::get_site($site_id);
    echo '<div class="wrap"><h1>Edit Remote Site</h1>';
    echo '<p>Site editing interface coming soon. For now, delete and re-add the site.</p>';
    echo '<p><a href="' . admin_url('admin.php?page=ai-comment-moderator-remote') . '">← Back to Remote Sites</a></p></div>';
}

function ai_moderator_sync_remote_site($site_id) {
    // This function is deprecated - sync now happens via AJAX
    // Redirect to main page to prevent headers already sent error
    wp_safe_redirect(admin_url('admin.php?page=ai-comment-moderator-remote'));
    exit;
}

// AJAX handler for syncing remote sites
add_action('wp_ajax_ai_moderator_sync_remote_site', 'ai_moderator_ajax_sync_remote_site');
function ai_moderator_ajax_sync_remote_site() {
    check_ajax_referer('ai_comment_moderator_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
    
    if (!$site_id) {
        wp_send_json_error('Invalid site ID');
    }
    
    // Fetch more comments - up to 500 in batches
    $total_fetched = 0;
    $total_stored = 0;
    $total_available = null;
    $total_pages_available = null;
    $pages = 5; // Fetch 5 pages of 100 = 500 comments max
    
    for ($page = 1; $page <= $pages; $page++) {
        $result = AI_Comment_Moderator_Remote_Site_Manager::fetch_comments($site_id, 100, 'hold', $page);
        
        if (!$result['success']) {
            wp_send_json_error('Sync failed: ' . $result['error']);
        }
        
        $total_fetched += $result['count'];
        $total_stored += $result['stored'];
        
        // Capture total available from first page
        if ($page === 1 && isset($result['total_available'])) {
            $total_available = $result['total_available'];
            $total_pages_available = $result['total_pages'];
        }
        
        // If we got fewer than 100, there are no more comments
        if ($result['count'] < 100) {
            break;
        }
        
        // Small delay to avoid overwhelming the remote server
        usleep(100000); // 0.1 seconds
    }
    
    // Build message
    $message = "Successfully synced {$total_stored} new comment(s) from remote site. Total fetched: {$total_fetched}";
    if ($total_available !== null) {
        $message .= ". Total pending on remote site: {$total_available}";
        if ($total_stored < $total_available) {
            $remaining = $total_available - $total_fetched;
            $message .= " ({$remaining} remaining - click Sync again to fetch more)";
        }
    }
    
    wp_send_json_success(array(
        'message' => $message,
        'fetched' => $total_fetched,
        'stored' => $total_stored,
        'total_available' => $total_available,
        'has_more' => ($total_available !== null && $total_fetched < $total_available)
    ));
}

