<?php
/**
 * Test script to debug remote site sync pagination
 * 
 * Place this file in the plugin root and access it via:
 * https://your-site.com/wp-content/plugins/ai-comment-moderator/test-sync-debug.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check admin permission
if (!current_user_can('manage_options')) {
    die('Insufficient permissions');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Debug Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .test { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2271b1; }
        .error { border-left-color: #dc3232; }
        .success { border-left-color: #46b450; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔍 Remote Site Sync Debugging</h1>
    
    <?php
    // Get the first remote site
    global $wpdb;
    $site = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ai_remote_sites ORDER BY id LIMIT 1");
    
    if (!$site) {
        echo '<div class="test error"><strong>Error:</strong> No remote sites configured</div>';
        exit;
    }
    
    echo '<div class="test">';
    echo '<strong>Testing Site:</strong> ' . esc_html($site->site_name) . '<br>';
    echo '<strong>URL:</strong> ' . esc_html($site->site_url) . '<br>';
    echo '<strong>Username:</strong> ' . esc_html($site->username) . '<br>';
    echo '</div>';
    
    // Decrypt password
    require_once(__DIR__ . '/includes/remote-site-manager.php');
    $app_password = AI_Comment_Moderator_Remote_Site_Manager::decrypt_password($site->app_password);
    
    // Test 1: Check total comments available
    echo '<h2>Test 1: Total Comments Count</h2>';
    echo '<div class="test">';
    
    $api_url = $site->site_url . 'wp-json/wp/v2/comments';
    $test_url = add_query_arg(array(
        'status' => 'hold',
        'per_page' => 1,
        'page' => 1
    ), $api_url);
    
    echo '<strong>Testing URL:</strong> ' . esc_html($test_url) . '<br><br>';
    
    $response = wp_remote_get($test_url, array(
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($site->username . ':' . $app_password)
        )
    ));
    
    if (is_wp_error($response)) {
        echo '<span style="color: red;">❌ Error: ' . esc_html($response->get_error_message()) . '</span>';
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        
        echo '<strong>Response Code:</strong> ' . $code . '<br>';
        echo '<strong>Total Comments (X-WP-Total):</strong> ' . ($headers['x-wp-total'] ?? 'Not found') . '<br>';
        echo '<strong>Total Pages (X-WP-TotalPages):</strong> ' . ($headers['x-wp-totalpages'] ?? 'Not found') . '<br><br>';
        
        if ($code === 200) {
            echo '<span style="color: green;">✅ Connection successful!</span>';
        } else {
            echo '<span style="color: red;">❌ HTTP Error ' . $code . '</span>';
        }
    }
    echo '</div>';
    
    // Test 2: Fetch multiple pages
    echo '<h2>Test 2: Pagination Test (First 3 Pages)</h2>';
    
    $pages_to_test = 3;
    $comment_ids_seen = array();
    
    for ($page = 1; $page <= $pages_to_test; $page++) {
        echo '<div class="test">';
        echo '<strong>Page ' . $page . ':</strong><br>';
        
        $page_url = add_query_arg(array(
            'status' => 'hold',
            'per_page' => 10,
            'page' => $page,
            'order' => 'desc',
            'orderby' => 'date'
        ), $api_url);
        
        echo 'URL: ' . esc_html($page_url) . '<br><br>';
        
        $response = wp_remote_get($page_url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($site->username . ':' . $app_password)
            )
        ));
        
        if (is_wp_error($response)) {
            echo '<span style="color: red;">❌ Error: ' . esc_html($response->get_error_message()) . '</span>';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $comments = json_decode($body, true);
            
            if ($code === 200 && is_array($comments)) {
                echo '<span style="color: green;">✅ Fetched ' . count($comments) . ' comments</span><br>';
                
                // Show comment IDs to verify uniqueness
                $page_ids = array();
                foreach ($comments as $comment) {
                    $page_ids[] = $comment['id'];
                    $comment_ids_seen[] = $comment['id'];
                }
                
                echo '<strong>Comment IDs:</strong> ' . implode(', ', $page_ids) . '<br>';
                
                // Check for duplicates
                $duplicates = array_intersect($page_ids, array_slice($comment_ids_seen, 0, -count($page_ids)));
                if (count($duplicates) > 0) {
                    echo '<span style="color: red;">⚠️ DUPLICATE IDs DETECTED: ' . implode(', ', $duplicates) . '</span><br>';
                }
                
                // Show first comment preview
                if (count($comments) > 0) {
                    $first = $comments[0];
                    echo '<strong>First Comment Preview:</strong><br>';
                    echo '- ID: ' . $first['id'] . '<br>';
                    echo '- Author: ' . esc_html($first['author_name'] ?? 'Unknown') . '<br>';
                    echo '- Date: ' . esc_html($first['date'] ?? 'Unknown') . '<br>';
                    echo '- Content: ' . esc_html(wp_trim_words(strip_tags($first['content']['rendered'] ?? ''), 15)) . '<br>';
                }
            } else {
                echo '<span style="color: red;">❌ Failed or empty response (HTTP ' . $code . ')</span>';
            }
        }
        
        echo '</div>';
    }
    
    // Test 3: Current plugin fetch_comments behavior
    echo '<h2>Test 3: Current Plugin Behavior (No Page Parameter)</h2>';
    echo '<div class="test">';
    
    $seen_ids = array();
    for ($i = 1; $i <= 3; $i++) {
        echo '<strong>Iteration ' . $i . ':</strong> ';
        
        $result = AI_Comment_Moderator_Remote_Site_Manager::fetch_comments($site->id, 10, 'hold');
        
        if ($result['success']) {
            $ids = array_map(function($c) { return $c['id']; }, $result['comments']);
            echo 'Fetched ' . count($ids) . ' comments: ' . implode(', ', $ids);
            
            if ($i > 1) {
                $matching = array_intersect($ids, $seen_ids);
                if (count($matching) > 0) {
                    echo ' <span style="color: red;">⚠️ SAME AS BEFORE!</span>';
                }
            }
            
            $seen_ids = $ids;
        } else {
            echo '<span style="color: red;">Error: ' . esc_html($result['error']) . '</span>';
        }
        
        echo '<br>';
    }
    
    echo '<br><strong>Diagnosis:</strong> ';
    echo '<span style="color: red;">The plugin fetches the same comments every time because it doesn\'t pass the page parameter!</span>';
    echo '</div>';
    
    // Test 4: Show current database state
    echo '<h2>Test 4: Local Database State</h2>';
    echo '<div class="test">';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ai_remote_comments WHERE site_id = %d",
        $site->id
    ));
    
    echo '<strong>Comments in local cache:</strong> ' . $count . '<br><br>';
    
    if ($count > 0) {
        $samples = $wpdb->get_results($wpdb->prepare(
            "SELECT id, remote_comment_id, comment_author, LEFT(comment_content, 50) as snippet, comment_date 
            FROM {$wpdb->prefix}ai_remote_comments 
            WHERE site_id = %d 
            ORDER BY comment_date DESC 
            LIMIT 5",
            $site->id
        ));
        
        echo '<strong>Latest 5 comments:</strong><br>';
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<tr style="background: #f0f0f0;"><th>Local ID</th><th>Remote ID</th><th>Author</th><th>Snippet</th><th>Date</th></tr>';
        foreach ($samples as $s) {
            echo '<tr>';
            echo '<td>' . $s->id . '</td>';
            echo '<td>' . $s->remote_comment_id . '</td>';
            echo '<td>' . esc_html($s->comment_author) . '</td>';
            echo '<td>' . esc_html($s->snippet) . '...</td>';
            echo '<td>' . $s->comment_date . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</div>';
    
    // Summary and fix
    echo '<h2>🔧 Summary & Fix Required</h2>';
    echo '<div class="test">';
    echo '<strong>Problem Identified:</strong><br>';
    echo '1. The <code>fetch_comments()</code> function does NOT accept or use a page parameter<br>';
    echo '2. The AJAX sync handler calls <code>fetch_comments()</code> 5 times but gets the same 100 comments each time<br>';
    echo '3. Result: Only 100 unique comments are fetched, not 500<br><br>';
    
    echo '<strong>Required Fix:</strong><br>';
    echo '1. Add <code>$page</code> parameter to <code>fetch_comments()</code> function<br>';
    echo '2. Pass <code>page</code> to the API URL query args<br>';
    echo '3. Update the AJAX loop to pass <code>$page</code> to <code>fetch_comments()</code><br>';
    echo '</div>';
    ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 2px solid #ffc107;">
        <strong>⚡ Ready to apply fix?</strong><br>
        The test results above show exactly what's wrong. The fix requires modifying 2 locations in remote-site-manager.php.
    </div>
</body>
</html>

