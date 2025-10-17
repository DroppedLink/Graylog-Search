# Graylog Search WordPress Plugin

A powerful WordPress plugin that provides a user-friendly interface for searching and analyzing Graylog logs via API. Perfect for non-technical users who need quick access to log data.

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/DroppedLink/Graylog-Search/releases)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.2%2B-purple.svg)](https://php.net/)

## Features

### 🔍 Search & Filter
- **Simple Search Interface**: Easy-to-use form for searching logs
- **FQDN Search**: Search by hostname/domain
- **Multi-Value Input**: Enter multiple hostnames, search terms, or filters
- **Time Range Picker**: Search last hour, day, or week
- **Interactive Filtering**: Highlight text to filter, exclude, or copy
- **Active Filters Display**: See and manage all active filters

### 🌐 IP Enrichment
- **Automatic IP Detection**: IPs are automatically detected and highlighted
- **DNS Lookups**: Click any IP to perform reverse DNS lookup
- **Batch Resolution**: Resolve all visible IPs at once
- **Visual Feedback**: Color-coded states for DNS resolution

### 🕐 Timezone Conversion
- **Smart Timezone Selector**: Auto-detects your timezone
- **Toggle View**: Switch between original and converted timestamps
- **Message Timestamp Detection**: Converts timestamps within log messages

### 💾 Saved Searches & History
- **Saved Searches**: Save and reuse common searches
- **Recent Searches**: Auto-tracks your last 10 searches
- **Quick Filters**: Pre-configured searches for common scenarios
- **One-Click Loading**: Instantly reload any saved or recent search

### ⚡ Performance
- **Result Pagination**: Efficiently handles large result sets
- **API Response Caching**: 5-minute cache reduces API load
- **Auto-Refresh**: Configurable intervals (15s, 30s, 60s, 5min)

### 🎨 User Experience
- **⌨️ Keyboard Shortcuts**: Power-user productivity features
  - `Ctrl/Cmd + Enter`: Submit search
  - `Esc`: Clear fields and close popups
  - `/`: Focus search box
  - `?`: Show keyboard shortcuts help
- **🌙 Dark Mode**: Full dark theme with system preference support
- **Beautiful UI**: Modern, responsive design
- **Color-Coded Results**: Visual indicators for log levels

### 📊 Export & Analysis
- **Multiple Export Formats**: CSV, JSON, plain text
- **Copy to Clipboard**: Quick copy of results
- **Row Actions**: Right-click menu for filtering and copying
- **Log Parsing**: Auto-parse JSON, key=value, CEF, and LEEF formats

### 🔄 Auto-Updates
- **GitHub Integration**: Automatic update notifications
- **One-Click Updates**: Update directly from WordPress admin
- **Manual Check**: Force check for updates from settings

## Installation

### Method 1: Download from GitHub (Recommended)

1. Download the latest `graylog-search.zip` from [GitHub Releases](https://github.com/DroppedLink/Graylog-Search/releases)
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the `graylog-search.zip` file
5. Click **Install Now** and then **Activate**

### Method 2: Clone from GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/DroppedLink/Graylog-Search.git graylog-search
```

Then activate the plugin from WordPress admin.

## Configuration

### 1. Basic Setup

1. Go to **Graylog Search → Settings** in WordPress admin
2. Enter your **Graylog API URL** (e.g., `https://graylog.example.com:9000`)
   - The plugin automatically adds `/api` to the URL
3. Enter your **Graylog API Token**
4. Click **Save Settings**

### 2. Create a Graylog API Token

1. Log into your Graylog web interface
2. Go to **System → Users** → Select your user
3. Click **Edit Tokens** tab
4. Create a new token with appropriate permissions
5. Copy the token and paste it into WordPress settings

### 3. Optional Settings

- **Disable SSL Verification**: For development environments with self-signed certificates
- **GitHub Token**: For private repositories or higher API rate limits (optional)

## Usage

### Admin Interface

Navigate to **Graylog Search** in the WordPress admin menu to access the full search interface.

### Shortcode

Embed the search interface on any page or post:

```
[graylog_search]
```

#### Shortcode Attributes

**Custom Height:**
```
[graylog_search height="800px"]
```

**Restrict by Capability:**
```
[graylog_search capability="edit_posts"]
```

**Combined:**
```
[graylog_search height="800px" capability="edit_posts"]
```

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.2 or higher
- **Graylog**: 6.1+ with API access (5.x may work but not tested)
- **Valid Graylog API token** with read permissions

## Graylog Compatibility

- ✅ **Graylog 6.1+**: Fully supported (uses `/api/search/messages` endpoint)
- ⚠️ **Graylog 5.x**: May work with older endpoint (not tested)

The plugin automatically handles Graylog 6.1's new API format with schema/datarows.

## Screenshots

_(Add screenshots of your plugin in action)_

1. Main search interface
2. Results with IP enrichment
3. Dark mode
4. Saved searches panel
5. Settings page

## Frequently Asked Questions

### How do I update the plugin?

The plugin includes automatic update notifications. When a new version is available, you'll see an update notification in your WordPress admin. Click "Update now" to install the latest version.

### Can I use this on a production site?

Yes! The plugin is designed for production use. It includes caching, security features, and proper WordPress integration.

### Does this work with custom Graylog streams?

Yes. You can search across all streams that your API token has access to.

### Can I customize the appearance?

Yes. The plugin uses standard WordPress hooks and filters. You can override styles with custom CSS.

### Is SSL required for Graylog?

SSL is recommended for production. For development/testing, you can disable SSL verification in the plugin settings.

## Development

### File Structure

```
graylog-search/
├── graylog-search.php           # Main plugin file
├── includes/
│   ├── settings.php             # Settings page
│   ├── search-page.php          # Admin search interface
│   ├── shortcode.php            # Shortcode handler
│   ├── ajax-handler.php         # API communication
│   ├── dns-lookup.php           # DNS resolution
│   ├── timezone-handler.php     # Timezone preferences
│   ├── github-updater.php       # Auto-update system
│   ├── saved-searches.php       # Saved searches
│   ├── search-history.php       # Search history
│   ├── regex-search.php         # Regex helpers
│   └── field-manager.php        # Field management
├── assets/
│   ├── css/                     # Stylesheets
│   └── js/                      # JavaScript files
├── src/                         # Modern OOP structure
│   ├── Admin/                   # Admin pages
│   ├── Ajax/                    # AJAX handlers
│   ├── API/                     # API client
│   ├── Frontend/                # Frontend components
│   ├── Helpers/                 # Helper classes
│   └── Updater/                 # Update system
├── languages/                   # Translations
└── uninstall.php               # Cleanup on uninstall
```

### Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Releasing

See [RELEASE_GUIDE.md](RELEASE_GUIDE.md) for detailed instructions on creating releases.

## Support

- **Issues**: [GitHub Issues](https://github.com/DroppedLink/Graylog-Search/issues)
- **Discussions**: [GitHub Discussions](https://github.com/DroppedLink/Graylog-Search/discussions)

## Changelog

### v1.0.0 - Initial Release

- Core search functionality
- IP enrichment with DNS lookups
- Timezone conversion
- Saved searches and history
- Keyboard shortcuts
- Dark mode
- Export to CSV/JSON/TXT
- Auto-updates from GitHub
- WordPress shortcode support
- Interactive filtering
- Query builder
- Regex helper

See [CHANGELOG.md](CHANGELOG.md) for full version history.

## License

This plugin is licensed under the GPL v2 or later.

```
Graylog Search WordPress Plugin
Copyright (C) 2025 DroppedLink

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

## Credits

Developed for network and server administrators who need quick, powerful log search capabilities within WordPress.

## Links

- **Repository**: https://github.com/DroppedLink/Graylog-Search
- **Issues**: https://github.com/DroppedLink/Graylog-Search/issues
- **Releases**: https://github.com/DroppedLink/Graylog-Search/releases
- **Graylog**: https://www.graylog.org/

