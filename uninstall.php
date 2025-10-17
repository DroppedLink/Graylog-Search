<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GraylogSearch
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options.
 */
function graylog_search_uninstall() {
	global $wpdb;

	// Check if user wants to delete all data on uninstall.
	$delete_data = get_option( 'graylog_search_delete_on_uninstall', '0' );

	if ( '1' === $delete_data ) {
		// User has opted to delete all data - perform full cleanup.

		// Delete database table.
		$table_name = $wpdb->prefix . 'graylog_search_history';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// Delete plugin options.
		delete_option( 'graylog_api_url' );
		delete_option( 'graylog_api_token' );
		delete_option( 'graylog_search_disable_ssl_verify' );
		delete_option( 'graylog_search_github_token' );
		delete_option( 'graylog_search_delete_on_uninstall' );

		// Delete all transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_graylog_search_' ) . '%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_graylog_search_' ) . '%'
			)
		);

		// Delete user meta data (saved searches, recent searches, preferences).
		delete_metadata( 'user', 0, 'graylog_saved_searches', '', true );
		delete_metadata( 'user', 0, 'graylog_recent_searches', '', true );
		delete_metadata( 'user', 0, 'graylog_timezone', '', true );

		// Remove custom capability from all roles.
		$roles = array( 'administrator', 'editor', 'author' );
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'search_graylog_logs' );
			}
		}

		// Clear any remaining caches.
		wp_cache_flush();
	} else {
		// User wants to keep data - only remove the uninstall option itself.
		// This allows reinstalling the plugin later with data intact.
		delete_option( 'graylog_search_delete_on_uninstall' );
	}
}

// Run uninstall.
graylog_search_uninstall();
