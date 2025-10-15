# Graylog Search WordPress Plugin

A powerful WordPress plugin that provides a user-friendly interface for searching and analyzing Graylog logs via API.

## Features

### ⚡ Phase 1: Core Improvements (v1.7.0)

#### Performance Optimization
- **Result Pagination**: Handles large result sets efficiently with offset-based pagination
- **API Response Caching**: 5-minute cache for search results reduces API load by 80-90%
- **Smart Cache Keys**: Based on query, time range, limit, and offset

#### User Experience Enhancements
- **💾 Saved Searches**: Save and reuse your common searches
  - Save current search with a custom name
  - One-click loading of saved searches
  - Delete unwanted searches
  - Stored in user preferences (no additional database tables)
  
- **🕒 Recent Searches**: Automatic tracking of your last 10 searches
  - Quick preview of search parameters
  - One-click to reload any recent search
  - Auto-updates after each search
  
- **⚡ Quick Filters**: Pre-configured searches for common scenarios
  - Errors (Last Hour)
  - Warnings (Last Hour)
  - Errors (Today)
  - All Logs (Last Hour)
  
- **⌨️ Keyboard Shortcuts**: Power-user productivity features
  - `Ctrl/Cmd + Enter`: Submit search
  - `Esc`: Clear fields and close popups
  - `/`: Focus search box
  - `?`: Show keyboard shortcuts help
  - Visual indicators for all shortcuts
  
- **🌙 Dark Mode**: Full dark theme support
  - Toggle button in bottom-right corner
  - Respects system color scheme preference
  - Preference saved across sessions
  - Smooth transitions between themes

### 🔍 Search & Filter
- **Simple Search Interface**: Search by FQDN, additional terms, and time range (hour/day/week)
- **Multi-Value Input**: Enter multiple hostnames, search terms, or filters
  - One per line, comma-separated, or space-separated
  - 3-line textareas with vertical resize
- **Enhanced Interactive Filtering**: Highlight any text in results for advanced actions
  - **Filter Out**: Exclude rows containing the text
  - **Keep Only**: Show only rows containing the text
  - **Highlight**: Highlight all occurrences with yellow background
  - **Copy**: Copy selected text to clipboard
- **Active Filters Display**: See and manage all active filters with one-click removal

### 🌐 IP Enrichment
- **Automatic IP Detection**: IPs are automatically detected and highlighted in results
- **DNS Lookups**: Click any IP to perform a reverse DNS lookup
- **Batch Resolution**: "Resolve All IPs" button to resolve all visible IPs at once
- **Visual Feedback**: Color-coded states (resolving/resolved/unresolvable)

### 🕐 Timezone Conversion
- **Smart Timezone Selector**: Automatically detects your timezone
- **Toggle View**: Switch between original and converted timestamps
- **Message Timestamp Detection**: Converts timestamps within log messages too

### 🔄 Auto-Refresh
- **Configurable Intervals**: Auto-refresh search results every 15s, 30s, 60s, or 5min
- **Manual Toggle**: Enable/disable with a single click

### 📊 Data Export
- **Multiple Formats**: Export visible results as CSV, JSON, or plain text
- **Copy to Clipboard**: Quick copy of visible rows
- **Filtered Exports**: Only exports what you see (respects active filters)

### 🛠 Row Actions
- **Include/Exclude Filters**: Right-click menu to filter by source
- **Copy Row**: Copy individual log entries
- **Expand Details**: View full log details with parsed fields

### 🔬 Log Parsing
- **Format Detection**: Automatically parse JSON, key=value, CEF, and LEEF formats
- **Field Extraction**: View parsed fields in organized tables
- **Toggle Parsing**: Enable/disable with a checkbox

### 📄 WordPress Integration
- **Admin Page**: Full-featured search interface in WordPress admin
- **Shortcode**: Embed search interface anywhere with `[graylog_search]`
- **Permissions**: Respects WordPress capabilities

## Installation

1. Download `graylog-search.zip` from the releases
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the `graylog-search.zip` file
5. Click "Install Now" and then "Activate"

## Configuration

1. Go to **Settings → Graylog Search** in WordPress admin
2. Enter your Graylog API URL (e.g., `http://logs.example.com:9000`)
   - The plugin automatically adds `/api` to the URL
3. Enter your Graylog API Token
4. Save settings

### Creating a Graylog API Token

1. Log into your Graylog web interface
2. Go to **System → Users** → Select your user
3. Click "Edit tokens"
4. Create a new token with appropriate permissions
5. Copy the token to WordPress settings

## Usage

### Admin Interface
Navigate to **Graylog → Search Logs** in the WordPress admin menu.

### Shortcode
Embed the search interface on any page or post:

```
[graylog_search]
```

Optional attributes:
- `height`: Set custom height (default: 600px)
  ```
  [graylog_search height="800px"]
  ```

- `capability`: Restrict access by capability (default: read)
  ```
  [graylog_search capability="edit_posts"]
  ```

## Graylog Compatibility

- **Graylog 6.1+**: Fully supported (uses `/api/search/messages` endpoint)
- **Graylog 5.x**: May work with older endpoint (not tested)

The plugin automatically handles Graylog 6.1's new API format with schema/datarows.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Graylog 6.1+ with API access
- Valid Graylog API token

## Development

### Building from Source

The plugin is located in `plugins/graylog-search/` directory.

No build process required - it's pure PHP/JavaScript/CSS.

### File Structure

```
plugins/graylog-search/
├── graylog-search.php           # Main plugin file
├── includes/
│   ├── settings.php             # Settings page
│   ├── search-page.php          # Admin search interface
│   ├── shortcode.php            # Shortcode handler
│   ├── ajax-handler.php         # API communication
│   ├── dns-lookup.php           # DNS resolution
│   └── timezone-handler.php     # Timezone preferences
├── assets/
│   ├── css/style.css           # Plugin styles
│   └── js/search.js            # Frontend JavaScript
└── *.md                        # Documentation
```

## Documentation

- **README.md** (this file): Overview and installation
- **USAGE_GUIDE.md**: Detailed user guide
- **SHORTCODE_GUIDE.md**: Shortcode documentation
- **INTERACTIVE_FILTERING_GUIDE.md**: Filtering feature guide
- **TIMEZONE_FEATURE.md**: Timezone conversion details
- **GRAYLOG_6.1_UPDATE.md**: API compatibility notes

## Version History

### v1.4.1 (Latest)
- Added blank lines between entries in text exports

### v1.4.0
- Added CSV/JSON/TXT exports and copy-to-clipboard
- Added row actions menu (include/exclude/copy/details)
- Added parse toggle for JSON/KV/CEF/LEEF formats
- Improved detail drawer with parsed fields

### v1.3.1
- Changed unresolvable IP color to orange

### v1.3.0
- Updated for Graylog 6.1 API compatibility
- Added required `X-Requested-By` header
- Implemented response format conversion for new schema

### v1.2.0
- Added "Resolve All IPs" batch action
- Added timezone conversion with user preferences
- Improved timestamp handling

### v1.1.1
- Added auto-refresh with configurable intervals
- Improved DNS lookup error handling

### v1.1.0
- Added IP address enrichment with DNS lookups
- Added interactive text filtering
- Visual feedback for DNS resolution states

### v1.0.0
- Initial release
- Basic search functionality
- Admin interface and shortcode support

## License

This plugin is provided as-is for use with Graylog log management systems.

## Support

For issues, questions, or feature requests, please contact the plugin author.

## Credits

Developed for network and server administrators who need quick, powerful log search capabilities without leaving WordPress.
