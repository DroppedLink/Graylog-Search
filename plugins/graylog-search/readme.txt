=== Graylog Search ===
Contributors: DroppedLink
Tags: graylog, logs, search, monitoring, api
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple interface for non-technical users to search Graylog logs via API

== Description ==

Graylog Search provides a WordPress-integrated interface to search and analyze Graylog logs without requiring users to access the Graylog interface directly.

= Features =

* **Simple Search Interface** - Easy-to-use search with multiple terms support
* **Regex Search** - Advanced pattern matching for power users
* **Query Builder** - Visual interface for building complex queries
* **Interactive Filtering** - Right-click any value to filter/exclude
* **Saved Searches** - Save frequently used searches for quick access
* **DNS Lookup** - Hover over IP addresses to resolve hostnames
* **Export Results** - Export as CSV, JSON, or plain text
* **Timezone Support** - Display results in any timezone
* **Keyboard Shortcuts** - Efficient navigation and actions
* **Shortcode Support** - Embed search interface on any page
* **Auto-Updates** - Automatic updates from GitHub repository

= Use Cases =

* Allow support staff to search logs without Graylog access
* Create public-facing log search pages
* Integrate log searching into existing WordPress workflows
* Provide filtered log access to different user roles

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/graylog-search`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Graylog Search → Settings'
4. Configure your Graylog API URL and token
5. Test the connection
6. Start searching logs!

= Configuration =

1. **API URL**: Full URL to your Graylog API (e.g., `https://graylog.example.com:9000/api`)
2. **API Token**: Generate in Graylog: System → Users → Edit User → Create Token
3. **SSL Verification**: Disable only for development with self-signed certificates

== Frequently Asked Questions ==

= How do I get a Graylog API token? =

1. Log into your Graylog instance
2. Go to System → Users
3. Edit your user
4. Click "Create Token"
5. Give it a name and copy the generated token

= Can I use this on the frontend? =

Yes! Use the `[graylog_search]` shortcode on any page or post. Configure it with:
* `title` - Custom title
* `show_filters` - Show/hide filter options (yes/no)
* `result_limit` - Default result limit (50-500)
* `time_range` - Default time range

Example: `[graylog_search title="Search Logs" result_limit="200"]`

= What permissions are needed? =

* **Search Logs**: Users need the `search_graylog_logs` capability or `read` capability
* **Settings**: Only administrators can configure plugin settings

= Does this work with Graylog 6.x? =

Yes! This plugin is compatible with Graylog 5.x and 6.x.

= Can I disable SSL verification? =

Yes, but it's not recommended for production. Only use this for development/testing with self-signed certificates.

== Screenshots ==

1. Simple search interface with multiple terms
2. Regex search with pattern helper
3. Query builder for complex queries
4. Interactive filtering and DNS lookup
5. Settings page with connection test
6. Export results in multiple formats

== Changelog ==

= 2.0.0 =
* Complete refactoring to OOP architecture
* Implemented WordPress coding standards
* Added proper internationalization support
* Enhanced security with input sanitization and output escaping
* Extracted inline JavaScript to separate files
* Added uninstall.php for proper cleanup
* Improved Settings API integration
* Added proper PHPDoc blocks
* Removed production debug code
* Better error handling and logging
* Release Date: 2024

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major refactoring with improved security, coding standards, and maintainability. All settings and data are preserved during the upgrade.

== Additional Information ==

= Keyboard Shortcuts =

* `Ctrl/Cmd + Enter` - Submit search
* `Ctrl/Cmd + K` - Clear search form
* `Ctrl/Cmd + S` - Save current search
* `Escape` - Close modals/popups
* `?` - Show keyboard shortcuts

= GitHub Repository =

https://github.com/DroppedLink/Graylog-Search

= Support =

For bugs, feature requests, or contributions, please use the GitHub repository.

