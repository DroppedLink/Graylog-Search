=== Graylog Search ===
Contributors: droppedlink
Tags: graylog, logs, search, monitoring, api
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A user-friendly WordPress plugin for searching and analyzing Graylog logs via API.

== Description ==

Graylog Search provides a powerful yet simple interface for searching Graylog logs directly from your WordPress admin. Perfect for network administrators, developers, and support teams who need quick access to log data without the complexity of the Graylog interface.

= Key Features =

* **Simple Search Interface** - Easy-to-use form for searching logs by hostname, keywords, and time range
* **IP Enrichment** - Automatic IP detection with reverse DNS lookup capabilities
* **Timezone Conversion** - View timestamps in your local timezone
* **Saved Searches** - Save and reuse your common search queries
* **Dark Mode** - Full dark theme support with system preference detection
* **Keyboard Shortcuts** - Power-user features for faster searching
* **Export Results** - Export to CSV, JSON, or plain text
* **Auto-Updates** - Automatic updates from GitHub releases
* **Shortcode Support** - Embed search interface anywhere with `[graylog_search]`

= Perfect For =

* Network administrators monitoring server logs
* DevOps teams troubleshooting issues
* Support teams investigating customer problems
* Security teams analyzing access logs
* Anyone who needs quick log access without leaving WordPress

= Requirements =

* Graylog 6.1+ with API access
* Valid Graylog API token
* WordPress 5.0+
* PHP 7.2+

== Installation ==

= From GitHub Release (Recommended) =

1. Download the latest `graylog-search.zip` from [GitHub Releases](https://github.com/DroppedLink/Graylog-Search/releases)
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the downloaded ZIP file
5. Click "Install Now" and then "Activate"

= Configuration =

1. Go to **Graylog Search → Settings** in WordPress admin
2. Enter your Graylog API URL (e.g., `https://graylog.example.com:9000`)
3. Enter your Graylog API Token
4. Click "Save Settings"

= Getting a Graylog API Token =

1. Log into your Graylog web interface
2. Go to System → Users → Select your user
3. Go to "Edit Tokens" tab
4. Create a new token
5. Copy and paste into WordPress settings

== Frequently Asked Questions ==

= Do I need a Graylog server? =

Yes, this plugin connects to an existing Graylog server via its API. You'll need:
* A running Graylog server (6.1+ recommended)
* Network access from WordPress to Graylog
* A valid API token with read permissions

= Can I use this on a production site? =

Yes! The plugin includes caching, security features, and proper WordPress integration for production use.

= How do updates work? =

The plugin includes an auto-updater that checks GitHub for new releases. When available, you'll see update notifications in WordPress admin just like any other plugin.

= Can I restrict access? =

Yes! You can control who sees the search interface using:
* WordPress capabilities (manage_options by default)
* Shortcode capability attribute: `[graylog_search capability="edit_posts"]`

= Does it work with custom streams? =

Yes, the plugin searches across all streams that your API token has access to.

= Can I export search results? =

Yes! Export results to CSV, JSON, or plain text format, or copy directly to clipboard.

= Is SSL required? =

SSL is recommended for production. For development environments, you can disable SSL verification in the settings.

== Screenshots ==

1. Main search interface with results
2. IP enrichment with DNS lookup
3. Dark mode interface
4. Saved searches panel
5. Settings page
6. Keyboard shortcuts help

== Changelog ==

= 1.0.0 - 2025-10-17 =
* Initial release
* Core search functionality
* IP enrichment with DNS lookups
* Timezone conversion
* Saved searches and history
* Keyboard shortcuts
* Dark mode support
* Export to CSV/JSON/TXT
* Auto-updates from GitHub
* WordPress shortcode support
* Interactive filtering
* Query builder
* Regex helper

== Upgrade Notice ==

= 1.0.0 =
Initial release with full feature set.

== Additional Info ==

= Support =
* GitHub Issues: https://github.com/DroppedLink/Graylog-Search/issues
* Documentation: https://github.com/DroppedLink/Graylog-Search

= Contributing =
Contributions are welcome! Visit the GitHub repository to submit pull requests or report issues.

= Graylog Compatibility =
* Fully compatible with Graylog 6.1+
* May work with Graylog 5.x (not tested)
* Uses modern API endpoints with automatic format handling

== Privacy Policy ==

This plugin does not collect, store, or transmit any user data. All searches are performed directly between your WordPress site and your Graylog server. API credentials are stored securely in your WordPress database.

