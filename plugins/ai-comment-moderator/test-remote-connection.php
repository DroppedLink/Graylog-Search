<?php
/**
 * Test Remote Site Connection
 * 
 * This is a standalone test script to diagnose remote site connection issues.
 * Upload this to your WordPress root and access it via browser.
 * DELETE THIS FILE after testing for security.
 */

// Simulate WordPress environment minimally
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Test credentials from your form
$site_url = 'https://www.liirk.com';
$username = 'sdkjfkjhfd0239q2324jkklhj3rwerjkfh';
$app_password = 'ygXQ VPTH UldY NSH0 tr8z ZPs4';

echo "<h1>Remote Site Connection Test</h1>";
echo "<pre>";
echo "Testing connection to: " . esc_html($site_url) . "\n";
echo "Username: " . esc_html($username) . "\n";
echo "Password: " . str_repeat('*', strlen($app_password)) . "\n\n";

// Test 1: Basic site accessibility
echo "=== TEST 1: Site Accessibility ===\n";
$test_url = rtrim($site_url, '/') . '/';
$basic_response = wp_remote_get($test_url, array('timeout' => 10));

if (is_wp_error($basic_response)) {
    echo "❌ FAILED: " . $basic_response->get_error_message() . "\n\n";
} else {
    $code = wp_remote_retrieve_response_code($basic_response);
    echo "✓ Site responded with HTTP " . $code . "\n\n";
}

// Test 2: REST API availability
echo "=== TEST 2: WordPress REST API ===\n";
$api_test_url = $test_url . 'wp-json/';
$api_response = wp_remote_get($api_test_url, array('timeout' => 10));

if (is_wp_error($api_response)) {
    echo "❌ FAILED: " . $api_response->get_error_message() . "\n\n";
} else {
    $code = wp_remote_retrieve_response_code($api_response);
    $body = wp_remote_retrieve_body($api_response);
    echo "Status: HTTP " . $code . "\n";
    
    if ($code === 200) {
        $data = json_decode($body, true);
        echo "✓ REST API is available\n";
        if (isset($data['name'])) {
            echo "Site Name: " . $data['name'] . "\n";
        }
        if (isset($data['description'])) {
            echo "Description: " . $data['description'] . "\n";
        }
    } else {
        echo "❌ REST API returned non-200 status\n";
        echo "Response: " . substr($body, 0, 200) . "...\n";
    }
    echo "\n";
}

// Test 3: Authentication test
echo "=== TEST 3: Application Password Authentication ===\n";
$auth_url = $test_url . 'wp-json/wp/v2/users/me';
$auth_response = wp_remote_get($auth_url, array(
    'timeout' => 15,
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
    )
));

if (is_wp_error($auth_response)) {
    echo "❌ FAILED: " . $auth_response->get_error_message() . "\n\n";
} else {
    $code = wp_remote_retrieve_response_code($auth_response);
    $body = wp_remote_retrieve_body($auth_response);
    
    echo "Status: HTTP " . $code . "\n";
    
    if ($code === 200) {
        echo "✓ Authentication SUCCESSFUL!\n";
        $user_data = json_decode($body, true);
        echo "Logged in as: " . ($user_data['name'] ?? 'Unknown') . "\n";
        echo "User ID: " . ($user_data['id'] ?? 'Unknown') . "\n";
        echo "Roles: " . implode(', ', $user_data['roles'] ?? array()) . "\n";
    } elseif ($code === 401) {
        echo "❌ Authentication FAILED: Invalid username or password\n";
        echo "Check:\n";
        echo "  1. Username is correct (case-sensitive)\n";
        echo "  2. Application password is correct (spaces are OK)\n";
        echo "  3. Application password hasn't been revoked\n";
        echo "  4. User exists on the remote site\n";
    } elseif ($code === 403) {
        echo "❌ Forbidden: User doesn't have sufficient permissions\n";
    } else {
        echo "❌ Unexpected response code\n";
        echo "Response: " . substr($body, 0, 500) . "\n";
    }
    echo "\n";
}

// Test 4: Comments API access
echo "=== TEST 4: Comments API Access ===\n";
$comments_url = $test_url . 'wp-json/wp/v2/comments?per_page=1';
$comments_response = wp_remote_get($comments_url, array(
    'timeout' => 15,
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
    )
));

if (is_wp_error($comments_response)) {
    echo "❌ FAILED: " . $comments_response->get_error_message() . "\n\n";
} else {
    $code = wp_remote_retrieve_response_code($comments_response);
    $body = wp_remote_retrieve_body($comments_response);
    
    echo "Status: HTTP " . $code . "\n";
    
    if ($code === 200) {
        $comments = json_decode($body, true);
        echo "✓ Can access comments API\n";
        echo "Sample comments found: " . count($comments) . "\n";
        
        // Check total comments from headers
        $headers = wp_remote_retrieve_headers($comments_response);
        if (isset($headers['x-wp-total'])) {
            echo "Total comments on site: " . $headers['x-wp-total'] . "\n";
        }
        if (isset($headers['x-wp-totalpages'])) {
            echo "Total pages: " . $headers['x-wp-totalpages'] . "\n";
        }
    } else {
        echo "❌ Cannot access comments\n";
        echo "Response: " . substr($body, 0, 500) . "\n";
    }
    echo "\n";
}

// Test 5: Pending comments check
echo "=== TEST 5: Pending Comments Check ===\n";
$pending_url = $test_url . 'wp-json/wp/v2/comments?status=hold&per_page=10';
$pending_response = wp_remote_get($pending_url, array(
    'timeout' => 15,
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
    )
));

if (!is_wp_error($pending_response)) {
    $code = wp_remote_retrieve_response_code($pending_response);
    
    if ($code === 200) {
        $pending = json_decode(wp_remote_retrieve_body($pending_response), true);
        echo "Pending comments found: " . count($pending) . "\n";
        
        $headers = wp_remote_retrieve_headers($pending_response);
        if (isset($headers['x-wp-total'])) {
            echo "Total pending comments: " . $headers['x-wp-total'] . "\n";
        }
    } else {
        echo "Could not fetch pending comments (HTTP " . $code . ")\n";
    }
}

echo "\n";
echo "=== SUMMARY ===\n";
echo "If all tests passed, you can safely add this remote site.\n";
echo "If authentication failed, verify your credentials on the remote site.\n";
echo "\n";
echo "⚠️  DELETE THIS FILE AFTER TESTING FOR SECURITY ⚠️\n";
echo "</pre>";

