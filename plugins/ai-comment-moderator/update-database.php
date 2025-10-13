<?php
/**
 * Database Update Script for AI Comment Moderator
 * 
 * This script adds the remote sites tables to existing installations.
 * Run this once, then DELETE this file.
 * 
 * Access via: /wp-content/plugins/ai-comment-moderator/update-database.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Insufficient permissions');
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

echo "<h1>AI Comment Moderator - Database Update</h1>";
echo "<pre>";

// Check if tables already exist
$remote_sites_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ai_remote_sites'");
$remote_comments_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ai_remote_comments'");

if ($remote_sites_exists && $remote_comments_exists) {
    echo "✓ Remote sites tables already exist. No update needed.\n\n";
    echo "Tables found:\n";
    echo "  - {$wpdb->prefix}ai_remote_sites\n";
    echo "  - {$wpdb->prefix}ai_remote_comments\n";
    echo "\n⚠️  DELETE THIS FILE NOW FOR SECURITY\n";
    echo "</pre>";
    exit;
}

echo "Adding remote sites database tables...\n\n";

// Table for remote sites
$table_remote_sites = $wpdb->prefix . 'ai_remote_sites';
$sql_remote_sites = "CREATE TABLE $table_remote_sites (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    site_name varchar(255) NOT NULL,
    site_url varchar(255) NOT NULL,
    username varchar(100) NOT NULL,
    app_password text NOT NULL,
    is_active tinyint(1) DEFAULT 1,
    last_sync datetime DEFAULT NULL,
    total_comments int DEFAULT 0,
    pending_moderation int DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY site_url (site_url),
    KEY is_active (is_active)
) $charset_collate;";

// Table for remote comments cache
$table_remote_comments = $wpdb->prefix . 'ai_remote_comments';
$sql_remote_comments = "CREATE TABLE $table_remote_comments (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    site_id mediumint(9) NOT NULL,
    remote_comment_id bigint(20) NOT NULL,
    comment_author varchar(255),
    comment_author_email varchar(100),
    comment_content text,
    comment_date datetime,
    post_id bigint(20),
    post_title varchar(255),
    comment_status varchar(20),
    moderation_status varchar(20) DEFAULT 'pending',
    ai_decision varchar(20),
    synced_back tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY site_comment (site_id, remote_comment_id),
    KEY site_id (site_id),
    KEY moderation_status (moderation_status),
    KEY synced_back (synced_back)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Create tables
echo "Creating table: {$table_remote_sites}... ";
dbDelta($sql_remote_sites);
$result1 = $wpdb->get_var("SHOW TABLES LIKE '$table_remote_sites'");
echo $result1 ? "✓ SUCCESS\n" : "✗ FAILED\n";

echo "Creating table: {$table_remote_comments}... ";
dbDelta($sql_remote_comments);
$result2 = $wpdb->get_var("SHOW TABLES LIKE '$table_remote_comments'");
echo $result2 ? "✓ SUCCESS\n" : "✗ FAILED\n";

echo "\n";

if ($result1 && $result2) {
    echo "=================================\n";
    echo "✓ DATABASE UPDATE COMPLETED!\n";
    echo "=================================\n\n";
    echo "You can now:\n";
    echo "1. Go to AI Moderator → Remote Sites\n";
    echo "2. Add your remote WordPress sites\n";
    echo "3. Sync and process comments\n\n";
    echo "⚠️  IMPORTANT: DELETE THIS FILE NOW FOR SECURITY!\n";
    echo "   File: /wp-content/plugins/ai-comment-moderator/update-database.php\n";
} else {
    echo "=================================\n";
    echo "✗ DATABASE UPDATE FAILED\n";
    echo "=================================\n\n";
    echo "Error details:\n";
    echo $wpdb->last_error . "\n\n";
    echo "Try running these SQL commands manually via phpMyAdmin:\n\n";
    echo "---SQL START---\n";
    echo $sql_remote_sites . "\n\n";
    echo $sql_remote_comments . "\n";
    echo "---SQL END---\n";
}

echo "</pre>";
echo "<hr>";
echo '<p><a href="' . admin_url('admin.php?page=ai-comment-moderator-remote') . '" class="button button-primary">Go to Remote Sites Page</a></p>';
echo '<p style="color: red;"><strong>DELETE THIS FILE after running!</strong></p>';
?>

